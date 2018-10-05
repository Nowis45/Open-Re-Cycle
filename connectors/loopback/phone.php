<?php
/*
CSS bubbles from:
https://stackoverflow.com/questions/19400183/how-to-style-chat-bubble-in-iphone-classic-style-using-css-only
*/
 // including international dialing code, no plus, no zeroes
if (isset($_POST["body"]))
   {
	$usenumber=$_POST['sender'];
   $dirname=dirname($_SERVER['REQUEST_URI']);
   $dirname=str_replace("/connectors/loopback","",$dirname);
   fopen("http://".$_SERVER['HTTP_HOST'].$dirname."/receive.php?sms_text=".urlencode($_POST["body"])."&sms_uuid=test&sender=".$usenumber."&receive_time=".urlencode(date("Y-m-d H:i:s")),"r");
   }
$sms=file_get_contents("loopback.log");
$lines=explode("\n",$sms);
echo("zog");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Dummy phone (SMS loopback tester)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="../../css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="../../css/bootstrap-theme.min.css" />
<link rel="stylesheet" type="text/css" href="loopback.css" />
</head>
<body>
<div class="container">
<div class="commentArea col-xs-12 col-sm-12 col-md-12 col-lg-12">
<?php
$lines=array_slice($lines,-5);
foreach ($lines as $text)
   {
   $parts=explode("|~",urldecode($text));
   $parts[0]=wordwrap($parts[0],50, "<br />",TRUE);
   if ($parts[0]=="<") echo '<div class="bubbledRight"><em>',$parts[1],'</em><br />',$parts[2],'</div>';
   if ($parts[0]==">") echo '<div class="bubbledLeft"><em>',$parts[1],'</em><br />',$parts[2],'</div>';
   }
?>
   </div>
   <form method="post" id="message" action="phone.php">
   <input type="text" class="form-control" name="body"></textarea>
   <input type="submit" value="Send message" class="btn btn-primary">
   </form>
   <form action="phone.php">
   <input type="submit" value="Refresh" class="btn btn-success">
   </form>
</div>
</body>
</html>