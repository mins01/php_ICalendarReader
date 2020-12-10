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
		$this->times = $rs['times'];
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
			$t = explode(':',trim($v));
			$val = trim($t[1]);
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
		$times = $this->generateTimes($links);
		return array('VCALENDAR'=>$VCALENDAR,'VEVENTs'=>$VEVENTs,'links'=>$links,'times'=>$times);
	}
	/**
	 * recursiveLinks
	 * @param  & array      $links links(시간정보등의 제어용 정보) 정보 배열
	 * @return null
	 */
	private function recursiveLinks(& $links){
		foreach ($links as & $link) {
			if(!isset($link['rrules'])){ continue; }
			$rrules = $link['rrules'];
			$freq = $rrules['FREQ'];
			$count = isset($rrules['COUNT'])?(int)$rrules['COUNT']:null;
			$until = isset($rrules['UNTIL'])?strtitime($rrules['UNTIL']):null;
			$currTime = $link['timeStart'];
			if($count != null){
				for($i=0,$m=$count;$i<$m;$i++){
					switch($freq){
						case 'YEARLY':
							$t = strtotime("+1 year", $currTime);
							$link['times'][]=$t;
							$currTime = $t;
						break;
						case 'MONTHLY':
							$t = strtotime("+1 month", $currTime);
							$link['times'][]=$t;
							$currTime = $t;
						break;
						case 'WEEKLY':
							$t = strtotime("+1 week", $currTime);
							$link['times'][]=$t;
							$currTime = $t;
						break;
					}
				}
			}else if($until != null){

			}
		}
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
		return $this->searchByUnixtime($timeST,$timeED);
	}
	public function searchByUnixtime($timeST,$timeED)
	{
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
}
