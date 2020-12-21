<?
require('../src/ICalendarReader.php');


$icr = new ICalendarReader();
$icr->debug = true;
// $icr->load(file_get_contents('ics/kr_ko.ics'));
// $icr->load(file_get_contents('https://p03-calendars.icloud.com/holidays/kr_ko.ics'));

header('Content-type: plain/text');

$icr->load(file_get_contents('ics/_test.ics'));
echo 'CALNAME: '.$icr->VCALENDAR['X-WR-CALNAME']."\n";
echo "-------------------\n";
// echo "search: 2022-01-01 ~ 2022-12-31\n";
$rs = $icr->searchByDate('2019-01-01','2030-12-31');
foreach ($rs as $r) {
	$ts = explode(',',$r['VEVENT']['COMMENT']);
	$dt = date('Y-m-d',$r['time']);
	if(!assert(in_array($dt,$ts))){
		echo "'{$r['VEVENT']['SUMMARY']}': [{$dt}] is not included [{$r['VEVENT']['COMMENT']}]\n";
	}
	echo date('Y-m-d D / o-\WW',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
}
