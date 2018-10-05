<?php
require("common.php");

function help($number)
{
 //  global $db;
  // $userid=getUser($number);
   //$privileges=getprivileges($userid);

     $message="Liste des commandes:\n";
      if (iscreditenabled()) $message.="CREDIT\n";
      $message.="1 - RETIRER suivi du numéro du vélo\n 2 - DEPOSER suivi numéro du vélo et de la lettre de la station"; 
      sendSMS($number,$message);
      $message2.="3 - En cas de soucis technique sur un vélo : SIGNALER suivi du numéro de vélo et de la description du problème en quelques mots\n  ";
      sendSMS($number,$message2);
	  $message3.="4 - Pour la liste des vélos disponibles : LISTE suivi de la lettre de la station\n 5 - Pour connaitre la localisation d'une station : INFO suivi de la lettre";
      sendSMS($number,$message3);
	  $message4.=" En cas d'urgence appelez le 0669007757 pendant les horaires d'ouverture du service pour obtenir de l'assistance";
	   sendSMS($number,$message4);
}

function unknownCommand($number,$command)
{
   global $db;
   sendSMS($number,_('Désolé, je n\'ai pas compris la commande :')." ".$command." "._('Si vous avez besoin d\'aide, envoyez :')." AIDE");
}

/**
 * @deprecated, call getuserid() directly
 */
function getUser($number)
{
   return getuserid($number);
}

function validateNumber($number)
{
    if (getUser($number))
   return true;
    else
   return false;
}


function info($number,$stand)
{
        global $db;
        $stand = strtoupper($stand);

        if (!preg_match("/^[A-Z]+[0-9]*$/",$stand))
        {
                sendSMS($number,_('Désolé, je ne reconnais pas la station')." '".$stand."' "._('Chaque station est identifiée par la lettre indiquée sur le panneau.'));
                return;
        }
        $result=$db->query("SELECT standId FROM stands where standName='$stand'");
                if ($result->num_rows!=1)
                {
                        sendSMS($number,_('Désolé, la station')." '$stand' "._('n\'existe pas.'));
                        return;
                }
                $row =$result->fetch_assoc();
                $standId =$row["standId"];
        $result=$db->query("SELECT * FROM stands where standname='$stand'");
                $row =$result->fetch_assoc();
                $standDescription=$row["standDescription"];
                //$standPhoto=$row["standPhoto"];
                $standLat=round($row["latitude"],5);
                $standLong=round($row["longitude"],5);
                $message="Station ".$stand;
                if ($standLong AND $standLat) $message.=", les coordonnées GPS sont : ".$standLat.",".$standLong;
                //if ($standPhoto) $message.=", ".$standPhoto;
                sendSMS($number,$message);

}

/** Validate received SMS - check message pour les arguments demandés
 * @param string $number numéro de l'envoyeur
 * @param int $receivedargumentno nombre d'arguments reçus
 * @param int $requiredargumentno nombre d'arguments demandés
 * @param string $errormessage message d'erreur à envoyer en cas de mismatch
**/
function validateReceivedSMS($number,$receivedargumentno,$requiredargumentno,$errormessage)
{
   global $db, $sms;
   if ($receivedargumentno<$requiredargumentno)
      {
      sendSMS($number,_('Désolé, je n\'ai pas comrpris. Utilisez la commande')." ".$errormessage);
      $sms->Respond();
      exit;
      }
   // si plus d'arguments que demandé, ils seront ignorés
   return TRUE;
}

function rent($number,$bike,$force=FALSE)
{

	global $db,$forcestack,$watches,$credit;
	$stacktopbike=FALSE;
   $userId = getUser($number);
   $bikeNum = intval($bike);
   $requiredcredit=$credit["min"]+$credit["rent"]+$credit["longrental"];

   $result=$db->query("SELECT note FROM notes WHERE bikeNum=$bikeNum AND deleted IS NULL ORDER BY time DESC LIMIT 1");
   if(!$result->num_rows){
	   if ($force==FALSE)
			   {
			   $creditcheck=checkrequiredcredit($userId);
				if ($creditcheck===FALSE)
				   {
				   $result=$db->query("SELECT credit FROM credit WHERE userId=$userId");
				   $row=$result->fetch_assoc();
				   sendSMS($number,_('S\'il vous plait, rechargez vos crédits avant de louer un vélo :')." ".$row["credit"].$credit["currency"].". "._('Credits nécessaires:')." ".$requiredcredit.$credit["currency"].".");
				   return;
				   }

			 checktoomany(0,$userId);

			 $result=$db->query("SELECT count(*) as countRented FROM bikes where currentUser=$userId");
					  $row =$result->fetch_assoc();
					  $countRented =$row["countRented"];

			 $result=$db->query("SELECT userLimit FROM limits where userId=$userId");
					  $row =$result->fetch_assoc();
					  $limit =$row["userLimit"];

			 if ($countRented >=$limit)
			 {
					  if ($limit==0)
						 {
						 sendSMS($number,_('Désolé ! Vous ne pouvez pas reserver de vélo. Contactez le service Cyclovis pour lever les restrictions.'));
						 }
					  elseif ($limit==1)
						 {
						 sendSMS($number,_('Désolé, vous ne pouvez réserver qu\'')." ".sprintf(ngettext('%d vélo','%d bikes',$limit),$limit)." "._('à la fois').".");
						 }
					  else
						 {
						 sendSMS($number,_('Désolé, vous ne pouvez réserver qu\'')." ".sprintf(ngettext('%d vélo','%d bikes',$limit),$limit)." "._('à la fois')." "._('et vous en avez déjà réservé')." ".$limit.".");
						 }

					  return;
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
				   //notifyAdmins(_('Le vélo')." ".$bike." "._('loué par')." ".$user.". ".$stacktopbike." "._('était à la station')." ".$stand.".",ERROR);
				   }
				if ($forcestack AND $stacktopbike<>$bikeNum)
				   {
				   response(_('Désolé, le vélo')." ".$bike." "._('n\'est pas disponible. Voici les vélos disponibles à cette station :')." ".$stacktopbike.".",ERROR);
				   return;
				   }
				}
			 }

	   $result=$db->query("SELECT currentUser,currentCode FROM bikes WHERE bikeNum=$bikeNum");
	   if($result->num_rows!=1)
		  {
		  sendSMS($number,"Désolé, le vélo $bikeNum n'existe pas.");
		  return;
		  }
	   $row =$result->fetch_assoc();
	   $currentCode = sprintf("%04d",$row["currentCode"]);
	   $currentUser=$row["currentUser"];
	   $result=$db->query("SELECT note FROM notes WHERE bikeNum=$bikeNum AND deleted IS NULL ORDER BY time DESC LIMIT 1");
	   $row=$result->fetch_assoc();
	   $note=$row["note"];
	   if ($currentUser)
		  {
		  $result=$db->query("SELECT number FROM users WHERE userId=$currentUser");
		  $row =$result->fetch_assoc();
		  $currentUserNumber =$row["number"];
		  }

	   $newCode = sprintf("%04d",rand(100,9900));// ne pas créer de codes avec un zéro en premier ou plus de deux 9 en premier (peu sûr).

	   if ($force==FALSE)
			  {
				if ($currentUser==$userId)
				{
						 sendSMS($number,_('Désolé, vous avez déjà réservé le vélo')." ".$bikeNum.". "._('Le code est')." ".$currentCode.". "._('Déposez le vélo avec la commande:')." DEPOSER "._('suivi du numéro de vélo')." "._('et de la lattre de la station').".");
						 return;
				}
				if ($currentUser!=0)
				{
						 sendSMS($number,_('Désolé, le vélo')." ".$bikeNum." "._('est déjà réservé.').".");
						 return;
				}
			 }

	   $message=_('Super ! La réservation pour le vélo')." ".$bikeNum." "._('à été prise en compte avec succès. Vous pouvez déverrouiller le cadenas avec le code ')." ".$currentCode.".  "._(' Bonne route ')."!";
	  
	   sendSMS($number,$message);
		
	   $result=$db->query("UPDATE bikes SET currentUser=$userId,currentCode=$newCode,currentStand=NULL WHERE bikeNum=$bikeNum");

	   if ($force==FALSE)
			  {
				$result=$db->query("INSERT INTO history SET userId=$userId,bikeNum=$bikeNum,action='RETIRER',parameter=$newCode");
			  }
			else
			 {
			   $result=$db->query("INSERT INTO history SET userId=$userId,bikeNum=$bikeNum,action='FORCERETIRER',parameter=$newCode");
			   sendSMS($currentUserNumber,_('Réservation forcée ').": "._('Le vélo')." ".$bikeNum." "._('a été loué par un administrateur').".");
			 }
   } else {
	   sendSMS($number,_('Désolé, le vélo')." ".$bikeNum." "._('est hors service, choisissez en un autre').".");
   }
}

function returnBike($number,$bike,$stand,$message="",$force=FALSE)
{

   global $db;
   $userId = getUser($number);
   $bikeNum = intval($bike);
   $stand = strtoupper($stand);

   $result=$db->query("SELECT standId FROM stands WHERE standName='$stand'");
   if (!$result->num_rows)
      {
      sendSMS($number,_('Désolé, la station')." '".$stand."' "._('n\'existe pas. Chaque station est identifiée par la lettre indiquée sur le panneau.'));
      return;
      }
   $row=$result->fetch_assoc();
   $standId=$row["standId"];

   if ($force==FALSE)
      {
      $result=$db->query("SELECT bikeNum FROM bikes WHERE currentUser=$userId ORDER BY bikeNum");
      $bikenumber=$result->num_rows;

      if ($bikenumber==0)
         {
         sendSMS($number,_('Oups ! Il ne me semble que vous n\'avez pas de vélo en location. Pour en louer un, envoyez RETIRER.'));
         return;
         }

      $listBikes="";
      while ($row=$result->fetch_assoc())
         {
         $listBikes.=$row["bikeNum"];
         }
      if ($bikenumber>1) $listBikes=substr($listBikes,0,strlen($listBikes)-1);
      }

   if ($force==FALSE)
      {
      $result=$db->query("SELECT currentCode FROM bikes WHERE currentUser=$userId AND bikeNum=$bikeNum");
      if ($result->num_rows!=1)
         {
         sendSMS($number,_('Oups ! Il me semble que vous n\'avez pas le vélo')." ".$bikeNum.". "._('Si je ne me trompe pas, vous avez pluitôt emprunté le vélo').": $listBikes");
         return;
         }

      $row=$result->fetch_assoc();
      $currentCode = sprintf("%04d",$row["currentCode"]);
      $result=$db->query("SELECT note FROM notes WHERE bikeNum=$bikeNum AND deleted IS NULL ORDER BY time DESC LIMIT 1");
      $row=$result->fetch_assoc();
      $note=$row["note"];
      }
   else
      {
      $result=$db->query("SELECT currentCode,currentUser FROM bikes WHERE bikeNum=$bikeNum");
      if ($result->num_rows!=1)
         {
         sendSMS($number,_('Désolé, il me semble que le vélo')." ".$bikeNum." "._('n\'est pas loué. Vérifiez que vous avez entré le bon numéro de vélo.'));
         return;
         }

      $row =$result->fetch_assoc();
      $currentCode = sprintf("%04d",$row["currentCode"]);
      $currentUser =$row["currentUser"];
      $result=$db->query("SELECT note FROM notes WHERE bikeNum=$bikeNum AND deleted IS NULL ORDER BY time DESC LIMIT 1");
      $row=$result->fetch_assoc();
      $note=$row["note"];
        if($currentUser)
        {
    	    $result=$db->query("SELECT number FROM users WHERE userId=$currentUser");
    	    $row =$result->fetch_assoc();
    	    $currentUserNumber =$row["number"];
        }
      }

   if (!preg_match("/return[\s,\.]+[0-9]+[\s,\.]+[a-zA-Z0-9]+[\s,\.]+(.*)/i",$message ,$matches))
      {
      $userNote="";
      }
   else $userNote=$db->conn->real_escape_string(trim($matches[1]));

   $result=$db->query("UPDATE bikes SET currentUser=NULL,currentStand=$standId WHERE bikeNum=$bikeNum");
   if ($userNote)
      {
      $db->query("INSERT INTO notes SET bikeNum=$bikeNum,userId=$userId,note='$userNote'");
      $result=$db->query("SELECT userName,number FROM users WHERE userId='$userId'");
      $row=$result->fetch_assoc();
      $userName=$row["userName"];
      $phone=$row["number"];
      $result=$db->query("SELECT stands.standName FROM bikes LEFT JOIN users ON bikes.currentUser=users.userID LEFT JOIN stands ON bikes.currentStand=stands.standId WHERE bikeNum=$bikeNum");
      $row=$result->fetch_assoc();
      $standName=$row["standName"];
      if ($standName!=NULL)
         {
         $bikeStatus=_('Stationné à')." ".$standName;
         }
         else
         {
         $bikeStatus=_('utilisé par')." ".$userName." +".$phone;
         }
      //notifyAdmins(_('Note')." b.$bikeNum (".$bikeStatus.") "._('par')." $userName/$phone:".$userNote);
      }

   $message=_('Merci ! Je note que le vélo')." ".$bikeNum." "._('a été déposé à la station')." ".$stand.". "._('Pour réinitialiser le cadenas, le nouveau code est : ')." ".$currentCode.".";
   $message.=" "._('A bientôt !');

   if ($force==FALSE)
      {
      $creditchange=changecreditendrental($bikeNum,$userId);
      $result=$db->query("INSERT INTO history SET userId=$userId,bikeNum=$bikeNum,action='DEPOT',parameter=$standId");
      }
   else
      {
      $result=$db->query("INSERT INTO history SET userId=$userId,bikeNum=$bikeNum,action='FORCEDEPOSER',parameter=$standId");
      if($currentUserNumber)
        {
    	    sendSMS($currentUserNumber,_('Dépôt forcé ').": "._('Le vélo')." ".$bikeNum." "._('a été retourné par les agents').".");
        }
      }

   if (iscreditenabled())
      {
      $message.=_('Credit').": ".getusercredit($userId).getcreditcurrency();
      if ($creditchange) $message.=" (-".$creditchange.")";
      $message.=".";
      }
   sendSMS($number,$message);

}


function where($number,$bike)
{

   global $db;
   $userId = getUser($number);
   $bikeNum = intval($bike);

   $result=$db->query("SELECT number,userName,stands.standName FROM bikes LEFT JOIN users on bikes.currentUser=users.userID LEFT JOIN stands on bikes.currentStand=stands.standId where bikeNum=$bikeNum");
   if ($result->num_rows!=1)
      {
      sendSMS($number,_('Vélo')." ".$bikeNum." "._('n\'existe pas').".");
      return;
      }
   $row =$result->fetch_assoc();
   $phone=$row["number"];
   $userName=$row["userName"];
   $standName=$row["standName"];
   $result=$db->query("SELECT note FROM notes WHERE bikeNum=$bikeNum AND deleted IS NULL ORDER BY time DESC LIMIT 1");
   $row=$result->fetch_assoc();
   $note=$row["note"];
   if ($note)
      {
      $note=" "._('Signalement sur le vélo').": $note";
      }

   if ($standName!=NULL)
      {
      sendSMS($number,_('Le vélo')." ".$bikeNum." "._('est à la station')." ".$standName.$note);
      }
   else
      {
      sendSMS($number,_('Le vélo')." ".$bikeNum." "._('est emprunté par')." ".$userName." (+".$phone.").".$note);
      }

}


function listBikes($number,$stand)
{

   global $db,$forcestack;
   $stacktopbike=FALSE;
   $userId = getUser($number);
   $stand = strtoupper($stand);

   if (!preg_match("/^[A-Z]+[0-9]*$/",$stand))
   {
      sendSMS($number,_('Désolé, je ne reconnais pas la station ')." '$stand' "._('. Chaque station est identifiée par la lettre indiquée sur les panneaux.'));
      return;
   }

   $result=$db->query("SELECT standId FROM stands WHERE standName='$stand'");
   if ($result->num_rows!=1)
      {
      sendSMS($number,_('La station')." '$stand' "._('n\'existe pas').".");
      return;
      }
    $row=$result->fetch_assoc();
    $standId=$row["standId"];

   if ($forcestack)
            {
            $stacktopbike=checktopofstack($standId);
            }

   $result=$db->query("SELECT bikeNum FROM bikes where currentStand=$standId ORDER BY bikeNum");
   $rentedBikes=$result->num_rows;

   if ($rentedBikes==0)
      {
      sendSMS($number,_('Désolé, la station')." ".$stand." "._('est vide').".");
      return;
      }

   $listBikes="";
  while ($row=$result->fetch_assoc())
    {
    $listBikes.=$row["bikeNum"];
    if ($stacktopbike==$row["bikeNum"]) $listBikes.=" "._('(first)');
    $listBikes.=",";
    }
   if ($rentedBikes>1) $listBikes=substr($listBikes,0,strlen($listBikes)-1);

   sendSMS($number,_('Il y a ')."".sprintf(ngettext('%d vélo','%d vélos',$rentedBikes),$rentedBikes)." "._('à la station')." ".$stand.".".ngettext(' Le vélo numéro',' Les vélos ',$rentedBikes)." ".$listBikes." ".ngettext(' est disponible.',' sont disponibles.',$rentedBikes));
}


function freeBikes($number)
{

   global $db;
   $userId = getUser($number);

   $result=$db->query("SELECT count(bikeNum) as bikeCount,placeName from bikes join stands on bikes.currentStand=stands.standId where stands.serviceTag=0 group by placeName having bikeCount>0 order by placeName");
   $rentedBikes=$result->num_rows;

   if ($rentedBikes==0)
   {
   	$listBikes=_('Désolé, il n\'y a pas de vélos disponibles.');
   }
   else $listBikes=_('Nombre de vélo libre').":";

   $listBikes="";
   while ($row=$result->fetch_assoc())
      {
      $listBikes.=$row["placeName"].":".$row["bikeCount"];
      $listBikes.=",";
      }
   if ($rentedBikes>1) $listBikes=substr($listBikes,0,strlen($listBikes)-1);

   $result=$db->query("SELECT count(bikeNum) as bikeCount,placeName from bikes right join stands on bikes.currentStand=stands.standId where stands.serviceTag=0 group by placeName having bikeCount=0 order by placeName");
   $rentedBikes=$result->num_rows;

   if (rentedBikes!=0)
   {
        $listBikes.=" "._('Station vide').": ";
   }

   while ($row=$result->fetch_assoc())
      {
      $listBikes.=$row["placeName"];
      $listBikes.=",";
      }
   if ($rentedBikes>1) $listBikes=substr($listBikes,0,strlen($listBikes)-1);

   sendSMS($number,$listBikes);
}

function delnote($number,$bikeNum,$message)
{

   global $db;
   $userId = getUser($number);
   
    $bikeNum=trim($bikeNum);
	if(preg_match("/^[0-9]*$/",$bikeNum))
   	{
		$bikeNum = intval($bikeNum);
   	}
	else if (preg_match("/^[A-Z]+[0-9]*$/i",$bikeNum))
	{
		$standName = $bikeNum;
		delstandnote($number,$standName,$message);
		return;
	}
	else
	{
      	sendSMS($number,_('Erreur avec le numéro de vélo ou la lettre de la station:'.$db->conn->real_escape_string($bikeNum)));
		return;
	}
	   
   $bikeNum = intval($bikeNum);

   checkUserPrivileges($number);

   $result=$db->query("SELECT number,userName,stands.standName FROM bikes LEFT JOIN users on bikes.currentUser=users.userID LEFT JOIN stands ON bikes.currentStand=stands.standId WHERE bikeNum=$bikeNum");
   if ($result->num_rows!=1)
      {
      sendSMS($number,_('Le vélo')." ".$bikeNum." "._('n\'existe pas').".");
      return;
      }
   $row =$result->fetch_assoc();
   $phone=$row["number"];
   $userName=$row["userName"];
   $standName=$row["standName"];

   if ($standName!=NULL)
      {
      $bikeStatus = "Le vélo $bikeNum "._('est à la station')." $standName.";
      }
   else
      {
      $bikeStatus = "Le vélo $bikeNum "._('est loué par')." $userName (+$phone).";
      }

   $result=$db->query("SELECT userName FROM users WHERE number=$number");
   $row =$result->fetch_assoc();
   $reportedBy=$row["userName"];

      $matches=explode(" ",$message,3);
      $userNote=$db->conn->real_escape_string(trim($matches[2]));

	if($userNote=='')
	{
		$userNote='%';
	}

      $result=$db->query("UPDATE notes SET deleted=NOW() where bikeNum=$bikeNum and deleted is null and note like '%$userNote%'");
      $count = $db->conn->affected_rows;

	if($count == 0)
	{
      		if($userNote=="%")
		{
		    sendSMS($number,_('Pas de signalement pour le vélo')." ".$bikeNum." "._('à supprimer').".");
		}
		else
		{
		    sendSMS($number,_('Pas de signalement correspondant')." '".$userNote."' "._('trouvé pour le vélo')." ".$bikeNum." "._('à supprimer').".");
		}
	}
	else
	{
      		// seuls les admins peuvent supprimer et ils recevront une confirmation dans les prochaines etapes.
      		sendSMS($number,"Signalement pour le vélo $bikeNum supprimé.");
      		if($userNote=="%")
		{
			//notifyAdmins(_('OK,')." ".sprintf(ngettext('%d le signalement','%d les signalements',$count),$count)." "._('pour le vélo')." ".$bikeNum." "._('ont été supprimés par')." ".$reportedBy.".");
		}
		else
		{
			//notifyAdmins(sprintf(ngettext('%d Le signalement','%d les signalements',$count),$count)." "._('sur le vélo')." ".$bikeNum." "._('correspondant à')." '".$userNote."' "._('ont été supprimés par')." ".$reportedBy.".");
		}
      	}
}


function untag($number,$standName,$message)
{

   global $db;
   $userId = getUser($number);

	checkUserPrivileges($number);
	$result=$db->query("SELECT standId FROM stands where standName='$standName'");
	if ($result->num_rows!=1)
    {
      sendSMS($number,_("La station")." ".$standName._(" n'existe pas").".");
      return;
    }

   $row =$result->fetch_assoc();
   $standId=$row["standId"];
    
   $result=$db->query("SELECT userName FROM users WHERE number=$number");
   $row =$result->fetch_assoc();
   $reportedBy=$row["userName"];


      $matches=explode(" ",$message,3);
      $userNote=$db->conn->real_escape_string(trim($matches[2]));

	if($userNote=='')
	{
		$userNote='%';
	}

    $result=$db->query("UPDATE signalement SET deleted=NOW() where bikeNum=$bikeNum and deleted is null and note like '%$userNote%'");
    $count = $db->conn->affected_rows;

	if($count == 0)
	{
      		if($userNote=="%")
		{
			sendSMS($number,_('Pas de signalement à supprimer pour la station')." ".$standName.".");
		}
		else
		{
		    sendSMS($number,_('Aucun avertissement ne contient')." '".$userNote."' "._('à la station')." ".$standName." "._('Suppression impossible.').".");
		}
	}
	else
	{
      		// seuls les admins peuvent supprimer et ils recevront une confirmation dans les prochaines etapes.
      		//sendSMS($number,"La note pour le vélo $bikeNum a été supprimée.");
      		if($userNote=="%")
		{
			//notifyAdmins(_('Tous les avertissements pour la station')." ".$standName." "._('ont été supprimés par')." ".$reportedBy.".");
		}
		else
		{
			//notifyAdmins(_('Tous les avertissements pour la station')." ".$standName." "._('contenant')." '".$userNote."' "._('ont été supprimés par')." ".$reportedBy.".");
		}
      	}
}

function delstandnote($number,$standName,$message)
{

   global $db;
   $userId = getUser($number);

	checkUserPrivileges($number);
	$result=$db->query("SELECT standId FROM stands where standName='$standName'");
	if ($result->num_rows!=1)
    {
      sendSMS($number,_("La station")." ".$standName._("n'existe pas").".");
      return;
    }

   $row =$result->fetch_assoc();
   $standId=$row["standId"];
    
   $result=$db->query("SELECT userName FROM users WHERE number=$number");
   $row =$result->fetch_assoc();
   $reportedBy=$row["userName"];


      $matches=explode(" ",$message,3);
      $userNote=$db->conn->real_escape_string(trim($matches[2]));

	if($userNote=='')
	{
		$userNote='%';
	}

      $result=$db->query("UPDATE signalement SET deleted=NOW() where standId=$standId and deleted is null and note like '%$userNote%'");
      $count = $db->conn->affected_rows;

	if($count == 0)
	{
      		if($userNote=="%")
		{
		    sendSMS($number,_('Pas de signalements à supprimer sur la station')." ".$standName." .");
		}
		else
		{
		    sendSMS($number,_('Pas de signalements contenant ces termes')." '".$userNote."' "._('trouvés sur la station')." ".$standName." .");
		}
	}
	else
	{
      		// seuls les admins peuvent supprimer et ils recevront une confirmation dans les prochaines etapes.
      		//sendSMS($number,"La note pour le vélo $bikeNum a été supprimée.");
      		if($userNote=="%")
		{
			//notifyAdmins(_('Tous les signalements à la station')." ".$standName." "._('ont été supprimés par')." ".$reportedBy.".");
		}
		else
		{
			//notifyAdmins(_('Tous les signalements à la station')." ".$standName." "._('contenant')." '".$userNote."' "._('ont été supprimés par ')." ".$reportedBy.".");
		}
      	}
}

function standNote($number,$standName,$message)
{

   global $db;
   $userId = getUser($number);


	$result=$db->query("SELECT standId FROM stands where standName='$standName'");
   if ($result->num_rows!=1)
      {
      sendSMS($number,_("La station")." ".$standName._("n'existe pas").".");
      return;
      }

   $row =$result->fetch_assoc();
   $standId=$row["standId"];

   $result=$db->query("SELECT userName from users where number=$number");
   $row =$result->fetch_assoc();
   $reportedBy=$row["userName"];


    $matches=explode(" ",$message,3);
    $userNote=$db->conn->real_escape_string(trim($matches[2]));

   if ($userNote=="") //supprimer mmm
      {
      		sendSMS($number,_('Pas de signalement enregistrés sur la station')." ".$standName." "._('Pour supprimer un signalement, utiliser DELNOTE (pour les admins)').".");

      //checkUserPrivileges($number);
      // @TODO SMS pour supprimer complètement ?
      //$result=$db->query("UPDATE bikes SET note=NULL where bikeNum=$bikeNum");
      // seuls les admins peuvent supprimer et ils recevront une confirmation dans les prochaines etapes.
      //sendSMS($number,"La note pour le vélo $bikeNum a été supprimée.");
      //notifyAdmins("La note pour le vélo $bikeNum a été supprimée par $reportedBy.");
      }
   else
      {
      $db->query("INSERT INTO notes SET standId='$standId',userId='$userId',note='$userNote'");
      $noteid=$db->conn->insert_id;
      sendSMS($number,_('Signalement pour station')." ".$standName." "._('sauvegardé').".");
      //notifyAdmins(_('Signalement #').$noteid.": "._("sur station")." ".$standName." "._('par')." ".$reportedBy." (".$number."):".$userNote);
      }

}



function tag($number,$standName,$message)
{

   global $db;
   $userId = getUser($number);


	$result=$db->query("SELECT standId FROM stands where standName='$standName'");
   if ($result->num_rows!=1)
      {
      sendSMS($number,_("La station")." ".$standName._("n'existe pas").".");
      return;
      }

   $row =$result->fetch_assoc();
   $standId=$row["standId"];

   $result=$db->query("SELECT userName from users where number=$number");
   $row =$result->fetch_assoc();
   $reportedBy=$row["userName"];


    $matches=explode(" ",$message,3);
    $userNote=$db->conn->real_escape_string(trim($matches[2]));

   if ($userNote=="") //deletemmm
      {
      		sendSMS($number,_('Le signalement vide sur la station')." ".$standName." "._('n\'a pas été sauvegardé. Précisez le nature du problème pour que le signalement soit sauvegardé').".");

			//checkUserPrivileges($number);
      // @TODO SMS pour supprimer complètement ?
      //$result=$db->query("UPDATE bikes SET note=NULL where bikeNum=$bikeNum");
      // seuls les admins peuvent supprimer et ils recevront une confirmation dans les prochaines etapes.
      //sendSMS($number,"La note pour le vélo $bikeNum a été supprimée.");
      //notifyAdmins("La note pour le vélo $bikeNum a été supprimée par $reportedBy.");
     
      }
   else
      {
      $db->query("INSERT INTO signalement SET userId='$userId',standId='$standId',note='$userNote',time=NOW()");
      //$noteid=$db->conn->insert_id;
      sendSMS($number,_('Le problème à la station')." ".$standName." "._('à été signalé').".");
      //notifyAdmins(_('Un problème sur la station')." "."$standName".' '._('à été signalé par')." ".$reportedBy." (".$number.")". _(" avec l'avertissement : ").$userNote);
      }
}


function note($number,$bikeNum,$message)
{

   global $db;
   $userId = getUser($number);
   
    $bikeNum=trim($bikeNum);
	if(preg_match("/^[0-9]*$/",$bikeNum))
   	{
		$bikeNum = intval($bikeNum);
   	}
	else if (preg_match("/^[A-Z]+[0-9]*$/i",$bikeNum))
	{
		$standName = $bikeNum;
		standnote($number,$standName,$message);
		return;
	}
	else
	{
      	sendSMS($number,_('Désolé, il doit y avoir une erreur sur le numéro de vélo ou la lettre de la station :'.$db->conn->real_escape_string($bikeNum)));
		return;
	}
	   
   $bikeNum = intval($bikeNum);
   
   $result=$db->query("SELECT number,userName,stands.standName FROM bikes LEFT JOIN users on bikes.currentUser=users.userID LEFT JOIN stands on bikes.currentStand=stands.standId where bikeNum=$bikeNum");
   if ($result->num_rows!=1)
      {
      sendSMS($number,_('Désolé, le vélo')." ".$bikeNum." "._('n\'existe pas').".");
      return;
      }
   $row =$result->fetch_assoc();
   $phone=$row["number"];
   $userName=$row["userName"];
   $standName=$row["standName"];

   if ($standName!=NULL)
      {
      $bikeStatus = "Le vélo $bikeNum "._('est à la station ')." ".$standName.".";
      }
   else
      {
      $bikeStatus = "Le vélo $bikeNum "._('est reservé')." by ".$userName." (+".$phone.").";
      }

   $result=$db->query("SELECT userName from users where number=$number");
   $row =$result->fetch_assoc();
   $reportedBy=$row["userName"];

   if (trim(strtoupper(preg_replace('/[0-9]+/','',$message)))=="NOTE") // blank, delete note
      {
      $userNote="";
      }
   else
      {
      $matches=explode(" ",$message,3);
      $userNote=$db->conn->real_escape_string(trim($matches[2]));
      }

   if ($userNote=="")
      {
      sendSMS($number,_('Le signalement vide sur le vélo')." ".$bikeNum." "._('n\'a pas été pas sauvegardé. Pour supprimer un signalement utilisez DELNOTE (admins seulement)').".");
      /*checkUserPrivileges($number);
      sendSMS($number,_('Pas de signalement enregistré sur le vélo')." ".$bikeNum." "._('Pour supprimer, utiliser DELNOTE.').".");
      
	//checkUserPrivileges($number);
      // @TODO SMS pour supprimer complètement ?
      //$result=$db->query("UPDATE bikes SET note=NULL where bikeNum=$bikeNum");
      // seuls les admins peuvent supprimer et ils recevront une confirmation dans les prochaines etapes.
      //sendSMS($number,"La note pour le vélo $bikeNum a été supprimée.");
      //notifyAdmins("La note pour le vélo $bikeNum a été supprimée par $reportedBy.");
      */
	}
   else
      {
      $db->query("INSERT INTO notes SET bikeNum='$bikeNum',userId='$userId',note='$userNote'");
      $noteid=$db->conn->insert_id;
      sendSMS($number,_('Merci ! Le signalement sur le vélo')." ".$bikeNum." "._(' a été sauvegardé').".");
      //notifyAdmins(_('Signalement #').$noteid." : Vélo ".$bikeNum." (".$bikeStatus.") "._('signalé par')." ".$reportedBy." (".$number2.") : ".$userNote);
      }

}

function last($number,$bike)
{

   global $db;
   $userId = getUser($number);
   $bikeNum = intval($bike);

   $result=$db->query("SELECT bikeNum FROM bikes where bikeNum=$bikeNum");
          if ($result->num_rows!=1)
      {
         sendSMS($number,_('Désolé, le vélo')." ".$bikeNum." "._('n\'existe pas').".");
         return;
      }

   $result=$db->query("SELECT userName,parameter,standName,action FROM `history` join users on history.userid=users.userid left join stands on stands.standid=history.parameter where bikenum=$bikeNum and action in ('DEPOT','RETIRER','RETOUR') order by time desc LIMIT 10");

   $historyInfo="Vélo $bikeNum : ";
   while($row=$result->fetch_assoc())
   {
     if (($standName=$row["standName"])!=NULL)
      {
         if ($row["action"]=="RETOUR") $historyInfo.="*";
         $historyInfo.=$standName;
      }
      else
      {
         $historyInfo.=$row["userName"]."(".$row["parameter"].")";
      }
      if ($result->num_rows>1) $historyInfo.=",";
   }
   if ($rentedBikes>1) $historyInfo=substr($historyInfo,0,strlen($historyInfo)-1);

   sendSMS($number,$historyInfo);


}

function revert($number,$bikeNum)
{

        global $db;
        $userId = getUser($number);

        $result=$db->query("SELECT currentUser FROM bikes WHERE bikeNum=$bikeNum AND currentUser<>'NULL'");
        if (!$result->num_rows)
           {
           sendSMS($number,_('Désolé, le vélo')." ".$bikeNum." "._('n\'est pas disponibe pour le moment. Réservation annulée !'));
           return;
           }
        else
           {
           $row=$result->fetch_assoc();
           $revertusernumber=getphonenumber($row["currentUser"]);
           }

        $result=$db->query("SELECT parameter,standName FROM stands LEFT JOIN history ON stands.standId=parameter WHERE bikeNum=$bikeNum AND action IN ('DEPOT','FORCEDEPOSER') ORDER BY time DESC LIMIT 1");
        if ($result->num_rows==1)
                {
                        $row=$result->fetch_assoc();
                        $standId=$row["parameter"];
                        $stand=$row["standName"];
                }
        $result=$db->query("SELECT parameter FROM history WHERE bikeNum=$bikeNum AND action IN ('RETIRER','FORCERETIRER') ORDER BY time DESC LIMIT 1,1");
        if ($result->num_rows==1)
                {
                        $row =$result->fetch_assoc();
                        $code=$row["parameter"];
                }
        if ($standId and $code)
           {
           $result=$db->query("UPDATE bikes SET currentUser=NULL,currentStand=$standId,currentCode=$code WHERE bikeNum=$bikeNum");
           $result=$db->query("INSERT INTO history SET userId=$userId,bikeNum=$bikeNum,action='RETOUR',parameter='$standId|$code'");
           $result=$db->query("INSERT INTO history SET userId=0,bikeNum=$bikeNum,action='RETIRER',parameter=$code");
           $result=$db->query("INSERT INTO history SET userId=0,bikeNum=$bikeNum,action='DEPOT',parameter=$standId");
           sendSMS($number,_('Vélo')." ".$bikeNum." "._('retourné à la station')." ".$stand." "._('avec le code')." ".$code.".");
           sendSMS($revertusernumber,_('Vélo')." ".$bikeNum." "._('à été rendu. Vous pouvez à nouveau réserver un vélo.'));
           }
        else
           {
           sendSMS($number,_('Désolé, pas de dernier code trouvé pour le vélo ')." ".$bikeNum." "._('Le retour est impossible.'));
           }

}

function add($number,$email,$phone,$message)
{

        global $db, $countrycode;
   $userId = getUser($number);

   $phone=normalizephonenumber($phone);

   $result=$db->query("SELECT number,mail,userName FROM users where number=$phone OR mail='$email'");
      if ($result->num_rows!=0)
      {
             $row =$result->fetch_assoc();

         $oldPhone=$row["number"];
         $oldName=$row["userName"];
         $oldMail=$row["mail"];

         sendSMS($number,_('Ce numéro est déjà utilisé par:')." ".$oldMail." +".$oldPhone." ".$oldName);
         return;
      }

   if ($phone < $countrycode."000000000" || $phone > ($countrycode+1)."000000000" || !preg_match("/add\s+([a-z0-9._%+-]+@[a-z0-9.-]+)\s+\+?[0-9]+\s+(.{2,}\s.{2,})/i",$message ,$matches))
   {
      sendSMS($number,_('Désolé, les informations sont incorrectes ou incomplètes. Merci de les formuler selon l\'exemple suivant :')."votremail@mail.com 0600000000 Nom Prénom ");
      return;
   }
   $userName=$db->conn->real_escape_string(trim($matches[2]));
   $email=$db->conn->real_escape_string(trim($matches[1]));

   $result=$db->query("INSERT into users SET userName='$userName',number=$phone,mail='$email'");

   sendConfirmationEmail($email);

   sendSMS($number,_('Utilisateur')." ".$userName." "._('ajouté. Vous devez accepter les conditions d\'utilisation du service avant de pouvoir louer un vélo.'));


}

function checkUserPrivileges($number)
{
   global $db, $sms;
   $userId=getUser($number);
   $privileges=getPrivileges($userId);
   if ($privileges==0)
      {
      sendSMS($number,_('Désolé, cette commande est uniquement disponible pour les administrateurs.'));
      $sms->Respond();
      exit;
      }
}

?>
