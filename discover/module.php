<?php
class WifiLEDDiscover extends IPSModule {
	
	public function Create(){
		parent::Create();
		$this->RegisterPropertyString('BindIP', ($v=Sys_GetNetworkInfo())?$v[0]['IP']:'');
		$this->RegisterAttributeString('DiscoverList', '[]');
	}
	public function GetConfigurationForm() {
		$f 	= json_decode(file_get_contents(__DIR__.'/form.json'));
		$ips=[];
		if($v=Sys_GetNetworkInfo())foreach($v as $n)$ips[]=['label'=>$n['IP'],'value'=>$n['IP']];
		$f->elements[0]->options=$ips;
		$f->actions[0]->values=$this->GetDiscoverList();
		return json_encode($f);	
	}

	public function Reset(){
		$this->WriteAttributeString('DiscoverList', '');
		$this->ReloadForm();
	}
	private function GetDiscoverList(){
		$myList 	= json_decode($this->ReadAttributeString('DiscoverList'),true);
		$Timeout	= 1;
		$BindIp 	= $this->ReadPropertyString('BindIP');
		$socket 	= socket_create ( AF_INET, SOCK_DGRAM, SOL_UDP );
		$request 	= iconv("UTF-8", "ASCII", "HF-A11ASSISTHREAD");
		$read 		= [ $socket ];
		$write 		= $except = [ ];
		$response 	= '';
		$modules 	= IPS_GetInstanceListByModuleID('{5638FDC0-C110-WIFI-MAHO-201905120MOD}');
		$location	= $this->Translate("Wifi RGB/w Devices");
		$create 	= [
			["moduleID"=> "{5638FDC0-C110-WIFI-MAHO-201905120MHC}","configuration"=>["Identifier"=>""],"location"=>[$location]],
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
		if (! empty ( $BindIp )) socket_bind ( $socket, $BindIp );
		socket_set_option ( $socket, SOL_SOCKET, SO_BROADCAST, 1 );
		socket_sendto ( $socket, $request, strlen ( $request ),0,'255.255.255.255' ,48899);
		socket_set_option ( $socket, SOL_SOCKET, SO_RCVTIMEO, array ('sec' => $Timeout, 'usec' => '0') );
		while ( socket_select ( $read, $write, $except, $Timeout ) && $read ) {
			socket_recvfrom ( $socket, $response, 2048, null, $ip, $port );
			if(!empty($response)) {
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
				$info = $this->_device_info($address);
				$info_str = $this->_model_info_to_String($info);
				$name="$info_str - $name";
				$create[0]["configuration"]=[
					"Identifier"=>$identifier,
					"ProtocolType"=>$info['protocol_type'],
					"ModelType"=>$info['model_type'],
					"RequireSeparateRgbwBit"=>$info['rgbw_protocol'],
					"NeedCheckSum"=>$info['use_csum'],
					"Initialized"=>true
				];
				$create[1]["configuration"]["Host"]=$address;
				$myList[]=['address'=>$address,"name"=>"$name ($identifier)","instanceID"=>0, "create"=>$create];
				$saveList=true;
			}
		}
		socket_close ( $socket );
		if($saveList)$this->WriteAttributeString('DiscoverList', json_encode($myList));
		foreach($myList as $key=>$item){
			$identifier	= $item["create"][0]["configuration"]['Identifier'];
			$address	= $item["address"];
			$instanceID = isset($modules[$identifier]) ? $modules[$identifier] : 0;
			if(isset($modules[$address])){
				// Update Identifier for Manual created devices 
				if(empty($instanceID)){
					$instanceID=$modules[$address];
					IPS_SetProperty($instanceID, 'Identifier', $identifier);
					IPS_ApplyChanges($instanceID);
				}
			}
			if(!Sys_Ping($item['address'],1))$this->ChangeOfflineName(true,$myList[$key]['name']);
			$myList[$key]['instanceID']=$instanceID;			
		}
		
		return $myList;
	}
	private function ChangeOfflineName(bool $Offline, string &$Name){
		$msgOffline=' => [ '.$this->Translate('OFFLINE').' ]';
		$isOffline=stripos($Name,$msgOffline)!==false;
		if($Offline  && !$isOffline){
			$Name.=$msgOffline;
			return true;
		}elseif(!$Offline && $isOffline){
			$Name=str_ireplace($msgOffline,'',$Name);
			return true;
		}
		return false;
	}

	private const LEDENET_DEFAULT 	= 0;
	private const LEDENET_8BIT 		= 1;
	private const LEDENET_ORIGINAL 	= 2;

	private const LEDMODEL_WW		= 0;
	private const LEDMODEL_RGB		= 1;
	private const LEDMODEL_RGBW	= 2;
	private const LEDMODEL_RGBWW	= 3;

	private function _model_info_to_String(array $model_info){
   		$a=[];
   		$a[]=['Magic Home','LedNet 8bit','LedNet Original'][$model_info['protocol_type']];
   		$a[]=[$this->Translate('White only'),'RGB','RGB/w',$this->Translate('RGB/w with extra w channel')][$model_info['model_type']];
 		return join(' ',$a);     		
     }
     
	private function _determine_model(&$socket, $UseCheckSum=true){
		$bytearray_to_str=function(array $ByteArray, $UseCheckSum){
			$s=''; 
			if($UseCheckSum)$ByteArray[]=array_sum($ByteArray) & 0xFF;
			foreach($ByteArray as $c)$s.=chr($c);
			return $s;
		};
		$str_to_bytearray=function(string $String){
			$raw=array_map(function($c){return ord($c);},str_split($String));
			return $raw;
		};	
		$SendMsg=function(array $raw)use(&$socket,$UseCheckSum,$bytearray_to_str){
			$raw=$bytearray_to_str($raw, $UseCheckSum);
			$raw_len = strlen( $raw); 
			$send	 = @socket_send ( $socket, $raw, $raw_len,0);
			return $send == $raw_len;
		};
		$_query_len=0;
	    $protocol_type = self::LEDENET_DEFAULT;
	    $rgbw_capable = false;
	    $rgbw_protocol = false;
		$model_type = self::LEDMODEL_WW;
		$_use_csum=$UseCheckSum;
		if($SendMsg([0x81, 0x8a, 0x8b])){
			$rx='';
			if(@socket_recv($socket, $rx, 20,0)==14){
				$_query_len = 14;
				$raw=$str_to_bytearray($rx);
			}
		}
		if(!$_query_len && $SendMsg([0xef, 0x01, 0x77])){
			$rx='';
			if(@socket_recv($socket, $rx, 20,0)>0){
				$raw=$str_to_bytearray($rx);
				if ($raw[1] == 0x01){
	            	$protocol_type = self::LEDENET_ORIGINAL;
	            	$_use_csum = False;
	            	$_query_len = 11;
				}
	       	}
		}
		if($_query_len){
	        # Devices that don't require a separate rgb/w bit
	        $rgbw_protocol = ($raw[1] == 0x04 ||  $raw[1] == 0x33 || $raw[1] == 0x81);
 	   		//------------------------------------
	 		//- Detect Curent ProtocolType
	 		//------------------------------------
	        # Devices that use an 8-byte protocol
	        if ($raw[1] == 0x25 || $raw[1] == 0x27 || $raw[1] == 0x35) $protocol_type = self::LEDENET_8BIT;
	  		//------------------------------------
	 		//- Detect Model Type
	 		//------------------------------------
	        # Devices that actually support rgbw
	        $rgbw_capable  = ($raw[1] == 0x04 || $raw[1] == 0x25 || $raw[1] == 0x33 || $raw[1] == 0x81 || $raw[1] == 0x44);
			if(!$rgbw_capable)
				$model_type = self::LEDMODEL_RGB; 
			elseif($_query_len==11)
				$model_type = self::LEDMODEL_RGBW;
			elseif($_query_len==14){
				$model_type = $protocol_type == self::LEDENET_8BIT ? self::LEDMODEL_RGBWW :  self::LEDMODEL_RGBW;
			}
	        return [
	        	"protocol_type"=>$protocol_type,
	        	"model_type"=>$model_type,
	        	"rgbw_capable"=>$rgbw_capable,
	        	"rgbw_protocol"=>$rgbw_protocol,
	        	"use_csum"=>$_use_csum	
	        ];
		}
		else if($UseCheckSum){
			return _determine_query_len($socket,false);
		}
		return [];
	}
	
	
	private function _device_info(string $IP){
		$socket 	= socket_create ( AF_INET, SOCK_STREAM, SOL_TCP );
		socket_set_option ( $socket, SOL_SOCKET, SO_RCVTIMEO, array ('sec' => 0, 'usec' => '100000') );
		if(!socket_connect($socket, $IP,5577)){
			return false;
		}
		$r=$this->_determine_model($socket);
		socket_close ( $socket );
		return $r;
	}

}
?>