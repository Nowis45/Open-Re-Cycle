<?php
require("common.php");

function response($message,$error=0,$additional="",$log=1)
{
   global $db;
   $json=array("error"=>$error,"content"=>$message);
   if (is_array($additional))
      {
      foreach ($additional as $key=>$value)
         {
         $json[$key]=$value;
         }
      }
   $json=json_encode($json);
   if ($log==1 AND $message)
      {
      if (isset($_COOKIE["loguserid"]))
         {
         $userid=$db->conn->real_escape_string(trim($_COOKIE["loguserid"]));
         }
      else $userid=0;
      $number=getphonenumber($userid);
      logresult($number,$message);
      }
   $db->conn->commit();
   echo $json;
   exit;
}

function rent($userId,$bike,$force=FALSE)
{

   global $db,$forcestack,$watches,$credit;
   $stacktopbike=FALSE;
   $bikeNum = $bike;
   $requiredcredit=$credit["min"]+$credit["rent"]+$credit["longrental"];

   if ($force==FALSE)
      {
      $creditcheck=checkrequiredcredit($userId);
      if ($creditcheck===FALSE)
         {
         response(_('You are below required credit')." ".$requiredcredit.$credit["currency"].". "._('Please, recharge your credit.'),ERROR);
         }
      checktoomany(0,$userId);

      $result=$db->query("SELECT count(*) as countRented FROM bikes where currentUser=$userId");
      $row = $result->fetch_assoc();
      $countRented = $row["countRented"];

      $result=$db->query("SELECT userLimit FROM limits where userId=$userId");
      $row = $result->fetch_assoc();
      $limit = $row["userLimit"];

      if ($countRented>=$limit)
         {
         if ($limit==0)
            {
            response(_('You can not rent any bikes. Contact the admins to lift the ban.'),ERROR);
            }
         elseif ($limit==1)
            {
            response(_('You can only rent')." ".sprintf(ngettext('%d bike','%d bikes',$limit),$limit)." "._('at once').".",ERROR);
            }
         else
            {
            response(_('You can only rent')." ".sprintf(ngettext('%d bike','%d bikes',$limit),$limit)." "._('at once')." "._('and you have already rented')." ".$limit.".",ERROR);
            }
         }

      if ($forcestack OR $watches["stack"])
         {
         $result=$db->query("SELECT currentStand FROM bikes WHERE bikeNum='$bike'");
         $row=$result->fetch_assoc();
         $standid=$row["currentStand"];
         $stacktopbike=checktopofstack($standid);
         if ($watches["stack"] AND $stacktopbike<>$bike)
            {
            $result=$db->query("SELECT standName FROM stands WHERE standId='$standid'");
            $row=$result->fetch_assoc();
            $stand=$row["standName"];
            $user=getusername($userId);
            notifyAdmins(_('Bike')." ".$bike." "._('rented out of stack by')." ".$user.". ".$stacktopbike." "._('was on the top of the stack at')." ".$stand.".",1);
            }
         if ($forcestack AND $stacktopbike<>$bike)
            {
            response(_('Bike')." ".$bike." "._('is not rentable now, you have to rent bike')." ".$stacktopbike." "._('from this stand').".",ERROR);
            }
         }
      }

   $result=$db->query("SELECT currentUser,currentCode FROM bikes WHERE bikeNum=$bikeNum");
   $row=$result->fetch_assoc();
   $currentCode=sprintf("%04d",$row["currentCode"]);
   $currentUser=$row["currentUser"];
   $result=$db->query("SELECT note FROM notes WHERE bikeNum='$bikeNum' AND deleted IS NULL ORDER BY time DESC");
   $note="";
   while ($row=$result->fetch_assoc())
      {
      $note.=$row["note"]."; ";
      }
   $note=substr($note,0,strlen($note)-2); // remove last two chars - comma and space

   $newCode=sprintf("%04d",rand(100,9900)); //do not create a code with more than one leading zero or more than two leading 9s (kind of unusual/unsafe).

   if ($force==FALSE)
      {
      if ($currentUser==$userId)
         {
         response(_('You already rented bike')." ".$bikeNum.". "._('Code is')." ".$currentCode.".",ERROR);
         return;
         }
      if ($currentUser!=0)
         {
         response(_('Bike')." ".$bikeNum." "._('is already rented').".",ERROR);
         return;
         }
      }

   $message='<h3>'._('Bike').' '.$bikeNum.': <span class="label label-primary">'._('Open with code').' '.$currentCode.'.</span></h3>'._('Change code immediately to').' <span class="label label-default">'.$newCode.'</span><br />'._('(open, rotate metal part, set new code, rotate metal part back)').'.';
   if ($note)
      {
      $message.="<br />"._('Reported issue').": <em>".$note."</em>";
      }

   $result=$db->query("UPDATE bikes SET currentUser=$userId,currentCode=$newCode,currentStand=NULL WHERE bikeNum=$bikeNum");
   if ($force==FALSE)
      {
      $result=$db->query("INSERT INTO history SET userId=$userId,bikeNum=$bikeNum,action='RESERVATION',parameter=$newCode");
      }
   else
      {
      $result=$db->query("INSERT INTO history SET userId=$userId,bikeNum=$bikeNum,action='FORCERESERVATION',parameter=$newCode");
      }
   response($message);

}


function returnBike($userId,$bike,$stand,$note="",$force=FALSE)
{

   global $db;
   $bikeNum = intval($bike);
   $stand = strtoupper($stand);

   if ($force==FALSE)
      {
      $result=$db->query("SELECT bikeNum FROM bikes WHERE currentUser=$userId ORDER BY bikeNum");
      $bikenumber=$result->num_rows;

      if ($bikenumber==0)
         {
         response(_('You currently have no rented bikes.'),ERROR);
         }
      }

   if ($force==FALSE)
      {
      $result=$db->query("SELECT currentCode FROM bikes WHERE currentUser=$userId and bikeNum=$bikeNum");
      }
   else
      {
      $result=$db->query("SELECT currentCode FROM bikes WHERE bikeNum=$bikeNum");
      }
   $row=$result->fetch_assoc();
   $currentCode = sprintf("%04d",$row["currentCode"]);

   $result=$db->query("SELECT standId FROM stands WHERE standName='$stand'");
   $row = $result->fetch_assoc();
   $standId = $row["standId"];

   $result=$db->query("UPDATE bikes SET currentUser=NULL,currentStand=$standId WHERE bikeNum=$bikeNum and currentUser=$userId");
   if ($note) addNote($userId,$bikeNum,$note);

   $message = '<h3>'._('Bike').' '.$bikeNum.': <span class="label label-primary">'._('Lock with code').' '.$currentCode.'.</span></h3>';
   $message.= '<br />'._('Please').', <strong>'._('rotate the lockpad to').' <span class="label label-default">0000</span></strong> '._('when leaving').'.';
   if ($note) $message.='<br />'._('You have also reported this problem:').' '.$note.'.';

   if ($force==FALSE)
      {
      $creditchange=changecreditendrental($bikeNum,$userId);
      if (iscreditenabled() AND $creditchange) $message.='<br />'._('Credit change').': -'.$creditchange.getcreditcurrency().'.';
      $result=$db->query("INSERT INTO history SET userId=$userId,bikeNum=$bikeNum,action='RETURN',parameter=$standId");
      }
   else
      {
      $result=$db->query("INSERT INTO history SET userId=$userId,bikeNum=$bikeNum,action='FORCERETURN',parameter=$standId");
      }
   response($message);

}


function where($userId,$bike)
{

   global $db;
   $bikeNum = $bike;

   $result=$db->query("SELECT number,userName,stands.standName FROM bikes LEFT JOIN users on bikes.currentUser=users.userID LEFT JOIN stands on bikes.currentStand=stands.standId where bikeNum=$bikeNum");
   $row = $result->fetch_assoc();
   $phone= $row["number"];
   $userName= $row["userName"];
   $standName= $row["standName"];
   $result=$db->query("SELECT note FROM notes WHERE bikeNum='$bikeNum' AND deleted IS NULL ORDER BY time DESC");
   $note="";
   while ($row=$result->fetch_assoc())
      {
      $note.=$row["note"]."; ";
      }
   $note=substr($note,0,strlen($note)-2); // remove last two chars - comma and space
   if ($note)
      {
      $note=_('Signalement sur le vélo:')." ".$note;
      }

   if ($standName)
      {
      response('<h3>'._('Bike').' '.$bikeNum.' '._('at').' <span class="label label-primary">'.$standName.'</span>.</h3>'.$note);
      }
   else
      {
      response('<h3>'._('Bike').' '.$bikeNum.' '._('rented by').' <span class="label label-primary">'.$userName.'</span>.</h3>'._('Phone').': <a href="tel:+'.$phone.'">+'.$phone.'</a>. '.$note);
      }

}

function addnote($userId,$bikeNum,$message)
{

   global $db;
   $userNote=$db->conn->real_escape_string(trim($message));

   $result=$db->query("SELECT userName,number from users where userId='$userId'");
   $row=$result->fetch_assoc();
   $userName=$row["userName"];
   $phone=$row["number"];
   $result=$db->query("SELECT stands.standName FROM bikes LEFT JOIN users on bikes.currentUser=users.userID LEFT JOIN stands on bikes.currentStand=stands.standId WHERE bikeNum=$bikeNum");
   $row=$result->fetch_assoc();
   $standName=$row["standName"];
   if ($standName!=NULL)
      {
      $bikeStatus=_('at')." ".$standName;
      }
      else
      {
      $bikeStatus=_('used by')." ".$userName." +".$phone;
      }
   $db->query("INSERT INTO notes SET bikeNum='$bikeNum',userId='$userId',note='$userNote'");
   $noteid=$db->conn->insert_id;
   notifyAdmins(_('Note #').$noteid.": b.".$bikeNum." (".$bikeStatus.") "._('by')." ".$userName."/".$phone.":".$userNote);

}

function listbikes($stand)
{
   global $db,$forcestack;

   $stacktopbike=FALSE;
   $stand=$db->conn->real_escape_string($stand);
   if ($forcestack)
      {
      $result=$db->query("SELECT standId FROM stands WHERE standName='$stand'");
      $row=$result->fetch_assoc();
      $stacktopbike=checktopofstack($row["standId"]);
      }
   $result=$db->query("SELECT bikeNum FROM bikes LEFT JOIN stands ON bikes.currentStand=stands.standId WHERE standName='$stand'");
   while($row=$result->fetch_assoc())
      {
      $bikenum=$row["bikeNum"];
      $result2=$db->query("SELECT note FROM notes WHERE bikeNum='$bikenum' AND deleted IS NULL ORDER BY time DESC");
      $note="";
      while ($row=$result2->fetch_assoc())
         {
         $note.=$row["note"]."; ";
         }
      $note=substr($note,0,strlen($note)-2); // remove last two chars - comma and space
      if ($note)
         {
         $bicycles[]="*".$bikenum; // bike with note / issue
         $notes[]=$note;
         }
      else
         {
         $bicycles[]=$bikenum;
         $notes[]="";
         }
      }
   if (!$result->num_rows)
      {
      $bicycles="";
      $notes="";
      }
   response($bicycles,0,array("notes"=>$notes,"stacktopbike"=>$stacktopbike),0);

}

function liststands()
{
   global $db;
	
	$result=$db->query("SELECT standId,standName,standDescription,standPhoto,serviceTag,placeName,longitude,latitude FROM stands ORDER BY standName");
	
    while($row = $result->fetch_assoc())
      {
		$compte = 0;
		$liste = null;
		$station = $row['standName'];
		$result2=$db->query("SELECT bikeNum FROM bikes LEFT JOIN stands ON bikes.currentStand=stands.standId WHERE standName='$station'"); 
		while($row2 = $result2->fetch_assoc())
		{
			$bikenum=$row2["bikeNum"];
			$compte++;
		  $result3=$db->query("SELECT note FROM notes WHERE bikeNum='$bikenum' AND deleted IS NULL ORDER BY time DESC");
		  $note="";
		  while ($row3=$result3->fetch_assoc())
			 {
			 $note.=$row3["note"]."; ";
			 }	
			if ($note)
			 {
			 $liste.=$row2['bikeNum']."* , "; // bike with note / issue
			 }
			else
			 {
			 $liste.=$row2['bikeNum'].", ";
			 }
		}
		$jsoncontent[]=array("standid"=>$row["standId"],"standname"=>$row["standName"],"standdescription"=>$row["standDescription"],"standphoto"=>$row["standPhoto"],"servicetag"=>$row["serviceTag"],"placename"=>$row["placeName"],"longitude"=>$row["longitude"],"latitude"=>$row["latitude"],"compte"=>$compte,"liste"=>$liste);
	  }
   echo json_encode($jsoncontent);
   
   // TODO change to response function

}

function removenote($bikeNum)
{
   global $db;

   $result=$db->query("UPDATE notes SET deleted=NOW() WHERE bikeNum=$bikeNum");
   response(_('Signalement pour le vélo ')." ".$bikeNum." "._(' supprimé').".");
}

function last($userId,$bike=0)
{

   global $db;
   $bikeNum=intval($bike);
   if ($bikeNum)
      {
      $result=$db->query("SELECT userName,parameter,standName,action,time FROM `history` JOIN users ON history.userid=users.userid LEFT JOIN stands ON stands.standid=history.parameter WHERE bikenum=$bikeNum AND (action NOT LIKE '%CREDIT%') ORDER BY time DESC LIMIT 10");
      $historyInfo="<h3>"._('Vélo')." ".$bikeNum." "._('historique').":</h3><ul>";
      while($row=$result->fetch_assoc())
         {
         $time=strtotime($row["time"]);
         $historyInfo.="<li>".date("d/m H:i",$time)." - ";
         if($row["standName"]!=NULL)
            {
            $historyInfo.=$row["standName"];
            if (strpos($row["parameter"],"|"))
               {
               $revertcode=explode("|",$row["parameter"]);
               $revertcode=$revertcode[1];
               }
            if ($row["action"]=="REVERT") $historyInfo.=' <span class="label label-warning">'._('Revert').' ('.str_pad($revertcode,4,"0",STR_PAD_LEFT).')</span>';
            }
         else
            {
            $historyInfo.=$row["userName"].' (<span class="label label-default">'.str_pad($row["parameter"],4,"0",STR_PAD_LEFT).'</span>)';
            }
         $historyInfo.="</li>";
         }
      $historyInfo.="</ul>";
      }
   else
      {
      $result=$db->query("SELECT bikeNum FROM bikes WHERE currentUser<>''");
      $inuse=$result->num_rows;
      $result=$db->query("SELECT bikeNum,userName,standName,users.userId FROM bikes LEFT JOIN users ON bikes.currentUser=users.userId LEFT JOIN stands ON bikes.currentStand=stands.standId ORDER BY bikeNum");
      $total=$result->num_rows;
      $historyInfo="<h3>"._('Etat actuel des vélos:')."</h3>";
      $historyInfo.="<h4>".sprintf(ngettext('%d vélo','%d vélos',$total),$total).", ".$inuse." "._('en cours d\'utilisation')."</h4><ul>";
      while($row=$result->fetch_assoc())
         {
         $historyInfo.="<li> Vélo : ".$row["bikeNum"]." - ";
         if($row["standName"]!=NULL)
            {
            $historyInfo.=" Station : ".$row["standName"];
            }
         else
            {
            $historyInfo.='<span class="bg-warning"> En cours d\'utilisation par : '.$row["userName"];
            $result2=$db->query("SELECT time FROM history WHERE bikeNum=".$row["bikeNum"]." AND userId=".$row["userId"]." AND action='RETIRER' ORDER BY time DESC");
            $row2=$result2->fetch_assoc();
            $historyInfo.=" emprunté le ".date("d/m à H:i",strtotime($row2["time"])).'</span>';
            }
         $result2=$db->query("SELECT note FROM notes WHERE bikeNum='".$row["bikeNum"]."' AND deleted IS NULL ORDER BY time DESC");
         $note="";
         while ($row=$result2->fetch_assoc())
            {
            $note.=$row["note"]."; ";
            }
         $note=substr($note,0,strlen($note)-2); // remove last two chars - comma and space
         if ($note) $historyInfo.=" (".$note.")";
         $historyInfo.="</li>";
         }
      $historyInfo.="</ul>";
      }
   response($historyInfo,0,"",0);
}


function userbikes($userId)
{
   global $db;
   if (!isloggedin()) response("");
   $result=$db->query("SELECT bikeNum,currentCode FROM bikes WHERE currentUser=$userId ORDER BY bikeNum");
   while ($row=$result->fetch_assoc())
      {
      $bikenum=$row["bikeNum"];
      $bicycles[]=$bikenum;
      $codes[]=str_pad($row["currentCode"],4,"0",STR_PAD_LEFT);
      $result2=$db->query("SELECT parameter FROM history WHERE bikeNum=$bikenum AND action='RETIRER' ORDER BY time DESC LIMIT 1,1");
      $row=$result2->fetch_assoc();
      $oldcodes[]=str_pad($row["parameter"],4,"0",STR_PAD_LEFT);
      }
   if (!$result->num_rows) $bicycles="";
   if (!isset($codes)) $codes="";
   else $codes=array("codes"=>$codes,"oldcodes"=>$oldcodes);
   response($bicycles,0,$codes,0);
}

function revert($bikeNum)
{

   global $db;

   $standId=0;
   $result=$db->query("SELECT currentUser FROM bikes WHERE bikeNum=$bikeNum AND currentUser IS NOT NULL");
   if (!$result->num_rows)
      {
      response(_('Le vélo')." ".$bikeNum." "._('n\'est pas réservé pour le moment. Retour impossible !'),ERROR);
      return;
      }
   else
      {
      $row=$result->fetch_assoc();
      $revertusernumber=getphonenumber($row["currentUser"]);
      }
   $result=$db->query("SELECT parameter,standName FROM stands LEFT JOIN history ON stands.standId=parameter WHERE bikeNum=$bikeNum AND action IN ('DEPOT','FORCEDEPOT') ORDER BY time DESC LIMIT 1");
   if ($result->num_rows==1)
      {
      $row = $result->fetch_assoc();
      $standId=$row["parameter"];
      $stand=$row["standName"];
      }
   $result=$db->query("SELECT parameter FROM history WHERE bikeNum=$bikeNum AND action IN ('RESERVATION','FORCERESERVATION') ORDER BY time DESC LIMIT 1,1");
   if ($result->num_rows==1)
      {
      $row = $result->fetch_assoc();
      $code=str_pad($row["parameter"],4,"0",STR_PAD_LEFT);
      }
   if ($standId and $code)
      {
      $result=$db->query("UPDATE bikes SET currentUser=NULL,currentStand=$standId,currentCode=$code WHERE bikeNum=$bikeNum");
      $result=$db->query("INSERT INTO history SET userId=0,bikeNum=$bikeNum,action='RETOUR',parameter='$standId|$code'");
      $result=$db->query("INSERT INTO history SET userId=0,bikeNum=$bikeNum,action='RESERVATION',parameter=$code");
      $result=$db->query("INSERT INTO history SET userId=0,bikeNum=$bikeNum,action='DEPOT',parameter=$standId");
      response('<h3>'._('Le vélo').' '.$bikeNum.' '._('a été retourné à la station').' <span class="label label-primary">'.$stand.'</span> '._('avec le code').' <span class="label label-primary">'.$code.'</span>.</h3>');
      sendSMS($revertusernumber,_('Le vélo')." ".$bikeNum." "._('a été retourné. Vous pouvez louer un nouveau vélo.'));
      }
   else
      {
      response(_('Pas de dernière station trouvée pour le vélo')." ".$bikeNum." "._('Retour impossible !'),ERROR);
      }

}

function register($number,$fullname,$email,$existing,$photo)
{
   global $db, $dbpassword, $countrycode, $systemURL;

   global $db, $dbpassword, $countrycode, $systemURL;

   $number=$db->conn->real_escape_string(trim($number));
   $fullname=$db->conn->real_escape_string(trim($fullname));
   $email=$db->conn->real_escape_string(trim($email));
   $photo=$db->conn->real_escape_string(trim($photo));
   $existing=$db->conn->real_escape_string(trim($existing));

    $target_dir = "uploads/";
	$filename = $_FILES["profile_pic"]["name"];
	$file_ext = substr($filename, strripos($filename, '.')); // avoir l'extension du fichier
	$newfilename = $target_dir . $fullname . $file_ext;
   
   $result=$db->query("INSERT INTO users (userName, password, mail, number, privileges, PI, note, recommendations) VALUES ('$fullname', 'pass', '$email', '$number', '0', '$newfilename', '', '')");
   $userId=$db->conn->insert_id;
   $result2=$db->query("INSERT INTO limits (userId, userLimit) VALUES ('$userId', '1')");
   sendSMS($number, 'Bonjour et bienvenue sur le service Cyclovis ! Votre inscription a bien été validée.');
   sendSMS($number, 'Vous pouvez désormais emprunter un vélo en envoyant RETIRER suivi du numéro de vélo par SMS. Pour plus d\'informations sur les commandes envoyez AIDE.');
   header("Location: ".$systemURL."admin.php");
   response(_('L\'utilisateur a ete correctement inscrit.'));
  
}

function login($number,$password)
{
   global $db,$systemURL,$countrycode;

   $number=$db->conn->real_escape_string(trim($number));
   $password=$db->conn->real_escape_string(trim($password));
   $number=str_replace(" ","",$number); $number=str_replace("-","",$number); $number=str_replace("/","",$number);
   if ($number[0]=="0") $number=$countrycode.substr($number,1,strlen($number));
   $altnumber=$countrycode.$number;
   setcookie("loguserid",null,false);
   setcookie("logsession",null,false);
   $result=$db->query("SELECT userId FROM users WHERE (number='$number' OR number='$altnumber') AND password=SHA2('$password',512) AND privileges=7" );
   if ($result->num_rows==1)
      {
      $row=$result->fetch_assoc();
      $userId=$row["userId"];
      $sessionId=hash('sha256',$userId.$number.time());
      $timeStamp=time()+28800;
      $result=$db->query("DELETE FROM sessions WHERE userId='$userId'");
      $result=$db->query("INSERT INTO sessions SET userId='$userId',sessionId='$sessionId',timeStamp='$timeStamp'");
      $db->conn->commit();
      setcookie("loguserid",$userId,false);
      setcookie("logsession",$sessionId,false);
      header("Location: ".$systemURL);
      header("Connection: close");
      exit;
      }
   else{
	   $result2=$db->query("SELECT userId FROM users WHERE (number='$number' OR number='$altnumber') AND privileges=0" );
		if($result2->num_rows==1){
		  header("Location: ".$systemURL."?error=3");
		  header("Connection: close");
		  exit;
		}else{
		  header("Location: ".$systemURL."?error=1");
		  header("Connection: close");
		  exit;
		}
  }

}

function logout()
{
   global $db,$systemURL;
   if (isset($_COOKIE["loguserid"]) AND isset($_COOKIE["logsession"]))
      {  
      $userid=$db->conn->real_escape_string(trim($_COOKIE["loguserid"]));
      $session=$db->conn->real_escape_string(trim($_COOKIE["logsession"]));
      $result=$db->query("DELETE FROM sessions WHERE userId='$userid'");
      $db->conn->commit();
	  $_COOKIE = [];
	  $_SERVER['HTTP_COOKIE'] = [];
      }
   header("HTTP/1.1 301 Moved permanently");
   header("Location: ".$systemURL);
   header("Connection: close");
   exit;
}

function checkprivileges($userid)
{
   global $db;
   $privileges=getprivileges($userid);
   if ($privileges<6)
      {
      response(_('Désolé, cette commande n\'est valable que pour les administrateurs.'),ERROR);
      exit;
      }
}

function smscode($number)
{

   global $db, $gatewayId, $gatewayKey, $gatewaySenderNumber, $connectors;
   srand();

   $number=normalizephonenumber($number);
   $number=$db->conn->real_escape_string($number);
   $userexists=0;
   $result=$db->query("SELECT userId FROM users WHERE number='$number'");
   if ($result->num_rows) $userexists=1;

   $smscode=chr(rand(65,90)).chr(rand(65,90))." ".rand(100000,999999);
   $smscodenormalized=str_replace(" ","",$smscode);
   $checkcode=md5("WB".$number.$smscodenormalized);
   if (!$userexists) $text=_('Enter this code to register:')." ".$smscode;
   else $text=_('Enter this code to change password:')." ".$smscode;
   $text=$db->conn->real_escape_string($text);

   if (!issmssystemenabled()) $result=$db->query("INSERT INTO sent SET number='$number',text='$text'");
   $result=$db->query("INSERT INTO history SET userId=0,bikeNum=0,action='REGISTER',parameter='$number;$smscodenormalized;$checkcode'");

   if (DEBUG===TRUE)
      {
      response($number,0,array("checkcode"=>$checkcode,"smscode"=>$smscode,"existing"=>$userexists));
      }
   else
      {
      sendSMS($number,$text);
      if (issmssystemenabled()==TRUE) response($number,0,array("checkcode"=>$checkcode,"existing"=>$userexists));
      else response($number,0,array("checkcode"=>$checkcode,"existing"=>$userexists));
      }
}

function trips($userId,$bike=0)
{

   global $db;
   $bikeNum=intval($bike);
   if ($bikeNum)
      {
      $result=$db->query("SELECT longitude,latitude FROM `history` LEFT JOIN stands ON stands.standid=history.parameter WHERE bikenum=$bikeNum AND action='RETURN' ORDER BY time DESC");
      while($row = $result->fetch_assoc())
         {
         $jsoncontent[]=array("longitude"=>$row["longitude"],"latitude"=>$row["latitude"]);
         }
      }
   else
      {
      $result=$db->query("SELECT bikeNum,longitude,latitude FROM `history` LEFT JOIN stands ON stands.standid=history.parameter WHERE action='RETURN' ORDER BY bikeNum,time DESC");
      $i=0;
      while($row = $result->fetch_assoc())
         {
         $bikenum=$row["bikeNum"];
         $jsoncontent[$bikenum][]=array("longitude"=>$row["longitude"],"latitude"=>$row["latitude"]);
         }
      }
   echo json_encode($jsoncontent); // TODO change to response function
}

function getuserlist()
{
   global $db;
   $result=$db->query("SELECT users.userId,username,mail,number,privileges,PI,credit,userLimit FROM users LEFT JOIN credit ON users.userId=credit.userId LEFT JOIN limits ON users.userId=limits.userId ORDER BY username");
   while($row = $result->fetch_assoc())
      {
      $jsoncontent[]=array("userid"=>$row["userId"],"username"=>$row["username"],"mail"=>$row["mail"],"number"=>$row["number"],"privileges"=>$row["privileges"],"photo"=>$row["PI"],"credit"=>$row["credit"],"limit"=>$row["userLimit"]);
      }
   echo json_encode($jsoncontent);// TODO change to response function
}

function getuserstats()
{
   global $db;
   $result=$db->query("SELECT users.userId,username,count(action) AS count FROM users LEFT JOIN history ON users.userId=history.userId WHERE history.userId IS NOT NULL GROUP BY username ORDER BY count DESC");
   while($row = $result->fetch_assoc())
      {
      $result2=$db->query("SELECT count(action) AS rentals FROM history WHERE action='RETIRER' AND userId=".$row["userId"]);
      $row2=$result2->fetch_assoc();
      $result2=$db->query("SELECT count(action) AS returns FROM history WHERE action='DEPOT' AND userId=".$row["userId"]);
      $row3=$result2->fetch_assoc();
      $jsoncontent[]=array("userid"=>$row["userId"],"username"=>$row["username"],"count"=>$row["count"],"rentals"=>$row2["rentals"],"returns"=>$row3["returns"]);
      }
   echo json_encode($jsoncontent);// TODO change to response function
}

function getusagestats()
{
   global $db;
   $result=$db->query("SELECT count(action) AS count,DATE(time) AS day,action FROM history WHERE userId IS NOT NULL AND action IN ('RETIRER', 'DEPOT') GROUP BY day,action ORDER BY day DESC LIMIT 60");
   while($row=$result->fetch_assoc())
      {
      $jsoncontent[]=array("day"=>$row["day"],"count"=>$row["count"],"action"=>$row["action"]);
      }
   echo json_encode($jsoncontent);// TODO change to response function
}

function edituser($userid)
{
   global $db;
   $result=$db->query("SELECT users.userId,userName,mail,number,privileges,userLimit,credit FROM users LEFT JOIN limits ON users.userId=limits.userId LEFT JOIN credit ON users.userId=credit.userId WHERE users.userId=".$userid);
   $row=$result->fetch_assoc();
   $jsoncontent=array("userid"=>$row["userId"],"username"=>$row["userName"],"email"=>$row["mail"],"phone"=>$row["number"],"privileges"=>$row["privileges"],"limit"=>$row["userLimit"],"credit"=>$row["credit"]);
   echo json_encode($jsoncontent);// TODO change to response function
}

function removeuser($userid)
{
   global $db;
   $result=$db->query("SELECT users.userId,userName FROM users LEFT JOIN bikes ON users.userId=bikes.currentUser WHERE users.userId='$userid' AND bikes.currentUser=".$userid);
   if ($result->num_rows>=1){
      $row=$result->fetch_assoc();
      response(_('Désolé, l\'utilisateur')." ".$row['userName']." "._('possède un vélo en cours d\'utilisation').".",1);
   } else {
      $result=$db->query("DELETE FROM limits WHERE userId='$userid'");
      $result=$db->query("DELETE FROM sessions WHERE userId='$userid'");
      $result=$db->query("DELETE FROM users WHERE userId='$userid'");
      $db->conn->commit();
      response(_('L\'utilisateur')." ".$row['userName']." "._('a été supprimé').".");
   }
}


function saveuser($userid,$username,$email,$phone,$privileges,$limit)
{
   global $db;
   $result=$db->query("UPDATE users SET username='$username',mail='$email',privileges='$privileges' WHERE userId=".$userid);
   if ($phone) $result=$db->query("UPDATE users SET number='$phone' WHERE userId=".$userid);
   $result=$db->query("UPDATE limits SET userLimit='$limit' WHERE userId=".$userid);
   response(_('Informations sur l\'utilisateur')." ".$username." "._('mises à jour').".");
}

function addcredit($userid,$creditmultiplier)
{
   global $db, $credit;
   $requiredcredit=$credit["min"]+$credit["rent"]+$credit["longrental"];
   $addcreditamount=$requiredcredit*$creditmultiplier;
   $result=$db->query("UPDATE credit SET credit=credit+".$addcreditamount." WHERE userId=".$userid);
   $result=$db->query("INSERT INTO history SET userId=$userid,action='CREDITCHANGE',parameter='".$addcreditamount."|add+".$addcreditamount."'");
   $result=$db->query("SELECT userName FROM users WHERE users.userId=".$userid);
   $row=$result->fetch_assoc();
   response(_('Added')." ".$addcreditamount.$credit["currency"]." "._('credit for')." ".$row["userName"].".");
}

function resetpassword($number)
{
   global $db, $systemname, $systemrules, $systemURL;

   $result=$db->query("SELECT mail,userName FROM users WHERE number='$number'");
   if (!$result->num_rows) response(_('No such user found.'),1);
   $row=$result->fetch_assoc();
   $email=$row["mail"];
   $username=$row["userName"];

   $subject = _('Password reset');

   mt_srand(crc32(microtime()));
   $password=substr(md5(mt_rand().microtime().$email),0,8);

   $result=$db->query("UPDATE users SET password=SHA2('$password',512) WHERE number='".$number."'");

   $names=preg_split("/[\s,]+/",$username);
   $firstname=$names[0];
   $message=_('Hello').' '.$firstname.",\n\n".
   _('Your password has been reset successfully.')."\n\n".
   _('Your new password is:')."\n".$password;

   sendEmail($email, $subject, $message);
   response(_('Your password has been reset successfully.').' '._('Check your email.'));
}

function mapgetmarkers()
{
   global $db;

   $jsoncontent=array();
   $result=$db->query("SELECT standId,count(bikeNum) AS bikecount,standDescription,standName,standPhoto,longitude AS lon, latitude AS lat FROM stands LEFT JOIN bikes on bikes.currentStand=stands.standId WHERE stands.serviceTag=0 GROUP BY standName ORDER BY standName");
   while($row = $result->fetch_assoc())
      {
      $jsoncontent[]=$row;
      }
   echo json_encode($jsoncontent); // TODO proper response function
}

function mapgetlimit($userId)
{
   global $db;

   if (!isloggedin()) response("");
   $result=$db->query("SELECT count(*) as countRented FROM bikes where currentUser=$userId");
   $row = $result->fetch_assoc();
   $rented= $row["countRented"];

   $result=$db->query("SELECT userLimit FROM limits where userId=$userId");
   $row = $result->fetch_assoc();
   $limit = $row["userLimit"];

   $currentlimit=$limit-$rented;

   $usercredit=0;
   $usercredit=getusercredit($userId);

   echo json_encode(array("limit"=>$currentlimit,"rented"=>$rented,"usercredit"=>$usercredit));
}

function mapgeolocation ($userid,$lat,$long)
{
   global $db;

   $result=$db->query("INSERT INTO geolocation SET userId='$userid',latitude='$lat',longitude='$long'");

   response("");

}

// TODO for admins: show bikes position on map depending on the user (allowed) geolocation, do not display user bikes without geoloc

?>
