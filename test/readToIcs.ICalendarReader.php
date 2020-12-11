<?
require('../src/ICalendarReader.php');


$icr = new ICalendarReader();
// $icr->debug = true;
header('Content-type: application/json');
$icr->load(file_get_contents('_toIcs.ics'));
echo 'CALNAME: '.$icr->VCALENDAR['X-WR-CALNAME']."\n";
echo "-------------------\n";
// echo "search: 2022-01-01 ~ 2022-12-31\n";
$rs = $icr->searchByDate('2019-01-01','2022-12-31');
foreach ($rs as $r) {
	$ts = explode(',',$r['VEVENT']['COMMENT']);
	$dt = date('Y-m-d',$r['time']);
	assert(in_array($dt,$ts),"'{$r['VEVENT']['SUMMARY']}': [{$dt}] is not included [{$r['VEVENT']['COMMENT']}]");
	echo date('Y-m-d D / o-\WW',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
}
