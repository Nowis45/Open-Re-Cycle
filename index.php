<?php
require("config.php");
require("db.class.php");
require("actions-web.php");

$db=new Database($dbserver,$dbuser,$dbpassword,$dbname);
$db->connect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo $systemname; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/viewportDetect.js"></script>
<script type="text/javascript" src="js/leaflet.js"></script>
<script type="text/javascript" src="js/L.Control.Sidebar.js"></script>
<script type="text/javascript" src="js/translations.php"></script>
<script type="text/javascript" src="js/functions.js"></script>
<?php
if (isset($geojson))
   {
   foreach($geojson as $url)
      {
      echo '<link rel="points" type="application/json" href="',$url,'">'."\n";
      }
   }
?>
<?php if (date("m-d")=="04-01") echo '<script type="text/javascript" src="http://maps.stamen.com/js/tile.stamen.js?v1.3.0"></script>'; ?>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css" />
<link rel="stylesheet" type="text/css" href="css/leaflet.css" />
<link rel="stylesheet" type="text/css" href="css/L.Control.Sidebar.css" />
<link rel="stylesheet" type="text/css" href="css/map.css" />
<link rel="stylesheet" type="text/css" href="css/footer.css" />
<script>
var maplat=<?php echo $systemlat; ?>;
var maplon=<?php echo $systemlong; ?>;
var mapzoom=<?php echo $systemzoom; ?>;
var standselected=0;
<?php
if (isloggedin())
   {
   echo 'var loggedin=1;',"\n";
   echo 'var priv=',getprivileges($_COOKIE["loguserid"]),";\n";
   }
else
   {
   echo 'var loggedin=0;',"\n";
   echo 'var priv=0;',"\n";
   }
if (iscreditenabled())
   {
   echo 'var creditsystem=1;',"\n";
   }
else
   {
   echo 'var creditsystem=0;',"\n";
   }
if (issmssystemenabled()==TRUE)
   {
   echo 'var sms=1;',"\n";
   }
else
   {
   echo 'var sms=0;',"\n";
   }
?>
</script>
<?php if (file_exists("analytics.php")) require("analytics.php"); ?>
</head>
<body>
<div id="map"></div>
    <!-- Fixed navbar -->
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only"><?php echo _('Toggle navigation'); ?></span>
          </button>
        </div>
<?php if (isloggedin()): ?>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="<?php echo $systemURL; ?>register.php"><?php echo _('Inscription'); ?></a></li>
            <li><a href="<?php echo $systemURL; ?>registerTouriste.php"><?php echo _('Location touristique'); ?></a>
            <li><a href="<?php echo $systemURL; ?>admin.php"><?php echo _('Gestion'); ?></a></li>
            <li class="active"><a href="<?php echo $systemURL; ?>"><?php echo _('Carte'); ?></a></li>
            <li><a href="<?php echo $systemURL; ?>ajoutVelo.php"><?php echo _('Nouveau vélo'); ?></a></li>
            <li><a href="<?php echo $systemURL; ?>ajoutStation.php"><?php echo _('Nouvelle station'); ?></a></li>
            <?php echo '<li style="float:right;"><a>',getusername($_COOKIE["loguserid"]),'</a>';?>
<?php endif; ?>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>
<br />
<div id="sidebar">
   <?php if (isloggedin()): ?>
   <div class="row">
      <div class="col-xs-11 col-sm-11 col-md-11 col-lg-11">
      <h1 class="pull-left">Carte du réseau Cyclovis</h1>
      <p>Sélectionnez une station ci-dessous pour contrôler son statut : </p>
      </div>
   </div>
   <?php endif; ?>
   <?php if (!isloggedin()): ?>
   <div id="loginform">
   <h1>Connexion administrateurs</h1>
   <?php
   if (isset($_GET["error"]) AND $_GET["error"]==1) echo '<div class="alert alert-danger" role="alert"><h3>',_('Numéro de téléphone ou mot de passe incorrect. Essayez à nouveau.'),'</h3></div>';
   else if (isset($_GET["error"]) AND $_GET["error"]==2) echo '<div class="alert alert-danger" role="alert"><h3>',_('Session terminée ! Reconnectez vous.'),'</h3></div>';
   else if (isset($_GET["error"]) AND $_GET["error"]==3) echo '<div class="alert alert-danger" role="alert"><h3>',_('Erreur. Accès réservé aux administrateurs.'),'</h3></div>';
   ?>
         <form method="POST" action="command.php?action=login">
         <div class="row"><div class="col-lg-12">
               <label for="number" class="control-label"><?php if (issmssystemenabled()==TRUE) echo _('Numéro de téléphone'); else echo _('Numéro d\'utilisateur'); ?></label> <input type="text" name="number" id="number" class="form-control" />
          </div></div>
          <div class="row"><div class="col-lg-12">
               <label for="password"><?php echo _('Mot de passe'); ?> <small id="passwordresetblock">(<a id="resetpassword"><?php echo _('Mot de passe oublié ? Réinitialisez le.'); ?></a>)</small></label> <input type="password" name="password" id="password" class="form-control" />
          </div></div><br />
          <div class="row"><div class="col-lg-12">
            <button type="submit" id="register" class="btn btn-lg btn-block btn-primary"><?php echo _('Connexion'); ?></button>
          </div></div>
            </form>
   </div>
   <?php endif; ?>
   <?php if (isloggedin()): ?>
   <h2 id="standname"><select id="stands"></select><span id="standcount"></span></h2>
   <div id="standphoto"></div>
   <div id="standbikes"></div>
   <div class="row">
      <div class="col-lg-12">
      <div id="console">
      </div>
      </div>
   </div>
   <div class="row">
   </div>
   <div class="row"><div class="col-lg-12">
   <br /></div></div>
   <div id="rentedbikes"></div>
    <?php endif; ?>
</div>
</body>
</html>
