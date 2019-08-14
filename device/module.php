<?php
class WifiBulbControler extends IPSModule {
	################################################################
	#  Public Hidden Methods
	################################################################
    public function Create(){
    	parent::Create();
    	$this->RegisterPropertyString('Identifier', '');
    	$this->RegisterPropertyBoolean('WritePersistent',true);
    	$this->RegisterPropertyInteger('ShowColorMode',self::SHOW_COLOR_ONLY);
    	$this->RegisterPropertyBoolean('EnableTimerList',false);
    	$this->RegisterPropertyBoolean('AutoUpdateTimerList',false);
    	$this->RegisterPropertyBoolean('AutoRemoveExpiredTimers',false);
    	
    	$this->RegisterPropertyInteger('Protocol',0);
		$this->RegisterPropertyBoolean('RgbwCapable',false);
		$this->RegisterPropertyBoolean('RgbwProtocol',false);
		$this->RegisterPropertyBoolean('NeedCheckSum',true);
		// TimerList	
    	$this->RegisterAttributeString('TimerList', '');
    	$this->RegisterAttributeString('DeviceTime','');
    	$this->RegisterAttributeBoolean('TimerlistChanged', false);
		// Device Updates
    	$this->RegisterTimer('UpdateTimer',0,"IPS_RequestAction($this->InstanceID,'UPDATE',0);");
    	$this->CreateProfiles();
    	// Events
    	$this->RegisterMessage(0, IPS_KERNELMESSAGE);
    	if(IPS_GetKernelRunlevel() == KR_READY){
			$this->RegisterMessage($this->InstanceID,FM_CONNECT);
			$this->RegisterMessage($this->InstanceID,FM_DISCONNECT);
    	}
    	// Default Values
		$this->SetValue('POWER', false);
		$this->SetValue('COLOR', 0);
		$this->SetValue('BRIGHTNESS', 0);
		$this->SetValue('MODE', 0);
		$this->SetValue('SPEED', 100);
		// Connect
		$this->ConnectParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');
    }
   	public function MessageSink($TimeStamp, $SenderID, $Message, $Data){
    	if($Message==KR_READY){
    		$this->StartTimer(random_int(2000,4000));
			$this->RegisterMessage($this->InstanceID,FM_CONNECT);
			$this->RegisterMessage($this->InstanceID,FM_DISCONNECT);
    	}
    	elseif($Message==IM_CHANGESTATUS){
   			$this->SendDebug(__FUNCTION__,"Parent status: ".$Data[0],0);
   			$this->UpdateOfflineName($Data[0]!=102);
   			if($Data[0]==102)$this->StartTimer(1000);
    	}
    	elseif($Message==FM_CONNECT){
    		$this->SetBuffer('LastConnectionID',$Data[0]);
			$this->RegisterMessage($Data[0],IM_CHANGESTATUS);
			$this->SendDebug(__FUNCTION__,"Connect: ".$Data[0],0);
			$this->UpdateOfflineName(!$this->Ready());
    		$this->StartTimer(1000);
    	}
    	elseif($Message==FM_DISCONNECT){
    		if($ID=intval($this->GetBuffer('LastConnectionID'))){
				$this->UnRegisterMessage($ID,IM_CHANGESTATUS);
    			$this->SendDebug(__FUNCTION__,"DisConnect: ".$ID,0);
    		}
    		$this->_UpdateOfflineName(true);	
    		$this->SetBuffer('LastConnectionID',0);
    	}
    }
    public function ApplyChanges(){
    	parent::ApplyChanges();
		$show = $this->ReadPropertyInteger('ShowColorMode');
		$rgb  = @$this->GetIDForIdent('COLOR')? colorsys::int_to_rgb($this->GetValue('COLOR')):
    			[(int)@$this->GetValue('RED'),(int)@$this->GetValue('GREEN'),(int)@$this->GetValue('BLUE')];
		if($show==self::SHOW_RGB_ONLY){
			$this->UnregisterVariable('COLOR');
			if(!@$this->GetIDForIdent('RED'))$this->SetValue('RED',$rgb[0]);
			if(!@$this->GetIDForIdent('GREEN'))$this->SetValue('GREEN', $rgb[1]);
		    if(!@$this->GetIDForIdent('BLUE'))$this->SetValue('BLUE', $rgb[2]);
		}
		else if($show==self::SHOW_COLOR_ONLY){
			@$this->UnregisterVariable('RED');
			@$this->UnregisterVariable('GREEN');
			@$this->UnregisterVariable('BLUE');
			if(!@$this->GetIDForIdent('COLOR'))$this->SetValue('COLOR', colorsys::rgb_to_int($rgb[0], $rgb[1],$rgb[2]));
		}
		else {
			if(!@$this->GetIDForIdent('COLOR'))$this->SetValue('COLOR', colorsys::rgb_to_int($rgb[0], $rgb[1],$rgb[2]));
			if(!@$this->GetIDForIdent('RED'))$this->SetValue('RED',$rgb[0]);
			if(!@$this->GetIDForIdent('GREEN'))$this->SetValue('GREEN', $rgb[1]);
		    if(!@$this->GetIDForIdent('BLUE'))$this->SetValue('BLUE', $rgb[2]);
		}
    	if(IPS_GetKernelRunlevel() == KR_READY){
	     	$this->StartTimer(500);
     	}
    }
 	public function RequestAction($Ident, $Value){
		switch($Ident){
			case 'POWER'	 : $this->SetPower((bool)$Value); break;
    		case 'COLOR'	 : $this->SetColor((int)$Value); break;
    		case 'RED'		 : $this->SetRed((int)$Value);break;
    		case 'GREEN'	 : $this->SetGreen((int)$Value);break;
    		case 'BLUE'		 : $this->SetBlue((int)$Value);break;;
     		case 'BRIGHTNESS': $this->SetBrightness((int)$Value,true);break;
    		case 'WARM_WHITE': $this->SetWhite((int)$Value,true); break;
    		case 'COLD_WHITE': $this->SetColdWhite((int)$Value,true); break;
    		case 'MODE' 	 : $this->RunProgram($Value, $this->GetSpeed());	break;
    		case 'UPDATE'	 : 
    			if(!$this->Ready())break;
    			if($Value<2)$this->SendRequest();
    			elseif($Value==2)$this->SendRequest(self::REQUEST_TIME);
    			elseif($Value==3)$this->SendRequest(self::REQUEST_TIMERS);
    			break;
    		case 'SAVE_TIMER' : $this->SetTimerConfig($Value);	break;
    		case 'SAVE_TIMERLIST' : $this->SendTimerList(); break;
    		default: IPS_LogMessage(IPS_GetName( $this->InstanceID),"Unknown Action \"$Ident\" received! $Value: $Value");	
		}
	}
	public function ReceiveData($JSONString){
    	$this->StopTimer();
		$data=json_decode($JSONString);
		$data->Buffer=utf8_decode($data->Buffer);
		$this->SendDebug(__FUNCTION__, $data->Buffer,1);
		$raw	= array_map(function($c){return ord($c);},str_split($data->Buffer));
    	$rawLen	= count($raw);	
   		$checkOk= true;
   		$error	= null;
   		if($this->ReadPropertyBoolean('NeedCheckSum')){
    		$checkOk = $raw[$rawLen-1] = (array_sum($raw) & 0xFF);
    	}
    	if($rawLen==3||$rawLen==4){ // Switch State Response
    		if($checkOk){
    			$this->ProcessRawSwitch($raw);
    		} else $error="Checksum Error received for Switch State response!";
    	}
    	elseif($rawLen == 12) {  // Clock Response
    		if($checkOk){
    			$this->ProcessRawTime($raw);
    		} else $error="Checksum Error received for Timer response!";
    	}
    	elseif($rawLen==88){ // Timerlist Response
    		if($checkOk){
    			$this->ProcessRawTimers($raw);
    		} else $error="Checksum Error received for Timerlist response!";
    	}
    	elseif($rawLen==11 || $rawLen==14){
    		if($checkOk){
    			
    			$this->ProcessRawState($raw);
//     			$this->_SendACK();
    		} else $error="Checksum Error received for Data response!";
    	}
    	else {
    		$error = "Unknown Data received!";
    	}
    	if($error){
    		IPS_LogMessage(IPS_GetName($this->InstanceID), $this->Translate($error));
    	}
    	$this->StartTimer();
	}
    public function GetConfigurationForm(){
    	$f=json_decode(file_get_contents(__DIR__.'/form.json'));
		$time=$this->ReadAttributeString('DeviceTime');
		$f->actions[1]->items[0]->caption=$time?$this->Translate('Device time').': '.$time : $this->Translate("Device time is loading...");
    	$options = self::REQUEST_TIME;
		if($this->ReadPropertyBoolean('EnableTimerList')){
			if($this->ReadPropertyBoolean('AutoUpdateTimerList'))$options|=self::REQUEST_TIMERS;
			else $options|= self::REQUEST_FORMUPDATE;
			$f->actions[0]->items[0]->values=$this->LoadTimerList();
    		$i=$f->actions[0]->items[1]->items;
    		$i[count($i)-1]->visible=$this->ReadAttributeBoolean('TimerlistChanged');
    	}
    	else {
    		array_shift($f->actions);
    	}
    	$this->StartTimer(2000,$options); // Give IPS Time to Load Form before update TimerList;
    	return json_encode($f);
    }
    public function Destroy(){
    	parent::Destroy();
    	if(count(IPS_GetInstanceListByModuleID("{5638FDC0-C110-WIFI-MAHO-201905120WBC}"))==0){
    		@IPS_DeleteVariableProfile('Presets.MHC');
    	}
    }
    ################################################################
    #  Public Dynamic Form Methods
	################################################################
 	public function SetFormField(string $Field, string $Arguments, $Value){
		$this->SendDebug(__FUNCTION__,"Field:$Field Args:$Arguments => ".json_encode($Value),0);
		$data=json_decode($this->GetBuffer('CacheTimer'));
		if(is_null($data)){
			$data=new stdClass();
			$data->id=-1;
			$data->raw=array_fill(0,14,0);
		}
		$id_changed = false;		
		if($Field == 'TimerID' && $Arguments=='value'){
    		if($data->id!=$Value){
    			$id_changed=true;
    			// here insert Save CacheTimer to timerliest
    			$timer_list=$this->LoadTimerList();
    			$data->raw= $timer_list[$Value]->data;
	    		$data->id=$Value;
    		}
		}
		elseif($Field == 'Active'){
			$data->raw[0] = $Value ? 0xf0 : 0;
		}
		elseif($Field == 'Power'){
			$data->raw[13] = $Value ? 0xf0 : 0;
		}
		elseif($Field == 'Mode'){
			$data->raw[8] = $Value;
		}
		elseif($Field=="Mo"||$Field=="Th"||$Field=="We"||$Field=="Tu"||$Field=="Fr"||$Field=="Sa"||$Field=="Su"){
			$bit = self::BuilInDayMask[$Field];
			if($Value)$data->raw[7]|=$bit;
			else $data->raw[7]^=$bit;
		}
		elseif($Field=='Color'){
			list($data->raw[9],$data->raw[10],$data->raw[11]) = colorsys::int_to_rgb($Value);
		}
    	$repeat_mask=0;
		$is_active=$data->raw[0] == 0xf0;
    	$is_on =  $data->raw[13] == 0xf0;
    	$is_repats = false;
    	$is_ww=false;
    	$is_color=false;
    	$is_preset=false;
    	$is_sun=false;
    	if($is_active){
    		$repeat_mask=$data->raw[7];
    		$is_repats = $repeat_mask > 0;
			$pattern_code= $data->raw[8];
			if($pattern_code==0xFE)
				$is_ww=true;
			elseif($pattern_code==0x61||$pattern_code==0x62)
				$is_color=true;
			elseif(array_search($pattern_code,self::BuildInPresets))
				$is_preset=true;					
			else if(array_search($pattern_code, self::BuildInTimer))
				$is_sun=true;
			else $pattern_code=0;
    	}
    	$date_visible 	= $is_active && !$is_repats && ($is_color || $is_preset || !$is_sun);
    	$time_visible 	= $is_active && !$is_sun;
    	$repeat_visible = $is_active && !$is_sun;
    	$mode_visible 	= $is_active && ($is_on || $is_sun);
    	$color_visible 	= $is_active && $is_on && !$is_sun && !$is_preset && !$is_ww ;
    	$speed_visible 	= $is_active && $is_on && $is_preset;
    	$sun_visible 	= $is_active && $is_sun && $is_on;
		$ww_visible 	= $is_active && $is_ww;
		
    	$this->UpdateFormField('Power', 'visible', $is_active);
    	$this->UpdateFormField('Mode', 'visible', $mode_visible);
    	$this->UpdateFormField('Date', 'visible', $date_visible);
    	$this->UpdateFormField('Time', 'visible', $time_visible);
		foreach(self::BuilInDayMask as $k=>$bit){
		   	$this->UpdateFormField($k, 'visible', $repeat_visible);
		   	if($id_changed && $repeat_visible)$this->UpdateFormField($k, 'value', (bool)($repeat_mask & $bit));
		}
		$this->UpdateFormField('Color', 'visible', $color_visible);
		$this->UpdateFormField('WW', 'visible', $ww_visible);
		$this->UpdateFormField('Speed', 'visible', $speed_visible);
		$this->UpdateFormField('Duration', 'visible', $sun_visible);
		$this->UpdateFormField('WWStart', 'visible', $sun_visible);
		$this->UpdateFormField('WWEnd', 'visible', $sun_visible);

		if($id_changed){
			$this->UpdateFormField('Active','value',$is_active);
			$this->UpdateFormField('Power','value',$is_on);
			if($color_visible){
				$color=colorsys::rgb_to_int($data->raw[9],$data->raw[10],$data->raw[11]);
				$this->UpdateFormField('Color','value',$color);
			}
			elseif($is_preset){
				$this->UpdateFormField('Speed', 'value',$data->raw[9]); 
			}
			elseif($is_sun){
	        	$this->UpdateFormField('Duration', 'value', $data->raw[9]);
	        	$this->UpdateFormField('WWStart', 'value', $data->raw[10]);
	        	$this->UpdateFormField('WWEnd', 'value', $data->raw[11]);
			}
			elseif($is_ww){
				$this->UpdateFormField('WW', 'value', $data->raw[12]);
			}
 		}
		$this->SetBuffer('CacheTimer',json_encode($data));
	}
	################################################################
    #  Public Module Methods
	################################################################
	public function RequestUpdate(){
		return $this->Ready() && $this->SendRequest();
 	}
 	public function SetPower(bool $PowerOn){
 		if(!$this->Ready()){
 			return false;
 		}
 		if ($this->ReadPropertyInteger('Protocol') == self::PROTOCOL_LEDENET_ORIGINAL){
            $msg_on =  [0xcc, 0x23, 0x33];
            $msg_off = [0xcc, 0x24, 0x33];
    	}else{
            $msg_on = [0x71, 0x23, 0x0f];
            $msg_off = [0x71, 0x24, 0x0f];
    	}
        $msg= $PowerOn ? $msg_on : $msg_off;
        if($ok=$this->SendData($msg)){
        	$this->UpdatePower($PowerOn);
        }
 		return $ok;
  	}
  	public function SetColor(int $Color){
  		$rgb=colorsys::int_to_rgb($Color);
  		return $this->Ready() && $this->SendRGBW($rgb[0], $rgb[1],$rgb[2]);
  	}
  	public function SetRGBW(int $Red, int $Green, int $Blue, int $White = -1){
  		return $this->Ready() && $this->SendRGBW($Red, $Green, $Blue, $White<0 ? null : $White);
  	}
  	public function SetRed(int $Level255){
  		return $this->Ready() && $this->SendRGBW($Level255, null,null);
  	}
  	public function SetGreen(int $Level255){
  		return $this->Ready() && $this->SendRGBW(null, $Level255,null);
  	}
  	public function SetBlue(int $Level255){
  		return $this->Ready() && $this->SendRGBW(null, null,$Level255);
  	}
  	public function SetBrightness(int $Level255){
  		return $this->Ready() && $this->SendRGBW(null, null, null, null,$Level255);
  	}
  	public function SetWhite(int $Level255){
  		return $this->Ready() && $this->SendRGBW(null, null, null, $Level255);
  	}
  	public function SetColdWhite(int $Level255){
  		return $this->Ready() && $this->SendRGBW(null, null, null, null,null,$Level255);
  	}
	public function RunProgram(int $ProgramID, int $Speed100){
		if(!$this->Ready()){
			return false;
		}
  		if ($ok=$ProgramID >= 0x25 && $ProgramID <= 0x38){
	   		if(!$this->GetPower()){
	   			$this->SetPower(true);
	   			IPS_Sleep(200);
	   		}
  			$delay	= utils::speedToDelay($Speed100);
			$msg 	= [0x61,$ProgramID,$delay,0x0f];
  			$ok		= $this->SendData($msg);
			if($ok){
				$this->UpdateLedMode(self::LEDMODE_PRESET);
				$this->SetValue('MODE', $ProgramID);
			}
  		} 
  		else 
  		{
  			$ok=$this->SendRGBW(null, null, null);
  		}
		return $ok;
	}
	################################################################
    #  Protected Override
	################################################################
    protected function SetValue($Ident, $Value){
    	if(!($id=@$this->GetIDForIdent($Ident))){
    		switch($Ident){
    			case 'POWER'	 : $id=$this->RegisterVariableBoolean($Ident, $this->Translate('Power'), 		'~Switch', 0);break;
    			case 'COLOR'	 : $id=$this->RegisterVariableInteger($Ident, $this->Translate('Color'), 		'~HexColor', 1);break;
    			case 'RED'		 : $id=$this->RegisterVariableInteger($Ident, $this->Translate('Red'), 			'~Intensity.255', 2);break;
    			case 'GREEN'	 : $id=$this->RegisterVariableInteger($Ident, $this->Translate('Green'), 		'~Intensity.255', 3);break;
    			case 'BLUE'		 : $id=$this->RegisterVariableInteger($Ident, $this->Translate('Blue'), 		'~Intensity.255', 4);break;
    			case 'BRIGHTNESS': $id=$this->RegisterVariableInteger($Ident, $this->Translate('Brightness'), 	'~Intensity.255', 5);break;
    			case 'WARM_WHITE': $id=$this->RegisterVariableInteger($Ident, $this->Translate('White'), 		'~Intensity.255', 6);break;
    			case 'COLD_WHITE': $id=$this->RegisterVariableInteger($Ident, $this->Translate('Coldwhite'), 	'~Intensity.255', 7);break;
    			case 'SPEED' 	 : $id=$this->RegisterVariableInteger($Ident, $this->Translate('Speed'), 		'~Intensity.100', 8);break;
    			case 'MODE' 	 : $id=$this->RegisterVariableInteger($Ident, $this->Translate('Mode'),			 'Presets.MHC', 9);break;
    		}
    		if($id)$this->EnableAction($Ident);
    	}
    	if($id)SetValue($id, $Value);
    }
    ################################################################
    #  Setting Modes 
	################################################################
    private function GetPower(){
    	$power=(@$id=$this->GetIDForIdent('POWER'))?GetValue($id):null;
    	if(is_null($power))$this->SetValue('POWER', $power=false);
      	return $power;
    }
    private function GetSpeed(){
    	$speed=(@$id=$this->GetIDForIdent('SPEED'))?GetValue($id):null;
    	if(is_null($speed))$this->SetValue('SPEED', $speed=100);
      	return $speed;
    }
    private function GetWW(){
    	$ww=(@$id=$this->GetIDForIdent('WARM_WHITE'))?GetValue($id):null;
    	if(is_null($ww))$this->SetValue('WARM_WHITE', $ww=0);
      	return $ww;
    }
    private function GetRGB(){
    	if($this->ReadPropertyInteger('ShowColorMode')==self::SHOW_RGB_ONLY){
    		$r=($id=@$this->GetIDForIdent('RED'))?GetValue($id):null;
    		if(is_null($r))$this->SetValue('RED',$r=0);
    		$g=($id=@$this->GetIDForIdent('GREEN'))?GetValue($id):null;
    		if(is_null($g))$this->SetValue('GREEN',$g=0);
    		$b=($id=@$this->GetIDForIdent('BLUE'))?GetValue($id):null;
    		if(is_null($b))$this->SetValue('BLUE',$b=0);
    		return [$r,$g,$b];
    	}
    	$color=($id=@$this->GetIDForIdent('COLOR'))?GetValue($id):null;
    	if(is_null($color))$this->SetValue('COLOR', $color=0);
    	return colorsys::int_to_rgb($color);
    }
    
    private function UpdateLedMode(int $LedMode){
    	if(intval($this->GetBuffer('LastLedMode'))!=$LedMode){
	    	$no_preset=$LedMode!=self::LEDMODE_PRESET;
	    	@IPS_SetDisabled($this->GetIDForIdent('SPEED'), $no_preset);
	    	if($id=@$this->GetIDForIdent('COLOR'))		IPS_SetDisabled($id, !$no_preset);
	    	if($id=@$this->GetIDForIdent('BRIGHTNESS'))	IPS_SetDisabled($id, !$no_preset);
	    	if($id=@$this->GetIDForIdent('WARM_WHITE'))	IPS_SetDisabled($id, !$no_preset);
	    	if($id=@$this->GetIDForIdent('COLD_WHITE'))	IPS_SetDisabled($id, !$no_preset);
	    	if($no_preset){
	     		$this->SetValue('MODE', $LedMode=0);
	    	} 
	    	$this->SendDebug(__FUNCTION__, $LedMode, 0);
    		$this->SetBuffer('LastLedMode',$LedMode);
    	}
    }    
    private function UpdatePower(bool $Power, bool $Force=false){
    	if($Force || $this->GetPower()!=$Power){
// 	    	foreach(['COLOR','RED','GREEN','BLUE','BRIGHTNESS','WARM_WHITE','COLD_WHITE','SPEED','MODE'] as $ident){
// 	    		if($id=@$this->GetIDForIdent($ident))IPS_SetDisabled($id, !$Power);
// 	    	}
	    	$this->SetValue('POWER',$Power);
    	}
    }
    ################################################################
    #  Send RGBW Color 
	################################################################
   	private function UpdateColor($r,$g,$b, $CalcBrightness=true){
 		$this->SendDebug(__FUNCTION__,"R:$r G:$g B:$g",0);
		$show = $this->ReadPropertyInteger('ShowColorMode');
		if($show==self::SHOW_RGB_ONLY||$show==self::SHOW_COLOR_RGB){
			if($show==self::SHOW_RGB_ONLY)@$this->UnregisterVariable('COLOR');
			else $this->SetValue('COLOR', colorsys::rgb_to_int($r, $g, $b));
			$this->SetValue('RED', $r);
			$this->SetValue('GREEN', $g);
		    $this->SetValue('BLUE', $b);
		}
		else {
			if(@$this->GetIDForIdent('RED')){
				$this->UnregisterVariable('RED');
				@$this->UnregisterVariable('GREEN');
				@$this->UnregisterVariable('BLUE');
			}
			$this->SetValue('COLOR', colorsys::rgb_to_int($r, $g, $b));
		}
 		if($CalcBrightness)$this->SetValue('BRIGHTNESS',max([$r, $g, $b]));
    }
   	private function SendRGBW($Red , $Green, $Blue, $White=null, $Brightness=null, $ColdWhite=null){
   		$rgbw_capable=$this->ReadPropertyBoolean('RgbwCapable');
   		if (($Red || $Green || $Blue) && ($White || $ColdWhite) && !$rgbw_capable){
        	IPS_LogMessage(IPS_GetName($this->InstanceID),$this->Translate("RGBW command sent to non-RGBW device"));
        	return false;
   		}
   		if(!$this->GetPower()){
   			$this->SetPower(true);
   			IPS_Sleep(200);
   		}
   		if(is_null($Red)|| is_null($Green) || is_null($Blue)){
   			$rgb=$this->GetRGB();
 			if(is_null($Red))$Red=$rgb[0];
   			if(is_null($Green))$Green=$rgb[1];
   			if(is_null($Blue))$Blue=$rgb[2];
   		}
   		
   		if(is_null($White)&& $rgbw_capable)$White=$this->GetWW();
   		if(!is_null($White))$White=(int)$White;
   		if(!is_null($ColdWhite))$ColdWhite=(int)$ColdWhite;
   		$Red=(int)$Red;$Green=(int)$Green;$Blue=(int)$Blue;
		
   		# sample message for original LEDENET protocol (w/o checksum at end)
        #  0  1  2  3  4
        # 56 90 fa 77 aa
        #  |  |  |  |  |
        #  |  |  |  |  terminator
        #  |  |  |  blue
        #  |  |  green
        #  |  red
        #  head

        
        # sample message for 8-byte protocols (w/ checksum at end)
        #  0  1  2  3  4  5  6
        # 31 90 fa 77 00 00 0f
        #  |  |  |  |  |  |  |
        #  |  |  |  |  |  |  terminator
        #  |  |  |  |  |  write mask / white2 (see below)
        #  |  |  |  |  white
        #  |  |  |  blue
        #  |  |  green
        #  |  red
        #  persistence (31 for true / 41 for false)
        #
        # byte 5 can have different values depending on the type
        # of device:
        # For devices that support 2 types of white value (warm and cold
        # white) this value is the cold white value. These use the LEDENET
        # protocol. If a second value is not given, reuse the first white value.
        #
        # For devices that cannot set both rbg and white values at the same time
        # (including devices that only support white) this value
        # specifies if this command is to set white value (0f) or the rgb
        # value (f0). 
        #
        # For all other rgb and rgbw devices, the value is 00

        # sample message for 9-byte LEDENET protocol (w/ checksum at end)
        #  0  1  2  3  4  5  6  7
        # 31 bc c1 ff 00 00 f0 0f
        #  |  |  |  |  |  |  |  |
        #  |  |  |  |  |  |  |  terminator
        #  |  |  |  |  |  |  write mode (f0 colors, 0f whites, 00 colors & whites)
        #  |  |  |  |  |  cold white
        #  |  |  |  |  warm white
        #  |  |  |  blue
        #  |  |  green
        #  |  red
        #  persistence (31 for true / 41 for false)
        #

   		if ($Brightness != null){
        	list($Red,$Green,$Blue) = colorsys::calculate_brightness($Red, $Green, $Blue, $Brightness);
        	$this->UpdateColor($Red, $Green, $Blue,false);
   		}
 
 		# Buildig Raw Message
 		$protocol=$this->ReadPropertyInteger('Protocol');
   		
 		# The original LEDENET protocol
        if ($protocol == self::PROTOCOL_LEDENET_ORIGINAL){
            $msg = [0x56,$Red,$Green,$Blue,0xaa];
        }
        else 
        {
            # all other devices
            #assemble the message
            $msg= [
           		$this->ReadPropertyBoolean('WritePersistent')?0x31 : 0x41,
           		$Red,$Green,$Blue,is_null($White) ? 0 : $White
            ];

			if ($protocol == self::PROTOCOL_LEDENET) {
                # LEDENET devices support two white outputs for cold and warm. We set
                # the second one here - if we're only setting a single white value,
                # we set the second output to be the same as the first
                if (!is_null($ColdWhite))
                    $msg[]=(int)$ColdWhite;
                elseif (!is_null($White)) 
                	$msg[]=(int)$White;
                else
                    $msg[]=0;
			}
            # write mask, default to writing color and whites simultaneously
            $write_mask = 0x00;
            # rgbwprotocol devices always overwrite both color & whites
            if (!$this->ReadPropertyBoolean('RgbwProtocol')){
                if (is_null($White) && is_null($ColdWhite))
                    # Mask out whites
                    $write_mask |= 0xf0;
                elseif (is_null($Red) && is_null($Green) && is_null($Blue))
                    # Mask out colors
                    $write_mask |= 0x0f;
            }
            $msg[]=$write_mask;

            # Message terminator
            $msg[]=0x0f;
        }
        # send the message
        $ok=$this->SendData($msg);
        
        if($ok){
        	$this->UpdateLedMode(self::LEDMODE_COLOR);
        	$this->UpdateColor($Red, $Green, $Blue);
        	if($rgbw_capable){
        		if(!is_null($White)){
        			$this->SetValue('WARM_WHITE', $White);
        		}
        		if (!is_null($ColdWhite)){
        			$this->SetValue('COLD_WHITE', $ColdWhite);
        		}
        	}
        }
        return $ok;
    }
    ################################################################
    #  Data Send / Receive
	################################################################
	private function SendData(array $RawData){
		if($this->ReadPropertyBoolean('NeedCheckSum'))$RawData[]= array_sum($RawData) & 0xFF;
 		$tx=join(array_map(function($b){return chr($b);},$RawData));
		$this->SendDebug(__FUNCTION__,$tx,1);
		$send=[
			'DataID'=>'{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}',
			'Buffer'=>utf8_encode($tx)
		];
		
 		$r=$this->SendDataToParent(json_encode($send));
		$this->StartTimer(); // Resetting Timer
 		return $r=="";
	}
	private function SendRequest(int $Options=0){
		$sl=0;$ok=true;
		$Options|=intval($this->GetBuffer('RequestOptions'));
		$this->SetBuffer('RequestOptions',0);
		if($ok && ($Options==0 || $Options&self::REQUEST_STATE)){
			$ok=$this->SendData($this->ReadPropertyInteger('Protocol')==self::PROTOCOL_LEDENET_ORIGINAL?[0xef, 0x01, 0x77]:[0x81, 0x8a, 0x8b]);
			$sl=200;
		}
		if($ok && ($Options&self::REQUEST_TIME)){
			if($sl)IPS_Sleep($sl);
			$ok=$this->SendData([0x11, 0x1a, 0x1b, 0x0f]);
			$sl=200;
		}
		if($ok && ($Options&self::REQUEST_TIMERS)){
			if($sl)IPS_Sleep($sl);
			$this->UpdateFormField('UpdateTimerListBtn', 'enabled', false);
			$ok=$this->SendData([0x22, 0x2a, 0x2b, 0x0f]);
		}
		elseif($ok && ($Options & self::REQUEST_FORMUPDATE)){
			$this->SetBuffer('CacheTimer','');
			IPS_Sleep(1000);
			$this->SetFormField('TimerID', 'value', 0);
		}
		return $ok;
	}
   	private function ProcessRawSwitch(array $raw){
		# response from a 5-channel:
      	#pos  0  1  2  3 
	    #    F0 71 23 84 
	    #     |  |  |  |
	    #     |  |  |  checksum
		#     |  |  off(24)/on(23)
		#     |  type
		#     msg head
		// arbeitet nicht richtig ?? der on/off state wird nicht immer richtig zurückgeliefert
    	# Normalerweise sollte unter 2 (23/24) kommen für an/aus 
    	# aber es kommt fast immer 24
    	
    	return true;
    }
   	private function ProcessRawState(array $raw){
        # typical response:
        #pos  0  1  2  3  4  5  6  7  8  9 10
        #    66 01 24 39 21 0a ff 00 00 01 99
        #     |  |  |  |  |  |  |  |  |  |  |
        #     |  |  |  |  |  |  |  |  |  |  checksum
        #     |  |  |  |  |  |  |  |  |  warmwhite
        #     |  |  |  |  |  |  |  |  blue
        #     |  |  |  |  |  |  |  green 
        #     |  |  |  |  |  |  red
        #     |  |  |  |  |  speed: 0f = highest f0 is lowest
        #     |  |  |  |  <don't know yet>
        #     |  |  |  preset pattern             
        #     |  |  off(23)/on(24)
        #     |  type
        #     msg head
        #        

        # response from a 5-channel LEDENET controller:
        #pos  0  1  2  3  4  5  6  7  8  9 10 11 12 13
        #    81 25 23 61 21 06 38 05 06 f9 01 00 0f 9d
        #     |  |  |  |  |  |  |  |  |  |  |  |  |  |
        #     |  |  |  |  |  |  |  |  |  |  |  |  |  checksum
        #     |  |  |  |  |  |  |  |  |  |  |  |  color mode (f0 colors were set, 0f whites, 00 all were set)
        #     |  |  |  |  |  |  |  |  |  |  |  cold-white
        #     |  |  |  |  |  |  |  |  |  |  <don't know yet>
        #     |  |  |  |  |  |  |  |  |  warmwhite
        #     |  |  |  |  |  |  |  |  blue
        #     |  |  |  |  |  |  |  green
        #     |  |  |  |  |  |  red
        #     |  |  |  |  |  speed: 0f = highest f0 is lowest
        #     |  |  |  |  <don't know yet>
        #     |  |  |  preset pattern
        #     |  |  off(23)/on(24)
        #     |  type
        #     msg head
        #
        //------------------------------------
 		//- Detect Device Mode
 		//------------------------------------
        # Devices that actually support rgbw
      	$rgbw_capable = ($raw[1] == 0x04 || $raw[1] == 0x25 || $raw[1] == 0x33 || $raw[1] == 0x81 || $raw[1] == 0x44);
    	$pattern_code = $raw[3];
        $ww_level = $raw[9];
        $led_mode = self::LEDMODE_MANUAL;
       	//$mode = "unknown";
        if ($pattern_code== 0x61 || $pattern_code== 0x62){
        	if ($rgbw_capable)
                $led_mode = self::LEDMODE_COLOR;
            elseif ($ww_level != 0)
                $led_mode = self::LEDMODE_WW;
            else
                $led_mode = self::LEDMODE_COLOR;
        }
        elseif ($pattern_code == 0x60)
            $led_mode = self::LEDMODE_CUSTOM;
        elseif ($pattern_code == 0x41)
        	$led_mode = self::LEDMODE_COLOR;
        elseif (array_search($pattern_code,self::BuildInPresets))
            $led_mode = self::LEDMODE_PRESET;
        elseif (array_search($pattern_code, self::BuildInTimer))
            $led_mode =  $pattern_code==0xA1 ? self::LEDMODE_SUNRISE :  self::LEDMODE_SUNSET;
        //------------------------------------
 		//- Update Values
 		//------------------------------------
       	$this->UpdateLedMode($led_mode);
           
      	if ($raw[2] == 0x23)
            $this->UpdatePower(true);
        elseif ($raw[2] == 0x24)
            $this->UpdatePower(false);
   		
            
        if($led_mode == self::LEDMODE_COLOR  ){
	 		$this->UpdateColor($raw[6],$raw[7],$raw[8]);
			if($rgbw_capable){
 				$this->SetValue('WARM_WHITE', $raw[9]);
 				if ($this->ReadPropertyInteger('Protocol') == self::PROTOCOL_LEDENET)$this->SetValue('COLD_WHITE', $raw[11]);
 			}        	
        }
        elseif($led_mode==self::LEDMODE_PRESET){
        	$this->SetValue('MODE',$pattern_code);
        	$this->SetValue('SPEED', utils::delayToSpeed($raw[5]));
        }else {
        	$this->SetValue('MODE',0);
        }
 
  		return true;
    }
   	private function ProcessRawTime(array $raw){
    	#pos 0  1  2  3  4  5  6  7  8  9 10 11
		#	0F 11 14 13 08 0A 01 23 1A 06 00 9D
        #    |  |  |  |  |  |  |  |  |  |  |  |
        #    |  |  |  |  |  |  |  |  |  |  |  checksum
        #    |  |  |  |  |  |  |  |  |  |  <don't know yet>
        #    |  |  |  |  |  |  |  |  |  day of week
        #    |  |  |  |  |  |  |  |  second
        #    |  |  |  |  |  |  |  minute 
        #    |  |  |  |  |  |  hour
        #    |  |  |  |  |  date
        #    |  |  |  |  month
        #    |  |  |  year + 2000             
        #    |  |  <don't know yet>
        #    |  type
        #    msg head
    	
		$year 	= $raw[3] + 2000;
		$month 	= $raw[4];
		$day 	= $raw[5];
		$hour 	= $raw[6];
		$minute = $raw[7];
		$second = $raw[8];
		#dayofweek = rx[9]
		$dt = new DateTime();
		$dt->setDate($year, $month, $day);
		$dt->setTime($hour, $minute,$second);
		$time = $dt->getTimestamp();
		$this->WriteAttributeString('DeviceTime', $time=date($this->Translate('Y-d-m H:i').':s',$time ));
       	$this->UpdateFormField('DeviceTime', 'caption',$this->Translate('Device time').': '.$time);
//       	if(!$this->ReadPropertyBoolean('AutoUpdateTimerList'))$this->SetFormField('TimerID', 'value', 0);
		return true;
    }
   	private function ProcessRawTimers(array $raw){
    	$start = 2;
		$timer_list = [];
		for($i=0;$i<6;$i++){
			$timer_bytes = array_slice($raw,$start,14);
			$item=[
				"id"=>$i+1,
				"info"=>$this->GetTimerInfo($timer_bytes),
				"data"=>$timer_bytes,
				"expired"=>$this->IsTimerExpired($timer_bytes)
			];
			if($item["expired"])$item["rowColor"]=self::COLOR_EXPIRED;
			elseif($timer_bytes[0]!== 0xf0)$item["rowColor"]=self::COLOR_EMPTY;
			else $item["rowColor"]=self::COLOR_ACTIVE;
			$timer_list[]=$item;
			$start += 14;
    	}
    	$this->WriteAttributeBoolean('TimerlistChanged',false);
    	$this->SaveTimerList($timer_list);	 
		$this->UpdateFormField('UpdateTimerListBtn', 'enabled', true);
   	}
    ################################################################
    #  Device Timer
	################################################################
    private function SendDeviveClock(){
        $msg = [0x10, 0x14,date('Y')-2000,data('m'),date('d'),date('H'),date('i'),date('s'),date('N'),0x00,0x0f];
        $this->SendData($msg);
    }
	private function LoadTimerList(){
		$data = $this->ReadAttributeString('TimerList');
		if(empty($data)){
			$data=[];
			for($j=0;$j<6;$j++)$data[]=['id'=>$j+1,'info'=>$this->Translate('Timer not defined'),'data'=>array_fill(0,14,0)];
			$data = json_encode($data);
			$this->WriteAttributeString('TimerList', $data);
		}
		return json_decode($data);
	}
	private function SaveTimerList(array $Timerlist){
    	$this->WriteAttributeString('TimerList', $timer_list=json_encode($Timerlist));
		$this->UpdateFormField('TimerList', 'values', $timer_list);	 
  		$this->SetFormField('TimerID', 'value', 0);
	}
    private function SendTimerList(){
        # remove inactive or expired timers from list
        $timer_list=$this->LoadTimerList();
        if($this->ReadPropertyBoolean('AutoRemoveExpiredTimers')){
	        $change=false;
        	foreach($timer_list as $id=>$item){
	        	if($item->expired){
					$this->SendDebug(__FUNCTION__,'Remove expired Timer: '.$item->info,0);
	        		unset($timer_list[$id]);
					$change=true;
	        	}
	        }
        	if($change)$timer_list=array_values($timer_list);
        }
        
        # truncate if more than 6
        if (count($timer_list) > 6){
            IPS_LogMessage(IPS_GetName($this->InstanceID),$this->Translate("Too many timers, truncating list"));
            $timer_list=array_slice($timer_list, 0,6);
        }
            
        # pad list to 6 with inactive timers
        if (count($timer_list) < 6){
         	$this->SendDebug(__FUNCTION__,"Apply empty Timer Records",0);
        	$empty=array_fill(0,14,0);
			$empty[0]=0x0f;        	
			while(count($timer_list) < 6){
				$item=new StdClass;
				$item->info=$this->Translate("Timer not defined");
				$item->expired=false;
				$item->data=$empty;
       			$timer_list[]=$item;
			}
        }
        $msg=[0x21];
        foreach($timer_list as $id=>$item){
        	$item->id=$id+1;
			if($item->expired)$item->rowColor=self::COLOR_EXPIRED;
			elseif($item->data[0]!== 0xf0)$item->rowColor=self::COLOR_EMPTY;
			else $item->rowColor=self::COLOR_ACTIVE;
        	$msg=array_merge($msg,$item->data);
        }
        $msg[]=0x00;
        $msg[]=0xf0;
        if(count($msg)!=87){
        	IPS_LogMessage(IPS_GetName($this->InstanceID), "Invalid TimerMsg lenght!! Skip sending Timerlist");
        	return;
        }
        if($this->SendData($msg)){
         	$this->SetBuffer('CacheTimer', '');
 			$this->WriteAttributeString('TimerList', $values=json_encode($timer_list));
 			$this->UpdateFormField('TimerList', 'values', $values);
 			$this->SetFormField('TimerID', 'value', 0);
         }
	}
   	private function GetTimerDate(array $raw){
		if($raw[1]!=0 && $raw[2]!=0 && $raw[3]!=0){
	   		$dt = new DateTime();
	   		$dt->setDate($raw[1]+2000,$raw[2],$raw[3]);
	   		$dt->setTime($raw[4],$raw[5]);
	   		return $dt->getTimestamp();
		}
		return 0;
    }
    private function IsTimerExpired(array $raw, $date=null){
    	if(is_null($date))$date = $this->GetTimerDate($raw);
    	if ($date && $raw[7] == 0){ // Repeat_mask
   		 	$delta = $date - time();
    		return $delta<0;
        }
        return false;
    }
	private function IsTimerEmpty(array $raw){
   		return ( $raw[0] != 0xf0 && $raw[7]==0 && $raw[13] != 0xf0);
	}
	private function GetTimerInfo(array $RawBytes){
	   # timer are in six 14-byte structs;
	   #     f0 0f 08 10 10 15 00 00 25 1f 00 00 00 f0 0f;
	   #      0  1  2  3  4  5  6  7  8  9 10 11 12 13 14;
	
	   #     0: f0 when active entry/ 0f when not active;
	   #     1: (0f=15) year when no repeat, else 0;
	   #     2:  month when no repeat, else 0;
	   #     3:  dayofmonth when no repeat, else 0;
	   #     4: hour;
	   #     5: min;
	   #     6: 0;
	   #     7: repeat mask, Mo=0x2,Tu=0x04, We 0x8, Th=0x10 Fr=0x20, Sa=0x40, Su=0x80;
	   #     8:  61 f|| solid col|| || warm, || preset pattern code;
	   #     9:  r (|| delay f|| preset pattern);
	   #     10: g;
	   #     11: b;
	   #     12: warm white level;
	   #     13: 0f = off, f0 = on ?;
  		
   		$is_active = $RawBytes[0] == 0xf0;
   		$repeat_mask=$RawBytes[7];
   		$power_on=$RawBytes[13] == 0xf0;
   		if(!$is_active&&!$repeat_mask&&!$power_on){
   			$txt = $this->Translate("Timer not defined");
   			return $txt;
   		}
   		$date=$this->GetTimerDate($RawBytes);
   		if($this->IsTimerExpired($RawBytes,$date))$is_active=null;
   		if(is_null($is_active))
   			$is_active = "Active: Expired";
   		else $is_active = $is_active ? "Active: Yes" : "Active: No";
   		$txt[] =$this->Translate( $is_active);
   		
   		if($repeat_mask==self::Weekdays){
			$txt[]=$this->Translate("every weekday");
		}
		elseif($repeat_mask==self::Weekend){
			$txt[]=$this->Translate("every Weekend");
		}
		else {
   			$t=[];
   			foreach(self::BuilInDayMask as $key=>$bit){
   				if($repeat_mask & $bit)$t[]=$this->Translate($key);
	    	}
	    	if(!empty($t))
	    		$txt[]=$this->Translate('every').' '.join(',',$t);
		}
		if($date)$txt[]=$this->Translate("on").' '.date($this->Translate("Y-d-m H:i"), $date);   		
		
		
		$pattern_code = $RawBytes[8];
        
        if ($pattern_code == 0x00){
//             $txt[]="Unknown";
        }elseif ($pattern_code == 0x61){
            $txt[]=$this->Translate("Color").sprintf(' R:%s G:%s B:%s',$RawBytes[9],$RawBytes[10],$RawBytes[11]) ;
        }
        elseif ($opt=array_search($pattern_code,self::BuildInTimer)){
            $txt[]=sprintf($this->Translate("Preset: %s Duration: %s Brightness: from %s to %s"),$opt,$RawBytes[9],$RawBytes[10],$RawBytes[11]);
        }
        elseif ($opt=array_search($pattern_code,self::BuildInPresets)){
          	$txt[]=sprintf($this->Translate("%s Delay: %s%%"),$this->Translate($opt) , $RawBytes[9]);
        }
        elseif($RawBytes[12]!=0){
        	$txt[]=sprintf($this->Translate("White only Value: %s"),$RawBytes[12]);
        }
        if($RawBytes[0] == 0xf0)
        	$txt[]=$this->Translate( $power_on ? "Switch: ON" : "Switch: OFF");
        return join(' ',$txt);
    }
	private function SetTimerConfig($JSONFormString){
    	list($TimerID,$Active,$Mode,$Speed,$Duration,$WWStart,$WWEnd,$Color,$Date,$Time,$Mo,$Tu,$We,$Th,$Fr,$Sa,$Su,$WW,$Power)=json_decode($JSONFormString,true);
    	$timer_list = $this->LoadTimerList();
		$item = $timer_list[$TimerID];
  		$saved = $item->data;
        if ($Active){
	        $item->data[0] = 0xf0;
	        
	        if($time=json_decode($Time,true)){
		        $item->data[4] = $time['hour'];
		        $item->data[5] = $time['minute'];
	        } else list($item->data[4],$item->data[5])=[0,0];
		   		
	       
	        $repeat_mask = 0;
	        if($Mo)$repeat_mask|=self::BuilInDayMask['Mo'];
	        if($Tu)$repeat_mask|=self::BuilInDayMask['Tu'];
	        if($We)$repeat_mask|=self::BuilInDayMask['We'];
	        if($Th)$repeat_mask|=self::BuilInDayMask['Th'];
	        if($Fr)$repeat_mask|=self::BuilInDayMask['Fr'];
	        if($Sa)$repeat_mask|=self::BuilInDayMask['Sa'];
	        if($Su)$repeat_mask|=self::BuilInDayMask['Su'];
	        $item->data[7] = $repeat_mask;
	        if(!$repeat_mask){
		        if($date=json_decode($Date,true)){
			        $item->data[1] = $date['year'] >= 2000 ? $date['year'] - 2000 : $date['year'];
			        $item->data[2] = $date['month'];
			        $item->data[3] = $date['day'];
			    }
	        	else list($item->data[1],$item->data[2],$item->data[3])=[0,0,0];
	        }
	        
	        $item->expired=$this->IsTimerExpired($item->data);
			if($item->expired)$item->rowColor=self::COLOR_EXPIRED;
			elseif($item->data[0]!== 0xf0)$item->rowColor=self::COLOR_EMPTY;
			else $item->rowColor=self::COLOR_ACTIVE;
		   	
	        if ($Power){
		        $item->data[13] = 0xf0;
		        if($Mode == 0x61){ // Color
		        	list($item->data[9],$item->data[10],$item->data[11]) = colorsys::int_to_rgb($Color);
		        }
		        elseif(array_search($Mode, self::BuildInPresets)){
		           	$item->data[9] = $Speed;
		            $item->data[10] = 0;
		            $item->data[11] = 0;
		        }
		        elseif(array_search($Mode,self::BuildInTimer)){
		           	$item->data[9] = $Duration;
		            $item->data[10] = $WWStart;
		            $item->data[11] = $WWEnd;
		            list($item->data[1],$item->data[2],$item->data[3],$item->data[4],$item->data[5])=[0,0,0,0,0];
		        }
		        $item->data[8] = $Mode;
		 		$item->data[12] = $Mode==0xFE ?  $WW : 0xFF;
	        }
	        else
	        	$item->data[13] = 0x0f;
        }
        else $item->data[0] = 0x0f;
        if($item->data!=$saved){
        	$this->WriteAttributeBoolean('TimerlistChanged',true);
        	$item->rowColor=self::COLOR_CHANGED;
        	$item->info=$this->GetTimerInfo($item->data);
			$this->UpdateFormField('TransferBtn', 'visible', true); 
        }
        $this->WriteAttributeString('TimerList', $values=json_encode($timer_list));
		$this->UpdateFormField('TimerList', 'values', $values);
	}
    
    ################################################################
    #  Module Update / Timer
	################################################################
	private function StopTimer(){
		$this->SetTimerInterval('UpdateTimer', 0);
	}
	private function StartTimer($MsDelay=0, $RequestOptions=0){
		if(!empty($MsDelay)){
			$this->SetBuffer('RequestOptions',$RequestOptions);
			$this->SetTimerInterval('UpdateTimer', $MsDelay);
		}
		else {
			$this->SetTimerInterval('UpdateTimer', 299000);
		}
	}
    ################################################################
    #  Private Utils 
	################################################################
    private function CreateProfiles(){
		$profile = 'Presets.MHC';
		$colors=[0x000000,0xff0000,0x00ff00,0x0000ff,0xffff00,0x00ffff,0xff00ff,0xf0f000,0xf000f0,0x00f0f0,0xa0a0a0,0xff0000,0x00ff00,0x0000ff,0xffff00,0x00ffff,0xff00ff,0xffffff,0xa0a0a0];
        @IPS_CreateVariableProfile($profile, 1);
		IPS_SetVariableProfileAssociation($profile, 0, "Manuell", "", 0x000000);
		foreach(self::BuildInPresets as $text=>$value){
			$color=count($colors)>0 ? array_shift($colors):-1;
			IPS_SetVariableProfileAssociation($profile, $value, $this->Translate($text), "", $color);
		}
    }
	private function UpdateOfflineName(bool $IsOffline){
		$name = IPS_GetName($this->InstanceID); 
		$msg_Offline=' => [ '.$this->Translate('OFFLINE').' ]';
		$is_Offline=stripos($name,$msg_Offline)!==false;
		if($IsOffline  && !$is_Offline){
			$name.=$msg_Offline;
			$change=true;
		}elseif(!$IsOffline && $is_Offline){
			$name=str_ireplace($msg_Offline,'',$name);
			$change=true;
		} else $change=false;
		if($change)IPS_SetName($this->InstanceID, $name);
		return $change;
	}
    private function Ready(){
    	return $this->HasActiveParent();
    }
	################################################################
    #  Private Constants 
	################################################################
	private const PROTOCOL_DEFAULT 	= 0;
	private const PROTOCOL_LEDENET	= 1;
	private const PROTOCOL_LEDENET_ORIGINAL = 2;
	private const LEDMODE_MANUAL 	= 0;
    private const LEDMODE_COLOR 	= 1;
    private const LEDMODE_WW 		= 2;
    private const LEDMODE_CUSTOM 	= 3;
    private const LEDMODE_PRESET 	= 4;
    private const LEDMODE_SUNRISE 	= 5;
    private const LEDMODE_SUNSET 	= 6;
    private const COLOR_EXPIRED		= '#FFC0C0';
    private const COLOR_ACTIVE		= '#C0FFC0';
    private const COLOR_EMPTY		= '#DFDFDF';
    private const COLOR_CHANGED		= '#FFFFC0';
	private const SHOW_COLOR_ONLY 	= 0;
	private const SHOW_RGB_ONLY 	= 1;
	private const SHOW_COLOR_RGB  	= 2;
	private const REQUEST_STATE 	= 1;
	private const REQUEST_TIME 		= 2;
	private const REQUEST_TIMERS 	= 4;
 	private const REQUEST_FORMUPDATE= 8;

	private const BuildInTimer = [
		'Sunrise'=>0xA1,
		'Sunset'=>0xA2
	];
	private const BuildInPresets = [
	    'Seven colors crossfade' 	=> 0x25,
	    'Red gradual change' 		=> 0x26,
		'Green gradual change'		=> 0x27,
		'Blue gradual change'		=> 0x28,
		'Yellow gradual change' 	=> 0x29,
		'Cyan gradual change' 		=> 0x2a,
		'Purple gradual change' 	=> 0x2b,
		'White gradual change' 		=> 0x2c,
		'Red-Green crossfade' 		=> 0x2d,
		'Red-Blue crossfade' 		=> 0x2e,
		'Green-Blue crossfade' 		=> 0x2f,
		'Seven color strobe flash' 	=> 0x30,
		'Red strobe flash' 			=> 0x31,
		'Green strobe flash' 		=> 0x32,
		'Blue strobe flash' 		=> 0x33,
		'Yellow strobe flash' 		=> 0x34,
		'Cyan strobe flash' 		=> 0x35,
		'Purple strobe flash' 		=> 0x36,
		'White strobe flash' 		=> 0x37,
		'Seven color jumping' 		=> 0x38
	];
	private const BuilInDayMask = [
	   	'Mo' => 0x02,
	    'Tu' => 0x04,
	    'We' => 0x08,
	    'Th' => 0x10,
	    'Fr' => 0x20,
	    'Sa' => 0x40,
	    'Su' => 0x80
	];
	private const Weekdays = 0x02|0x04|0x08|0x10|0x20;
	private const Weekend  = 0x40|0x80;
}
class utils {
	private const max_delay = 0x1f;
	public static function delayToSpeed($delay){
        # speed is 0-100, delay is 1-31
        # 1st translate delay to 0-30
        $delay = $delay -1;
        if ($delay > self::max_delay - 1 )
            $delay = self::max_delay - 1;
        if ($delay < 0)
            $delay = 0;
        $inv_speed = (int)($delay * 100)/(self::max_delay - 1);
        $speed =  100-$inv_speed;
        return $speed;
	}

	public static function speedToDelay($speed){
        # speed is 0-100, delay is 1-31
        if ($speed > 100)
            $speed = 100;
        if ($speed < 0)
            $speed = 0;
        $inv_speed = 100-$speed;
        $delay = (int)($inv_speed * (self::max_delay-1))/100;
        # translate from 0-30 to 1-31
        $delay = $delay + 1;
        return $delay;
	}

	public static function byteToPercent($byte){
        if ($byte > 255)
            $byte = 255;
        if ($byte < 0)
            $byte = 0;
        return (int)($byte * 100)/255;
	}

	public static function percentToByte($percent){
        if ($percent > 100)
            $percent = 100;
        if ($percent < 0)
            $percent = 0;
        return (int)($percent * 255)/100;
	}

}
class colorsys {
	public static function int_to_rgb($Color){
		return [($Color >> 16) & 0xFF,
				($Color >> 8) & 0xFF,
				$Color & 0xFF	
		];
	}
	public static function rgb_to_int($r,$g,$b){
		return ($r << 16) + ($g << 8) + $b;
	}
	public static function calculate_brightness($r,$g,$b,$Level){
		$rgb=[$r,$g,$b];
		$maxc = max($rgb);
		if($maxc==0)return [$Level,$Level,$Level];
		
		if($maxc==$Level) return $rgb;
		for($j=0;$j<3;$j++){
			$rgb[$j]=(int)(round($rgb[$j]/$maxc*$Level));
			if($rgb[$j]>255)$rgb[$j]=255;
		}
		return $rgb;
	}
}
?>