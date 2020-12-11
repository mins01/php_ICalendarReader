<?
require('../src/ICalendarReader.php');


$icr = new ICalendarReader();
$icr->debug = true;


$files = array(
'kr_ko.ics',
'ca_en.ics',
'cn_zh.ics',
'de_de.ics',
'gb_en.ics',
'jp_ja.ics',
'tw_zh.ics',
'us_en.ics');
header('Content-type: plain/text');
foreach ($files as $file) {
	$icr->load(file_get_contents('ics/'.$file));
	echo "-------------------\n";
	echo 'FILENAME: '.$file."\n";
	echo 'CALNAME: '.$icr->VCALENDAR['X-WR-CALNAME']."\n";
	echo "-------------------\n";
	echo "search: 2021-01-01 ~ 2021-12-31\n";
	$rs = $icr->searchByDate('2021-01-01','2021-12-31');
	foreach ($rs as $r) {
		echo date('Y-m-d',$r['time']).': '.$r['VEVENT']['SUMMARY']."\n";
	}
	echo "\n\n";
}
