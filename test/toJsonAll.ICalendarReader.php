<?
require('../src/ICalendarReader.php');


$icr = new ICalendarReader();
// $icr->debug = true;
header('Content-type: application/json');
$icr->load(file_get_contents('ics/_test.ics'));

if(defined('JSON_ERROR_NONE')){ // PHP 5.3 이상
	$jsonOpt=0;
	if(defined('JSON_UNESCAPED_UNICODE')){
		$jsonOpt += JSON_UNESCAPED_UNICODE;
	}
	if(defined('JSON_PRETTY_PRINT')){
		$jsonOpt += JSON_PRETTY_PRINT;
	}
	echo json_encode($icr->toObjectAll(),$jsonOpt);
}else{
	echo json_encode($icr->toObjectAll());
}
