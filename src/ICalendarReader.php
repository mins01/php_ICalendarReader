<?
// https://tools.ietf.org/html/rfc5545
/*
https://lynmp.com/en/article/oi811c9dc5gy
한국 : https://p03-calendars.icloud.com/holidays/kr_ko.ics
일본 : https://p03-calendars.icloud.com/holidays/jp_ja.ics
중국 : https://p03-calendars.icloud.com/holidays/cn_zh.ics
대만 : https://p03-calendars.icloud.com/holidays/tw_zh.ics
미국 : https://p03-calendars.icloud.com/holidays/us_en.ics
영국 : https://p03-calendars.icloud.com/holidays/gb_en.ics
캐나다 : https://p03-calendars.icloud.com/holidays/ca_en.ics
독일 : https://p03-calendars.icloud.com/holidays/de_de.ics
*/
/*
간략하게만 지원, 연휴체크용이다.
FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1 처럼 복잡한 것 지원안할꺼다.
 */

class ICalendarReader{
	public $debug = false;
	public $VCALENDAR = array();
	public $VEVENTs = array();
	public $links = array();
	public $times = array();
	public function __construct($str=null){
		if($str != null){
			$this->load($str);
		}
	}
	public function load($str)
	{
		$rs = $this->parse($str);
		$this->VCALENDAR = $rs['VCALENDAR'];
		$this->VEVENTs = $rs['VEVENTs'];
		$this->links = $rs['links'];
		$this->times = $this->generateTimes($this->links);
	}
	public function clear(){
		$this->VCALENDAR = array();
		$this->VEVENTs = array();
		$this->links = array();
		$this->times = array();
	}
	public function append($str)
	{

		$rs = $this->parse($str);
		$this->VCALENDAR = array_merge($this->VCALENDAR,$rs['VCALENDAR']);
		$this->VEVENTs = array_merge($this->VEVENTs,$rs['VEVENTs']);
		$this->links = array_merge($this->links,$rs['links']);
		$this->times = $this->generateTimes($this->links);
	}
	public function decode($str){
		return str_replace(array('\\\\' , '\\;' , '\\,', '\\N', '\\n'),array('\\' , ';' , ',',"\n","\n"),$str);
	}
	public function encode($str){
		return str_replace(array('\\' , ';' , ',',"\n"),array('\\\\' , '\\;' , '\\,', '\\n'),$str);
	}
	public function str_split($str){
		// $str = 'VERSION:2.0한글도 잘되는가?한글도 잘되는가?한글도 잘되는가?한글도 잘되는가?한글도 잘되는가?한글도 잘되는가?한글도 잘되는가?한글도 잘되는가?한글도 잘되는가?';
		$arr = preg_split( '//u' , $str);
		// print_r($r);exit;
		// echo $str;
		// echo array_shift($arr);
		$rstr = '';
		$tstr = '';
		while(($t = array_shift($arr))!==null){
			if(strlen($tstr)+strlen($t) > 73){
				$tstr.="\r\n";
				// var_dump($tstr);
				$rstr.=$tstr;
				$tstr = ' ';
			}
			$tstr.=$t;
		}
		// var_dump($tstr);
		$rstr.=$tstr;
		return $rstr;
	}
	/**
	 * parse
	 * @param  string $str iCalendar contents string
	 * @return array      array('VCALENDAR'=>{VCALENDAR 정보 매열},'VEVENTs'=>{VEVENT 정보 2차 배열},'links'=>{links(시간정보등의 제어용 정보) 정보 배열},'times'=>{시간순으로 정렬된 정보})
	 */
	public function parse($str){
		$str = str_replace("\r\n ", '', $str); //멀티라인 처리
		$arr = preg_split('/(\r\n|\r|\n)/',$str,-1,PREG_SPLIT_NO_EMPTY);
		unset($str);
		$VCALENDAR = array();
		$VEVENTs = array();
		$links = array();
		$VEVENT = null;
		$link = null;
		$curr_begin = '';
		foreach ($arr as $k => $v) {
			$v = trim($v);
			if(!isset($v)){continue;}
			$t = explode(':',trim($v));
			$val = $this->decode(trim($t[1]));
			if($t[0]=='BEGIN'){
				$curr_begin = $val;
				if($val=='VEVENT'){
					$VEVENT = array();
					$VEVENTs[] = & $VEVENT;
					$link = array('VEVENT'=> & $VEVENT);
					$links[] = & $link;
				}
				continue;
			}
			if($t[0]=='END'){
				$curr_begin = '';
				if($val=='VEVENT'){
					unset($VEVENT);
					unset($link);
				}
				continue;
			}
			if($curr_begin=='VCALENDAR'){
				$VCALENDAR[$t[0]] = $val;
			}
			if($curr_begin=='VEVENT'){
				$VEVENT[$t[0]] = $val;
				if(strpos($t[0],';')!==false){
					$t2 = explode(';',$t[0]);
					if($t2[0]=='DTSTART'){
						$link['timeStart'] = strtotime($val);
						$link['times'] = array($link['timeStart']);
						$VEVENT['DTSTART'] = date('Y-m-d H:i:s',$link['timeStart']);
					}else if($t2[0]=='DTEND'){
						$link['timeEND'] = strtotime($val);
						$VEVENT['DTEND'] = date('Y-m-d H:i:s',$link['timeEND']);
					}else{
						$VEVENT[$t2[0]] = $val;
					}
				}else if($t[0]=='RRULE'){
					//RRULE:FREQ=YEARLY;COUNT=5
					$rules = array();
					$trules = explode(';',$val);
					foreach ($trules as $r) {
						$trule = explode('=', $r);
						$rules[$trule[0]]=$trule[1];
					}
					$link['rrules'] = $rules;
				}
			}
		}
		$this->recursiveLinks($links);
		// print_r($links);
		// $times = $this->generateTimes($links);
		return array('VCALENDAR'=>$VCALENDAR,'VEVENTs'=>$VEVENTs,'links'=>$links);
	}
	/**
	 * recursiveLinks
	 * @param  & array      $links links(시간정보등의 제어용 정보) 정보 배열
	 * @return null
	 */
	private function recursiveLinks(& $links){
		foreach ($links as & $link) {
			if(!isset($link['rrules'])){ continue; }
			// echo "링크\n";
			// print_r($link);
			// $rrules = $link['rrules'];
			// $currTime = $link['timeStart'];
			$link['times'] = $this->calcurateRrules($link['rrules'],$link['timeStart']);
			// print_r($link['times']);
		}
	}
	private function calcurateRrulesByBys($currTime,$BYMONTH,$BYWEEKNO,$BYYEARDAY,$BYMONTHDAY,$BYDAY,$BYHOUR,$BYMINUTE,$BYSECOND,$BYSETPOS){
		// following order: BYMONTH, BYWEEKNO, BYYEARDAY, BYMONTHDAY, BYDAY, BYHOUR, BYMINUTE, BYSECOND and BYSETPOS; then COUNT and UNTIL are evaluated.
		$days = array(
			'MO' => 'Monday',
			'TU' => 'Tuesday',
			'WE' => 'Wednesday',
			'TH' => 'Thursday',
			'FR' => 'Friday',
			'SA' => 'Saturday',
			'SU' => 'Sunday',
		);
		// $args = func_get_args();
		// echo date('Y-m-d',$currTime).' : ';
		// print_r(implode(', ',$args));
		// echo "\n";
		$time = $currTime;
		$curr_Y = date('Y',$time);
		$curr_n = ($BYMONTH!==null)?$BYMONTH:date('n',$time);
		$curr_j = ($BYMONTHDAY!==null)?$BYMONTHDAY:date('j',$time);
		$curr_G = ($BYHOUR!==null)?$BYHOUR:date('G',$time);
		$curr_i = ($BYMINUTE!==null)?$BYMINUTE:intval(date('i',$time),10);
		$curr_s = ($BYSECOND!==null)?$BYSECOND:intval(date('s',$time),10);
		$time = mktime($curr_G,$curr_i,$curr_s,$curr_n,$curr_j,$curr_Y);
		// if($BYMONTH!=null){
		// 	$time = mktime($curr_G,$curr_i,$curr_s,$BYMONTH,$curr_j,$curr_Y);
		// 	$curr_Y = date('Y',$time);
		// 	$curr_n = date('n',$time);
		// 	$curr_j = date('j',$time);
		// }
		if($BYWEEKNO!=null){ // not tested!
			$time = strtotime($curr_Y.'W'.sprintf('%02d',$BYWEEKNO));
			$curr_Y = date('Y',$time);
			$curr_n = date('n',$time);
			$curr_j = date('j',$time);
		}
		if($BYYEARDAY!=null){ // not tested!
			$time = mktime($curr_G,$curr_i,$curr_s,1,$BYYEARDAY,$curr_Y);
			$curr_Y = date('Y',$time);
			$curr_n = date('n',$time);
			$curr_j = date('j',$time);
		}
		// if($BYMONTHDAY!=null){ // not tested!
		// 	$time = mktime($curr_G,$curr_i,$curr_s,$curr_n,$BYMONTHDAY,$curr_Y);
		// 	$curr_Y = date('Y',$time);
		// 	$curr_n = date('n',$time);
		// 	$curr_j = date('j',$time);
		// }

		if($BYDAY!=null){
			$matches = array();
			preg_match("/(-?\d)([A-Z]{2})/",$BYDAY,$matches);
			if(isset($matches[2])){
				$twn = $matches[1];
				$twd = $days[$matches[2]];
			}else{
				$twn = 0;
				$twd = $days[$BYDAY];
			}
			if($twn==0){
				$time = date('l', $time) !== $twd?strtotime('last '.$twd, $time):$time;
			}else if($twn<0){
				$time = mktime($curr_G,$curr_i,$curr_s,date('n',$time)+1,0,$curr_Y);
				// echo 'ST BYDAY: '.date('Y-m-d D',$time)." \n";
				$time = date('l', $time) !== $twd?strtotime('last '.$twd, $time):$time;
				$time += 86400*7*($twn+1);
			}else if($twn>0){
				$time = mktime($curr_G,$curr_i,$curr_s,date('n',$time),1,$curr_Y);
				// echo 'ST BYDAY: '.date('Y-m-d D',$time)."\n";
				$time = date('l', $time) !== $twd?strtotime('next '.$twd, $time):$time;
				$time += 86400*7*($twn-1);
			}
			// echo 'ED BYDAY: '.date('Y-m-d D',$time)."\n";
		}
		return $time;
	}
	private function calcurateRrulesByRrules($rrules,$currTime){
		$times = array();

		$BYMONTHs = isset($rrules['BYMONTH'])?explode(',',$rrules['BYMONTH']):array(null);
		$BYWEEKNOs = isset($rrules['BYWEEKNO'])?explode(',',$rrules['BYWEEKNO']):array(null);
		$BYYEARDAYs = isset($rrules['BYYEARDAY'])?explode(',',$rrules['BYYEARDAY']):array(null);
		$BYMONTHDAYs = isset($rrules['BYMONTHDAY'])?explode(',',$rrules['BYMONTHDAY']):array(null);
		$BYDAYs = isset($rrules['BYDAY'])?explode(',',$rrules['BYDAY']):array(null);
		$BYHOURs = isset($rrules['BYHOUR'])?explode(',',$rrules['BYHOUR']):array(null);
		$BYMINUTEs = isset($rrules['BYMINUTE'])?explode(',',$rrules['BYMINUTE']):array(null);
		$BYSECONDs = isset($rrules['BYSECOND'])?explode(',',$rrules['BYSECOND']):array(null);
		$BYSETPOSs = isset($rrules['BYSETPOS'])?explode(',',$rrules['BYSETPOS']):array(null);
		// print_r($rrules);
		foreach ($BYMONTHs as $BYMONTH) {
			foreach ($BYWEEKNOs as $BYWEEKNO) {
				foreach ($BYYEARDAYs as $BYYEARDAY) {
					foreach ($BYMONTHDAYs as $BYMONTHDAY) {
						foreach ($BYDAYs as $BYDAY) {
							foreach ($BYHOURs as $BYHOUR) {
								foreach ($BYMINUTEs as $BYMINUTE) {
									foreach ($BYSECONDs as $BYSECOND) {
										foreach ($BYSETPOSs as $BYSETPOS) {
											$times[]=$this->calcurateRrulesByBys($currTime,$BYMONTH,$BYWEEKNO,$BYYEARDAY,$BYMONTHDAY,$BYDAY,$BYHOUR,$BYMINUTE,$BYSECOND,$BYSETPOS);
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $times;
	}

	//전체다 지원하는건 아니다.
	private function calcurateRrules($rrules,$currTime){
		// recur-rule-part = ( "FREQ" "=" freq )
    //                    / ( "UNTIL" "=" enddate )
    //                    / ( "COUNT" "=" 1*DIGIT )
    //                    / ( "INTERVAL" "=" 1*DIGIT )
    //                    / ( "BYSECOND" "=" byseclist )
    //                    / ( "BYMINUTE" "=" byminlist )
    //                    / ( "BYHOUR" "=" byhrlist )
    //                    / ( "BYDAY" "=" bywdaylist )
    //                    / ( "BYMONTHDAY" "=" bymodaylist )
    //                    / ( "BYYEARDAY" "=" byyrdaylist )
    //                    / ( "BYWEEKNO" "=" bywknolist )
    //                    / ( "BYMONTH" "=" bymolist )
    //                    / ( "BYSETPOS" "=" bysplist )
    //                    / ( "WKST" "=" weekday )
		$times = array();
		// print_r($rrules);
		$FREQ = $rrules['FREQ'];
		$COUNT = isset($rrules['COUNT'])?(int)$rrules['COUNT']:null;
		$UNTIL = isset($rrules['UNTIL'])?strtotime($rrules['UNTIL']):null;


		$times = array();
		$rtimes = $this->calcurateRrulesByRrules($rrules,$currTime);
		// print_r($rtimes);
		$currTime = $rtimes[0];
		$times = array_merge($times,$rtimes);

		//규칙에는 두 조건 모두 없으면 무한. 그래서 6회로 제한한다.
		if($UNTIL===null && $COUNT===null){
			$COUNT = 5;
		}

		if($UNTIL != null){
			while($currTime <= $UNTIL){
				if($FREQ =='YEARLY'){
					$currTime = strtotime("+1 year", $currTime);
				}else if($FREQ =='MONTHLY'){
					$currTime = strtotime("+1 month", $currTime);
				}else if($FREQ =='WEEKLY'){
					$currTime = strtotime("+1 week", $currTime);
				}
				$rtimes = $this->calcurateRrulesByRrules($rrules,$currTime);
				// print_r($rtimes);
				if(max($rtimes) > $UNTIL ){ break; }
				$currTime = max($rtimes);
				$times = array_merge($times,$rtimes);
			}
		}else if($COUNT != null){
			for($i=0,$m=($COUNT-1);$i<$m;$i++){
				if($FREQ =='YEARLY'){
					$currTime = strtotime("+1 year", $currTime);
				}else if($FREQ =='MONTHLY'){
					$currTime = strtotime("+1 month", $currTime);
				}else if($FREQ =='WEEKLY'){
					$currTime = strtotime("+1 week", $currTime);
				}
				$rtimes = $this->calcurateRrulesByRrules($rrules,$currTime);
				// print_r($rtimes);
				// echo date('Y-m-d',$rtimes[0]),"\n";
				$currTime = $rtimes[0];
				$times = array_merge($times,$rtimes);
			}
		}
		return $times;
	}
	private function generateTimes(& $links){
		$times = array();
		foreach($links as &$r){
			foreach ($r['times'] as $time) {
				$times[] = array('time'=>$time,'link'=>&$r);
			}
		}
		usort($times,array($this,'sort_fn_generateTimes'));
		return $times;
	}
	private function sort_fn_generateTimes($a,$b){
		if($a['time']>$b['time']){
			return 1;
		}else if($a['time']<$b['time']){
			return -1;
		}
		return 0;
	}
	public function searchByDate($dateST,$dateED=null){
		$timeST = strtotime(date('Y-m-d 00:00:00',strtotime($dateST)));
		if($dateED!=null){
			$timeED = strtotime(date('Y-m-d 23:59:59',strtotime($dateED)));
		}else{
			$timeED = strtotime(date('Y-m-d 23:59:59',$timeST));
		}
		$rs = $this->searchByUnixtime($timeST,$timeED);
		foreach ($rs as & $r) {
			$r['date'] = date('Y-m-d',$r['time']);
		}
		return $rs;
	}
	public function searchByUnixtime($timeST,$timeED){
		if($this->debug){
			echo "[debug] {$timeST} ~ {$timeED}\n";
			echo '[debug] '.date('Y-m-d H:i:s',$timeST).' ~ '.date('Y-m-d H:i:s',$timeED)."\n";
		}
		$res = array();
		// print_r($this->times);exit;
		foreach ($this->times as $r) {
			if($timeST<=$r['time'] && $r['time']<=$timeED){
				$res[] = array('time'=>$r['time'],'VEVENT'=>&$r['link']['VEVENT']);
			}
			if($r['time'] > $timeED){
				break;
			}
		}
		unset($r);
		return $res;
	}
	public function toObjectAll(){
		$VCALENDAR = $this->VCALENDAR;
		$links = $this->links;
		return array(
			'VCALENDAR'=>$this->VCALENDAR,
			'links'=>$this->links
		);
	}
	public function toObject(){
		$VCALENDAR = $this->VCALENDAR;
		$links = $this->links;
		return array(
			'VCALENDAR'=>$this->VCALENDAR,
			'VEVENTs'=>$this->VEVENTs
		);
	}
	public function toIcs(){
		$res = array();
		$res[] = 'BEGIN:VCALENDAR';
		$keys_str = implode('|',array_keys($this->VCALENDAR));
		foreach ($this->VCALENDAR as $k => $v) {
			if(strpos($keys_str,$k.';')!==false){ continue;}
			$res[] =$this->str_split($k.':'.$this->encode($v));
		}
		$keys_str = implode('|',array_keys($this->VCALENDAR));
		foreach ($this->VEVENTs as $VEVENT) {
			$res[] = 'BEGIN:VEVENT';
			$keys_str = implode('|',array_keys($VEVENT));
			foreach ($VEVENT as $k => $v) {
				if(strpos($keys_str,$k.';')!==false){ continue;}
				if($k =='RRULE'){
					$res[] =$this->str_split($k.':'.$v);
				}else{
					$res[] =$this->str_split($k.':'.$this->encode($v));
				}
			}
			$res[] = 'END:VEVENT';
		}
		$res[] = 'END:VCALENDAR';
		return implode("\r\n",$res);
	}
}
