<?php
require_once('smsGatewayV4.php');


	$name = 'Simon';
	$number="+33624776994";
	$event = 'Recieved';
	$filterType = 'contains';
	$filter = 'hello';
	$method = 'HTTP';
	$action = 'http://www.evo-pods.eu/cyclovis/OpenSourceBikeShare-breakthrough/connectors/sendSms.php';
	$secret = 'nowis1102';
	$id = 12684128;
	$iddevice = 94134;
	$options=[];
	
	$smsGateway = new SmsGateway("eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhZG1pbiIsImlhdCI6MTUyOTQzMDcxNSwiZXhwIjo0MTAyNDQ0ODAwLCJ1aWQiOjUzNTI4LCJyb2xlcyI6WyJST0xFX1VTRVIiXX0.JiP9xJjqqDG_ygodjIYJt3SuOekqjCJQDoY_uiCNFxI");
	$result = $smsGateway->getCallback($iddevice);

	echo json_encode($result);
?>