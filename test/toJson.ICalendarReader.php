<?
require('../src/ICalendarReader.php');


$icr = new ICalendarReader();
// $icr->debug = true;
header('Content-type: application/json');
$icr->load(file_get_contents('ics/_test.ics'));
echo $icr->toJson(JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
