<?php
require("config.php");
require("db.class.php");
$db=new Database($dbserver,$dbuser,$dbpassword,$dbname);
$db->connect();
require("actions-sms.php");
log_sms($sms->UUID(),$sms->Number(),$sms->Time(),$sms->Text(),$sms->IPAddress());
$args=preg_split("/\s+/",$sms->ProcessedText());//preg_split must be used instead of explode because of multiple spaces
if(!validateNumber($sms->Number()))
   {
   sendSMS($sms->Number(),_('Désolé, votre numéro n\'est pas enregistré.'));
   }
else
   {
   switch($args[0])
      {
    case "AIDE":
	  case "HELP":
         help($sms->Number());
         break;
    case "FREE":
         freeBikes($sms->Number());
         break;
    case "RETIRER":
    case "RENT":
         validateReceivedSMS($sms->Number(),count($args),2,_('avec le numéro de vélo :')." RETIRER 18");
         rent($sms->Number(),$args[1]);//intval
         break;
	  case "DéPOSER":
    case "DEPOSER":
         validateReceivedSMS($sms->Number(),count($args),3,_('avec le numéro de vélo et la lettre de la station :')." DEPOSER 18 C");
         returnBike($sms->Number(),$args[1],$args[2],trim(urldecode($sms->Text())));
         break;
	  case "FORCERETIRER":
    case "FORCERENT":
         checkUserPrivileges($sms->Number());
         validateReceivedSMS($sms->Number(),count($args),2,_('avec le numéro de vélo :')." FORCERETIRER 18");
         rent($sms->Number(),$args[1],TRUE);
         break;
    case "FORCEDEPOSER":
    case "FORCERETURN":
         checkUserPrivileges($sms->Number());
         validateReceivedSMS($sms->Number(),count($args),3,_('avec le numéro de vélo et la lettre de la station :')." FORCEDEPOSER 18 C");
         returnBike($sms->Number(),$args[1],$args[2],trim(urldecode($sms->Text())),TRUE);
         break;
    case "TROUVER":
    case "FIND":
		 checkUserPrivileges($sms->Number());
         validateReceivedSMS($sms->Number(),count($args),2,_('avec le numéro de vélo. Ex :')." TROUVER 18");
         where($sms->Number(),$args[1]);
         break;
    case "INFO":
         validateReceivedSMS($sms->Number(),count($args),2,_('avec la lettre de la station. Ex :')." INFO C");
         info($sms->Number(),$args[1]);
         break;
	  case "SIGNALER":
    case "NOTE":
         validateReceivedSMS($sms->Number(),count($args),2,_('avec le numéro de vélo et la description du problème. Ex :')." SIGNALER 18 "._('Pneu avant dégonflé'));
         note($sms->Number(),$args[1],trim(urldecode($sms->Text())));
         break;
	  case "AVERTIR":
	  case "TAG":
         validateReceivedSMS($sms->Number(),count($args),2,_('avec la lettre de la station et la desciption du problème. Ex :')." AVERTIR C "._('vandalisme'));
         tag($sms->Number(),$args[1],trim(urldecode($sms->Text())));
         break;
	  case "DELNOTE":
    case "SUPSIGNALEMENT":
	     checkUserPrivileges($sms->Number());
         validateReceivedSMS($sms->Number(),count($args),1,_('avec le numéro du vélo et le problème à signaler. Tout signalement contenant les mots clés seront supprimés. Ex :')." SUPSIGN 18 pneu");
         delnote($sms->Number(),$args[1],trim(urldecode($sms->Text())));
         break;
	  case "SUPAVERTISSEMENT":
    case "UNTAG":
		 checkUserPrivileges($sms->Number());
         validateReceivedSMS($sms->Number(),count($args),1,_('avec la lettre de la station et le signalament. Tout signalement contenant les mots clés seront supprimés pour tous les vélos à la station. Ex :')." SUPAVERTISSEMENT C vandalisme");
         delstandnote($sms->Number(),$args[1],trim(urldecode($sms->Text())));
         break;
    case "LISTE":
	  case "LIST":
         //checkUserPrivileges($sms->Number()); //autorisé pour tous les utilisateurs
         validateReceivedSMS($sms->Number(),count($args),2,_('avec la lettre de la station. Ex :')." LISTE A");
         validateReceivedSMS($sms->Number(),count($args),2,"avec la lettre de la station. Ex : LISTE A");
         listBikes($sms->Number(),$args[1]);
         break;
    case "LAST":
	  case "DERNIER":
         checkUserPrivileges($sms->Number());
         validateReceivedSMS($sms->Number(),count($args),2,_('avec le numéro de vélo :')." DERNIER 47");
         last($sms->Number(),$args[1]);
         break;
      default:
         unknownCommand($sms->Number(),$args[0]);
      }
   }

$db->conn->commit();
$sms->Respond();

?>