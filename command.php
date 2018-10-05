<?php
require("config.php");
require("db.class.php");
require('actions-web.php');

$db=new Database($dbserver,$dbuser,$dbpassword,$dbname);
$db->connect();

if (isset($_COOKIE["loguserid"])) $userid=$_COOKIE["loguserid"];
else $userid=0;
if (isset($_COOKIE["logsession"])) $session=$_COOKIE["logsession"];
$action="";
if (isset($_GET["action"])) $action=trim($_GET["action"]);

switch($action)
   {
   case "smscode":
      $number=trim($_GET["number"]);
      smscode($number);
      break;
   case "register":
      $number=trim($_POST["number"]);
      $fullname=trim($_POST["fullname"]);
      $email=trim($_POST["useremail"]);
      $photo=trim($_POST["profile_pic"]);
      $existing=trim($_POST["existing"]);

      $target_dir = "uploads/";
      $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
	   $target_file_name = basename( $_FILES["profile_pic"]["name"]);
      $uploadOk = 1;
      $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
      $filename = $_FILES["profile_pic"]["name"];
      $file_ext = substr($filename, strripos($filename, '.')); // avoir l'extension du fichier
      $newfilename = $fullname . $file_ext;
      // Vérification si le fichier est bien une image
      if(isset($_POST["submit"])) {
          $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
          if($check !== false) {
              response(_('Le fichier est bien une image - ')." ". $check["mime"] .".");
              $uploadOk = 1;
          } else {
			  response(_('Le fichier n\'est pas une image.'),ERROR);
              $uploadOk = 0;
          }
      }
      // Vérifier si le fichier n'existe pas deja
      if (file_exists($target_file)) {
		  response(_('Désolé, le fichier existe déjà, vérifiez si l\'utilisateur n\'est pas déjà inscrit !'),ERROR);
          $uploadOk = 0;
      }
      // vérifier taille de l'image
      if ($_FILES["profile_pic"]["size"] >= 5242880) {
          response(_('Désolé, le fichier est trop grand !'),ERROR);
          $uploadOk = 0;
      }
      // Autoriser ertains formats d'image
      if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
      && $imageFileType != "gif" ) {
		  response(_('Désolé, le format du fichier n\'est pas accepté, seules les images au formats JPG, JPEG et PNG sont acceptées !'),ERROR);
          $uploadOk = 0;
      }
      // vérifier si $uploadOk est mis à 0 par une erreur
      if ($uploadOk == 0) {
		  response(_('Désolé, votre fichier n\'a pas été envoyé.'),ERROR);
      // si tout est ok, upload de l'image
      } else {
          if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], "uploads/". $newfilename)) {
              register($number,$fullname,$email,$existing,$photo);
			  
          } else {
			  response(_('Désolé, il y a eu un problème avec l\'envoi de votre fichier.'),ERROR);
          }
      }
      break;
   case "login":
      $number=trim($_POST["number"]);
      $password=trim($_POST["password"]);
      login($number,$password);
      break;
   case "logout":
      logout();
      break;
   case "resetpassword":
      resetpassword($_GET["number"]);
      break;
   case "list":
      $stand=trim($_GET["stand"]);
      listbikes($stand);
      break;
   case "rent":
      logrequest($userid,$action);
      checksession();
      $bikeno=trim($_GET["bikeno"]);
      checkbikeno($bikeno);
      rent($userid,$bikeno);
      break;
   case "return":
      logrequest($userid,$action);
      checksession();
      $bikeno=trim($_GET["bikeno"]);
      $stand=trim($_GET["stand"]);
      $note="";
      if (isset($_GET["note"])) $note=trim($_GET["note"]);
      checkbikeno($bikeno); checkstandname($stand);
      returnBike($userid,$bikeno,$stand,$note);
      break;
   case "validatecoupon":
      logrequest($userid,$action);
      checksession();
      $coupon=trim($_GET["coupon"]);
      validatecoupon($userid,$coupon);
      break;
   case "forcerent":
      logrequest($userid,$action);
      checksession();
      checkprivileges($userid);
      $bikeno=trim($_GET["bikeno"]);
      checkbikeno($bikeno);
      rent($userid,$bikeno,TRUE);
      break;
   case "forcereturn":
      logrequest($userid,$action);
      checksession();
      checkprivileges($userid);
      $bikeno=trim($_GET["bikeno"]);
      $stand=trim($_GET["stand"]);
      $note="";
      if (isset($_GET["note"])) $note=trim($_GET["note"]);
      checkbikeno($bikeno); checkstandname($stand);
      returnBike($userid,$bikeno,$stand,$note,TRUE);
      break;
   case "where":
      if ($_GET["bikeno"])
         {
         $bikeno=trim($_GET["bikeno"]);
         checkbikeno($bikeno);
         where($userid,$bikeno);
         }
      else where($userid);
      break;
   case "removenote":
       if ($_GET["bikeno"])
         {
         $bikeno=trim($_GET["bikeno"]);
         checkbikeno($bikeno);
         removenote($bikeno);
         }
	  else removenote($bikeno);
      break;
   case "removesignalement":
		removesignalement($_GET["standId"]);
		break;
   case "revert":
	  logrequest($userid,$action);
	  checksession();
	  checkprivileges($userid);
      $bikeno=trim($_GET["bikeno"]);
      revert($bikeno);
      break;
   case "last":
      if ($_GET["bikeno"])
         {
         $bikeno=trim($_GET["bikeno"]);
         checkbikeno($bikeno);
         last($userid,$bikeno);
         }
      else last($userid);
      break;
   case "stands":
      liststands();
      break;
   case "userlist":
      getuserlist();
      break;
   case "touristeslist":
	  gettouristeslist();
	  break;
   case "userstats":
      getuserstats();
      break;
   case "usagestats":
      getusagestats();
      break;
   case "removeuser":
      removeuser($_GET["userid"]);
      break;
   case "edituser":
      edituser($_GET["edituserid"]);
      break;
   case "saveuser":
      saveuser($_GET["edituserid"],$_GET["username"],$_GET["email"],$_GET["phone"],$_GET["privileges"],$_GET["limit"]);
      break;
   case "addcredit":
      logrequest($userid,$action);
      checksession();
      checkprivileges($userid);
      addcredit($_GET["edituserid"],$_GET["creditmultiplier"]);
      break;
   case "trips":
      logrequest($userid,$action);
      checksession();
      checkprivileges($userid);
      if ($_GET["bikeno"])
         {
         $bikeno=trim($_GET["bikeno"]);
         checkbikeno($bikeno);
         trips($userid,$bikeno);
         }
      else trips($userid);
      break;
   case "userbikes":
      userbikes($userid);
      break;
   case "couponlist":
      logrequest($userid,$action);
      checksession();
      getcouponlist();
      break;
   case "generatecoupons":
      logrequest($userid,$action);
      checksession();
      generatecoupons($_GET["multiplier"]);
      break;
   case "sellcoupon":
      logrequest($userid,$action);
      checksession();
      sellcoupon($_GET["coupon"]);
      break;
   case "map:markers":
      mapgetmarkers();
      break;
   case "map:status":
      mapgetlimit($userid);
      break;
   case "map:geolocation":
      $lat=floatval(trim($_GET["lat"]));
      $long=floatval(trim($_GET["long"]));
      mapgeolocation($userid,$lat,$long);
      break;
   }

?>