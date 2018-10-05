
<?php
require_once('smsGatewayV4.php');

function send($number,$text){
	$phone_number = "$number";
	$message = "$text";
	$deviceID = 94134;

	$options = [];

	$smsGateway = new SmsGateway("eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhZG1pbiIsImlhdCI6MTUyOTQzMDcxNSwiZXhwIjo0MTAyNDQ0ODAwLCJ1aWQiOjUzNTI4LCJyb2xlcyI6WyJST0xFX1VTRVIiXX0.JiP9xJjqqDG_ygodjIYJt3SuOekqjCJQDoY_uiCNFxI");
	$result = $smsGateway->sendMessageToNumber($phone_number, $message, $deviceID, $options);

	echo json_encode($result);
}


$number = "+33677586759";
$text = "allso";
  if (isset($_POST["body"])) $text=$_POST["body"];
  if (isset($_POST["sender"])) $number=$_POST["sender"];
send($number, $number);


?>
