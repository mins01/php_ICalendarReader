<?
require('../src/ICalendarReader.php');


$icr = new ICalendarReader();
$icr->debug = true;
$icr->load(file_get_contents('ics/kr_ko.ics'));
// $icr->load(file_get_contents('https://p03-calendars.icloud.com/holidays/kr_ko.ics'));

header('Content-type: plain/text');
echo $icr->$VCALENDAR['X-WR-CALNAME']."\n";
echo "-------------------\n";
echo "search: 2021-01-01\n";
$rs = $icr->searchByUnixtime(mktime(0,0,0,1,1,2021),mktime(23,59,59,1,1,2021));
foreach ($rs as $r) {
	echo date('Y-m-d',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
}
echo "-------------------\n";
echo "search: 2021-01-01 ~ 2021-12-31\n";
$rs = $icr->searchByUnixtime(mktime(0,0,0,1,1,2021),mktime(23,59,59,12,31,2021));
foreach ($rs as $r) {
	echo date('Y-m-d',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
}
echo "-------------------\n";
echo "search: 2022-01-01\n";
$rs = $icr->searchByDate('2022-01-01');
foreach ($rs as $r) {
	echo date('Y-m-d',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
}
echo "-------------------\n";
echo "search: 2022-01-01 ~ 2022-12-31\n";
$rs = $icr->searchByDate('2022-01-01','2022-12-31');
foreach ($rs as $r) {
	echo date('Y-m-d',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
}


$icr->load(file_get_contents('ics/us_en.ics'));
echo $icr->$VCALENDAR['X-WR-CALNAME']."\n";
echo "-------------------\n";
echo "search: 2021-01-01\n";
$rs = $icr->searchByUnixtime(mktime(0,0,0,1,1,2021),mktime(23,59,59,1,1,2021));
foreach ($rs as $r) {
	echo date('Y-m-d',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
}
echo "-------------------\n";
echo "search: 2021-01-01 ~ 2021-12-31\n";
$rs = $icr->searchByUnixtime(mktime(0,0,0,1,1,2021),mktime(23,59,59,12,31,2021));
foreach ($rs as $r) {
	echo date('Y-m-d',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
}
echo "-------------------\n";
echo "search: 2022-01-01\n";
$rs = $icr->searchByDate('2022-01-01');
foreach ($rs as $r) {
	echo date('Y-m-d',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
}
echo "-------------------\n";
echo "search: 2022-01-01 ~ 2022-12-31\n";
$rs = $icr->searchByDate('2022-01-01','2022-12-31');
foreach ($rs as $r) {
	echo date('Y-m-d',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
}
