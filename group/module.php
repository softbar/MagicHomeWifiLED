<?php
require_once __DIR__.'/../libs/utils.inc';
class WifiBulbControlerGroup extends IPSModule {
	################################################################
	#  Public Hidden Methods
	################################################################
	public function Create(){
		parent::Create();
		$this->RegisterPropertyString('Controllers','');
		$this->RegisterPropertyBoolean('ShowColor', true);
		$this->RegisterPropertyBoolean('ShowBrightness', true);
		$this->RegisterPropertyBoolean('ShowWhite', true);
		$this->RegisterPropertyBoolean('ShowMode', true);
		
	}
    public function Destroy(){
    	parent::Destroy();
    	if( count(IPS_GetInstanceListByModuleID("{5638FDC0-C110-WIFI-MAHO-201905120WBC}"))==0 &&
    		count(IPS_GetInstanceListByModuleID("{5638FDC0-C110-WIFI-MAHO-201905120WBG}"))==0)
      	{
    		@IPS_DeleteVariableProfile('Presets.WBC');
    	}
    }
	public function ApplyChanges(){
		parent::ApplyChanges();
		if(!@$this->GetIDForIdent('POWER'))$this->SetValue('POWER',null);
		$id=@$this->GetIDForIdent('COLOR');
		if($this->ReadPropertyBoolean('ShowColor')){
			if(!$id)$this->SetValue('COLOR',0);
		}elseif($id)$this->UnregisterVariable('COLOR');
		$id=@$this->GetIDForIdent('BRIGHTNESS');
		if($this->ReadPropertyBoolean('ShowBrightness')){
			if(!$id)$this->SetValue('BRIGHTNESS',0);
		}elseif($id)$this->UnregisterVariable('BRIGHTNESS');
		$id=@$this->GetIDForIdent('WARM_WHITE');
		if($this->ReadPropertyBoolean('ShowWhite')){
			if(!$id)$this->SetValue('WARM_WHITE',0);
		}elseif($id)$this->UnregisterVariable('WARM_WHITE');
		
		
		$list 	= [];
		$status	= $this->GetControllerList($list);
		if($status==102){
			$showMode= $this->ReadPropertyBoolean('ShowMode');
			
			if($showMode){
				if(!@$this->GetIDForIdent('MODE'))$this->SetValue('MODE',0);
				if(!@$this->GetIDForIdent('SPEED'))$this->SetValue('SPEED',90);
			} else {
				@$this->UnregisterVariable('MODE');
				@$this->UnregisterVariable('SPEED');
			}
			if(empty(@$this->GetValue('COLOR')) && ($id=@IPS_GetObjectIDByIdent('COLOR', $list[0]->instanceID))){
				$this->SetValue('COLOR',$Color=GetValue($id));
				$this->SetValue('BRIGHTNESS',max(colorsys::int_to_rgb($Color)));
				if($showMode){
					$this->SetValue('MODE',0);
	 				$this->SetValue('SPEED',90);
				}
				if($this->ReadPropertyBoolean('ShowWhite')){
	 				if($id=@IPS_GetObjectIDByIdent('WARM_WHITE', $list[0]->instanceID)){
	 					$this->SetValue('WARM_WHITE',GetValue($id));
	 				}
				}
			}
    	}
    	$this->SetStatus($status);	
	}
	public function GetConfigurationForm() {
		$list=json_decode($this->ReadPropertyString('Controllers'));
		$this->GetControllerList($list);
		$f 	= json_decode(file_get_contents(__DIR__.'/form.json'));
		$f->elements[1]->values=$list;
		return json_encode($f);	
	}
	public function RequestAction($Ident, $Value){
		switch($Ident){
			case 'POWER': $this->SetPower((bool)$Value); break;
			case 'COLOR': $this->SetColor((int)$Value); break;
			case 'BRIGHTNESS': $this->SetBrightness((int)$Value);break;
			case 'WARM_WHITE': $this->SetWhite((int)$Value);break;
			case 'MODE' : $this->RunProgram((int)$Value, $this->GetSpeed());break;
			case 'SPEED' :$this->RunProgram($this->GetMode(),(int)$Value);break;
		}
	}
	################################################################
    #  Public Module Methods
	################################################################
	public function SetPower(bool $PowerOn){
		if($list=$this->LoadControllerList()){
			foreach($list as $item){
				if($item->enabled)IPS_RequestAction($item->instanceID, 'POWER', $PowerOn);
			}
			$this->SetValue('POWER', $PowerOn);
		}
	}
	public function SetColor(int $Color){
		if($list=$this->LoadControllerList()){
			foreach($list as $item){
				if($item->enabled)IPS_RequestAction($item->instanceID, 'COLOR', $Color);
			}
			$this->SetValue('POWER', true);
			$this->SetValue('COLOR', $Color);
			$this->SetValue('BRIGHTNESS',max(colorsys::int_to_rgb($Color)));
			$this->SetValue('MODE',0);
		}
	}
	public function SetBrightness(int $Level255){
		if($list=$this->LoadControllerList()){
			foreach($list as $item){
				if($item->enabled)IPS_RequestAction($item->instanceID, 'BRIGHTNESS', $Level255);
			}
			$r=colorsys::int_to_rgb($this->GetValue('COLOR'));
	        $r=colorsys::calculate_brightness($r[0], $r[1], $r[2], $Level255);
	        $this->SetValue('POWER', true);
			$this->SetValue('BRIGHTNESS',$Level255);
			$this->SetValue('COLOR', colorsys::rgb_to_int($r[0], $r[1], $r[2]));
 			$this->SetValue('MODE',0);
		}
	}
	public function SetWhite(int $Level255){
		if($list=$this->LoadControllerList()){
			foreach($list as $item){
				if($item->enabled)IPS_RequestAction($item->instanceID, 'WARM_WHITE', $Level255);
			}
			$this->SetValue('POWER', true);
			$this->SetValue('WARM_WHITE', $Level255);
			$this->SetValue('MODE',0);
		}
	}
	public function RunProgram(int $ProgramID, int $Speed100){
  		if ($ProgramID >= 0x25 && $ProgramID <= 0x38){
			if($list=$this->LoadControllerList()){
				foreach($list as $item){
					if($item->enabled)WBC_RunProgram($item->instanceID, $ProgramID, $Speed100);
				}
				$this->SetValue('POWER', true);
				$this->SetValue('MODE', $ProgramID);
			}
  		} else {
			if($list=$this->LoadControllerList()){
				foreach($list as $item){
					if($item->enabled)WBC_RunProgram($item->instanceID, 0, 0);
				}
				$this->SetValue('POWER', true);
				$this->SetValue('MODE', 0);
			}
  			
  		}
	}
	################################################################
    #  Protected Override
	################################################################
	protected function SetValue($Ident, $Value){
		$id=@$this->GetIDForIdent($Ident);
		switch ($Ident){
			case 'POWER': 
				if(!$id)$id=$this->RegisterVariableBoolean($Ident, $this->Translate('Power'), '~Switch', 0);
				if(!is_null($Value))SetValue($id,(bool)$Value);
				break;
			case 'COLOR': 
				if(!$this->ReadPropertyBoolean('ShowColor'))break;
				if(!$id)$id=$this->RegisterVariableInteger($Ident, $this->Translate('Color'), '~HexColor', 1);
				if(!is_null($Value))SetValue($id,(int)$Value);
				break;
			case 'BRIGHTNESS': 
				if(!$this->ReadPropertyBoolean('ShowBrightness'))break;
				if(!$id)$id=$this->RegisterVariableInteger('BRIGHTNESS', $this->Translate('Brightness'), '~Intensity.255', 2);
				if(!is_null($Value))SetValue($id,(int)$Value);
				break;
			case 'WARM_WHITE':
				if(!$this->ReadPropertyBoolean('ShowWhite'))break;
				if(!$id)$id=$this->RegisterVariableInteger('WARM_WHITE', $this->Translate('White'), '~Intensity.255', 3);
				if(!is_null($Value))SetValue($id,(int)$Value);
				break;
			case 'SPEED': 
				if(!$this->ReadPropertyBoolean('ShowMode'))break;
				if(!$id)$id=$this->RegisterVariableInteger('SPEED', $this->Translate('Speed'), '~Intensity.100', 5);
				if(!is_null($Value)){
					SetValue($id,(int)$Value);
					if($idd=@$this->GetIDForIdent('MODE'))IPS_SetDisabled($id, GetValue($idd)==0);
				}
				break;
			case 'MODE':
				if(!$this->ReadPropertyBoolean('ShowMode'))break;
				if(!$id)$id=$this->RegisterVariableInteger('MODE', $this->Translate('Mode'), 'Presets.WBC', 4);
				if(!is_null($Value)){
					SetValue($id,(int)$Value);
					if($id=@$this->GetIDForIdent('SPEED'))IPS_SetDisabled($id, $Value==0);
// 					if($id=@$this->GetIDForIdent('COLOR'))IPS_SetDisabled($id, $Value!=0);
// 					if($id=@$this->GetIDForIdent('BRIGHTNESS'))IPS_SetDisabled($id, $Value!=0);
// 					if($id=@$this->GetIDForIdent('WARM_WHITE'))IPS_SetDisabled($id, $Value!=0);
				}
				break;
			default:return;
		}
		
	}
	protected function SetStatus($Status){
		parent::SetStatus($Status);
		$enabled=$Status==102;
		@$this->MaintainAction('POWER', $enabled);
		@$this->MaintainAction('COLOR', $enabled);
		@$this->MaintainAction('BRIGHTNESS', $enabled);
		@$this->MaintainAction('WARM_WHITE', $enabled);
		@$this->MaintainAction('MODE', $enabled);
		@$this->MaintainAction('SPEED', $enabled);
	}
    ################################################################
    #  Private Utils 
	################################################################
	private function GetSpeed(){
		return ($speed=@$this->GetValue('SPEED'))?$speed:100;
	}
	private function GetMode(){
		return ($mode=@$this->GetValue('MODE'))?$mode:0;
	}
	private function LoadControllerList(){
		if($this->GetStatus()==102){
			$list=[];
			$status=$this->GetControllerList($list);
			if($status==102)
				return $list;
			else $this->SetStatus($status);
		}
	}
	private function GetControllerList(&$list=null){
		if(empty($list))$list=json_decode($this->ReadPropertyString('Controllers'));
		$status=200;
		if(!empty($list)){
			// Check list
			$status=102;
			foreach($list as $item){
				if(empty($item->instanceID)){
					$status=201;
					$item->rowColor='#FFC0C0';
				}
				else if(empty($i=@IPS_GetInstance($item->instanceID))){
					$status=202;
					$item->rowColor='#FFC0C0';
				}
				else if($i['ModuleInfo']['ModuleID']!='{5638FDC0-C110-WIFI-MAHO-201905120WBC}'){
					$status=203;
					$item->rowColor='#FFC0C0';
				}
				else unset($item->rowColor);
			}
			if($status==102){
				$ids	= array_map(function($i){return $i->instanceID;},$list);
				$check 	= array_keys(array_flip($ids));
				if(count($ids)!=count($check)){
					$status=204;
				}
			}
		}
		return $status;
	}
}
?>