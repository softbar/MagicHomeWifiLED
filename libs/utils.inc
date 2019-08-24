<?php
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