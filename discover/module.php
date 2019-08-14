<?php
class WifiBulbDiscover extends IPSModule {
	################################################################
	#  Public Hidden Methods
	################################################################
	public function Create(){
		parent::Create();
		$this->RegisterAttributeString('DiscoverList', '[]');
	}
	public function GetConfigurationForm() {
		$f 	= json_decode(file_get_contents(__DIR__.'/form.json'));
		$f->actions[0]->values=$this->GetDiscoverList();
		return json_encode($f);	
	}
	################################################################
    #  Public Module Methods
	################################################################
	public function Reset(){
		$this->WriteAttributeString('DiscoverList', '');
		$this->ReloadForm();
	}
	################################################################
    #  Private Utils 
	################################################################
	private function GetDiscoverList(){
		$device_guid= '{5638FDC0-C110-WIFI-MAHO-201905120WBC}';
		$myList 	= json_decode($this->ReadAttributeString('DiscoverList'),true);
		$Timeout	= 1;
		$request 	= iconv("UTF-8", "ASCII", "HF-A11ASSISTHREAD");
		$modules 	= IPS_GetInstanceListByModuleID($device_guid);
		$location	= $this->Translate("Wifi RGB/w Devices");
		$localIPs	= Sys_GetNetworkInfo();
		$create 	= [
			["moduleID"=> $device_guid, "configuration"=>["Identifier"=>""],"location"=>[$location]],
			["moduleID"=> "{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}","configuration"=>["Host"=>"","Port"=>5577,"Open"=>true]]	
		];
		foreach($modules as $index=>$id){
			$cfg = json_decode(IPS_GetConfiguration($id));
			if(empty($cfg->Identifier)){
				$cid = IPS_GetInstance($id)['ConnectionID'];
				if($cid){
					$cfg = json_decode(IPS_GetConfiguration($cid));
					$modules[$cfg->Host]=$id;
				}
			} else {
				$modules[$cfg->Identifier]=$id;
			}
			unset($modules[$index]);
		}
		$ip=$port=null;
		$saveList=false;
		$foundMsg=$this->Translate("Found");
		if(is_null($myList))$myList=[];
		$foundResponse = false;
		// Make a Multicall for all locale ips
		foreach($localIPs as $net){
			if($net['IP']=='0.0.0.0')continue;
			$socket 	= socket_create ( AF_INET, SOCK_DGRAM, SOL_UDP );
			$read 		= [ $socket ];
			$write 		= $except = [ ];
			$response 	= '';
			socket_bind ( $socket, $net['IP'] );
			socket_set_option ( $socket, SOL_SOCKET, SO_BROADCAST, 1 );
			socket_sendto ( $socket, $request, strlen ( $request ),0,'255.255.255.255' ,48899);
			socket_set_option ( $socket, SOL_SOCKET, SO_RCVTIMEO, array ('sec' => $Timeout, 'usec' => '0') );
			while ( socket_select ( $read, $write, $except, $Timeout ) && $read ) {
				socket_recvfrom ( $socket, $response, 2048, null, $ip, $port );
				if(!empty($response)) {
					$foundResponse = true;
					$d = explode(',',$response);
					if(count($d)<3){
						IPS_LogMessage(IPS_GetName($this->InstanceID),__FUNCTION__.'::'.$this->Translate("Error unknown Response").' => '.$response);
						continue;
					}
					$this->SendDebug(__FUNCTION__,"$foundMsg => ". $response, 0);
					list($address,$identifier,$name) = $d;
					$found=array_filter($myList,function($i)use($address){return $i['address']==$address;});
					if(!empty($found)){
						continue;;
					}
					$info = $this->QuerryModel($address);
					$info_str = $this->GetModelInfoStr($info);
					$name="$info_str - $name";
					$create[0]["configuration"]["Identifier"]=$identifier;
					foreach($info as $k=>$v)$create[0]["configuration"][$k]=$v;
					$create[1]["configuration"]["Host"]=$address;
					$myList[]=['address'=>$address,"name"=>"$name ($identifier)","instanceID"=>0, "create"=>$create];
					$saveList=true;
				}
			}
			socket_close ( $socket );
			if($foundResponse) break;
		}
		if($saveList)$this->WriteAttributeString('DiscoverList', json_encode($myList));
		foreach($myList as $key=>$item){
			$identifier	= $item["create"][0]["configuration"]['Identifier'];
			$address	= $item["address"];
			$instanceID = isset($modules[$identifier]) ? $modules[$identifier] : 0;
			$myList[$key]['status']=Sys_Ping($item['address'],1)? $this->Translate("Online"):$this->Translate("Offline");
			$myList[$key]['instanceID']=$instanceID;			
		}
		
		return $myList;
	}
	private function GetModelInfoStr(array $model_info){
   		$a=[];
   		$a[]=['Magic Home','LedNet 8bit','LedNet Original'][$model_info['Protocol']];
   		if($model_info['RgbwCapable']){
   			$model_type=$model_info['Protocol']==self::PROTOCOL_LEDENET_ORIGINAL?2:1;
   		}else $model_type=0;
   		$a[]=['RGB','RGB/w',$this->Translate('RGB/w with extra w channel')][$model_type];
 		return join(' ',$a);     		
     }
	private function QuerryModel($Host){
		$SendData=function($need_csum, array $RawData,  int $Retrys=2)use($Host){
			if(!($socket= @socket_create ( AF_INET, SOCK_STREAM, SOL_TCP ))){
				return null;
			}
			socket_set_option ( $socket, SOL_SOCKET, SO_RCVTIMEO, array ('sec' => 0, 'usec' => '100000') );
			if(!@socket_connect($socket, $Host,5577)){
				@socket_close ( $socket );
				return null;
			}
			if($need_csum)$RawData[]= array_sum($RawData) & 0xFF;
			$tx			= join(array_map(function($b){return chr($b);},$RawData));
			$tx_len		= strlen($tx);
			$this->SendDebug('QuerryModel', $tx,1);
			do {
				$send_bytes = @socket_send ( $socket, $tx, $tx_len,0);
				$send_ok	= $send_bytes == $tx_len;
			   	$check_ok	= true;
			   	$raw 		= '';
				if($send_ok){
					usleep(20000);
					$raw_len= @socket_recv($socket, $raw, 100,0);
					if($raw_len){
						$this->SendDebug('QuerryModel', $raw,1);
						$raw	= array_map(function($c){return ord($c);},str_split($raw));
				   		if($need_csum){
				    		$check_ok = $raw[$raw_len-1] = (array_sum($raw) & 0xFF);
				    	}
					}
				} 
				else if($Retrys>1) {
					$this->SendDebug('QuerryModel',"Receive failed !! retry send",0);
				}
			} while ((!$send_ok || !$check_ok) && --$Retrys > 0);
			socket_close ( $socket );
			return $send_ok?$raw:false;
		};
		
		$this->SendDebug(__FUNCTION__,"Check MagicHome with check sum",0);
		$protocol=self::PROTOCOL_DEFAULT;
		$test = [0x81, 0x8a, 0x8b];
		if(!($raw = $SendData($use_csum=true,$test))){
			$this->SendDebug(__FUNCTION__,"Check PROTOCOL_LEDENET without check sum",0);
			$protocol=self::PROTOCOL_LEDENET;
			$raw = $SendData($use_csum=false,$test);
		}
		if(empty($raw)){
			$this->SendDebug(__FUNCTION__,"Check PROTOCOL_LEDENET_ORIGINAL",0);
			$protocol=self::PROTOCOL_LEDENET_ORIGINAL;
			$test = [0xef, 0x01, 0x77];
			if(!($raw = $SendData($use_csum=true,$test)))
				$raw=$SendData($use_csum=false,$test);
		}
		if(empty($raw)){
			return false;
		}
	    //------------------------------------
		//- Detect Device Attributes
		//------------------------------------
		# Devices that don't require a separate rgb/w bit
		$rgbw_protocol  = ($raw[1] == 0x04 ||  $raw[1] == 0x33 || $raw[1] == 0x81);
	    # Devices that actually support rgbw
	    $rgbw_capable = ($raw[1] == 0x04 || $raw[1] == 0x25 || $raw[1] == 0x33 || $raw[1] == 0x81 || $raw[1] == 0x44);
	    # Devices that use an 8-byte protocol
	    if ($raw[1] == 0x25 || $raw[1] == 0x27 || $raw[1] == 0x35) $protocol = self::PROTOCOL_LEDENET;
	    # Devices that use the original LEDENET protocol
	    if ($raw[1] == 0x01 && $protocol!=self::PROTOCOL_LEDENET_ORIGINAL){
	         $protocol = self::PROTOCOL_LEDENET_ORIGINAL;
	    }
	    $r=['Protocol'=>$protocol,'RgbwCapable'=>$rgbw_capable,'RgbwProtocol'=>$rgbw_protocol,'NeedCheckSum'=>$use_csum]; 
	    $this->SendDebug(__FUNCTION__,json_encode($r),0);
	    return $r; 	
	}
	################################################################
    #  Private Constants 
	################################################################
	private const PROTOCOL_DEFAULT 			= 0;
	private const PROTOCOL_LEDENET			= 1;
	private const PROTOCOL_LEDENET_ORIGINAL	= 2;
}
?>