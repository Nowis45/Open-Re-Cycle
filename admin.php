<?php
require("config.php");
require("db.class.php");
require('actions-web.php');

$db=new Database($dbserver,$dbuser,$dbpassword,$dbname);
$db->connect();

checksession();
if (getprivileges($_COOKIE["loguserid"])<=0) exit(_('Vous devez être administrateur pour accéder à cette page.'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title><?php echo $systemname; ?> <?php echo _('administration'); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/bootstrapValidator.min.js"></script>
<script type="text/javascript" src="js/translations.php"></script>
<script type="text/javascript" src="actions-web.php"></script>
<script type="text/javascript" src="js/admin.js"></script>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css" />
<link rel="stylesheet" type="text/css" href="css/bootstrapValidator.min.css" />
<link rel="stylesheet" type="text/css" href="css/footer.css" />
<?php if (file_exists("analytics.php")) require("analytics.php"); ?>
<script>
<?php
if (iscreditenabled())
   {
   echo 'var creditenabled=1;',"\n";
   echo 'var creditcurrency="',$credit["currency"],'"',";\n";
   $requiredcredit=$credit["min"]+$credit["rent"]+$credit["longrental"];
   }
else
   {
   echo 'var creditenabled=0;',"\n";
   }
?>

</script>
</head>
<body>
    <!-- Fixed navbar -->
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only"><?php echo _('Activer la navigation'); ?></span>
          </button>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="<?php echo $systemURL; ?>register.php"><?php echo _('Inscription'); ?></a></li>
            <li><a href="<?php echo $systemURL; ?>registerTouriste.php"><?php echo _('Location touristique'); ?></a>
            <li class="active"><a href="<?php echo $systemURL; ?>admin.php"><?php echo _('Gestion'); ?></a></li>
            <li><a href="<?php echo $systemURL; ?>"><?php echo _('Carte'); ?></a></li>
            <li><a href="<?php echo $systemURL; ?>ajoutVelo.php"><?php echo _('Nouveau vélo'); ?></a></li>
            <li><a href="<?php echo $systemURL; ?>ajoutStation.php"><?php echo _('Nouvelle station'); ?></a></li>
            
<?php if (isloggedin()): ?>
            <?php echo '<li style="float:right;"><a>',getusername($_COOKIE["loguserid"]),'</a>';?>
<?php endif; ?>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>
    <div class="container">

      <div class="page-header">
        <h1><?php echo _('Gestion du réseau'); ?></h1>
      </div>

<?php
if (isloggedin()):
?>
            <div role="tabpanel">

  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#fleet" aria-controls="fleet" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span> <?php echo _('Flotte de vélos'); ?></a></li>
    <li role="presentation"><a href="#stands" aria-controls="stands" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-map-marker" aria-hidden="true"></span> <?php echo _('Stations'); ?></a></li>
    <li role="presentation"><a href="#users" aria-controls="users" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> <?php echo _('Utilisateurs'); ?></a></li>
    <li role="presentation"><a href="#touristes" aria-controls="touristes" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> <?php echo _('Touristes'); ?></a></li>
<?php
if (iscreditenabled()):
?>
    <li role="presentation"><a href="#credit" aria-controls="credit" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-euro" aria-hidden="true"></span> <?php echo _('Credit system'); ?></a></li>
<?php endif; ?>
    <li role="presentation"><a href="#reports" aria-controls="reports" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-stats" aria-hidden="true"></span> <?php echo _('Statistiques'); ?></a></li>
  </ul>

  <!-- Tab panes -->
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="fleet">
      <div class="row">
      <div class="col-lg-12">
               <input placeholder="Entrez un numéro de vélo et choisissez une action" type="text" name="adminparam" id="adminparam" class="form-control">
			  <a href="admin.php"><button class="btn btn-default" type="button" title="<?php echo _('Affiche la liste des vélos'); ?>"><span class="glyphicon glyphicon-refresh"></span><?php echo _(' Etat des vélos'); ?></button></a>
               <button style="float: right;"  type="button" id="removenote" class="btn btn-default" title="<?php echo _('Supprime le signalement du vélo correspondant'); ?>"><span class="glyphicon glyphicon-remove"></span> <?php echo _('Supprimer le signalement'); ?></button>
               <button style="float: right;" class="btn btn-default" type="button" id="where" title="<?php echo _('Affiche la station du vélo ou la personne l\'utilisant.'); ?>"><span class="glyphicon glyphicon-screenshot"></span> <?php echo _('Ou est il ?'); ?></button>
         <button style="float: right;" type="button" id="last" class="btn btn-default" title="<?php echo _('Affiche l\'historique de l\'utilisation du vélo.'); ?>"><span class="glyphicon glyphicon-stats"></span> <?php echo _('Derniere utilisation'); ?></button>
         
         <div id="fleetconsole"></div>
         </div>
      </div>

    </div>
    <div role="tabpanel" class="tab-pane" id="stands">
      <div class="row">
         <div class="col-lg-12">
         <button type="button" id="stands" class="btn btn-default" title="Montre l'état detaillé des stations."><span class="glyphicon glyphicon-map-marker"></span> <?php echo _('Etat des stations'); ?></button>
         <div id="standsconsole"></div>
         </div>
      </div>
    </div>
<?php
if (iscreditenabled()):
?>
    <div role="tabpanel" class="tab-pane" id="credit">
      <div class="row">
         <div class="col-lg-12">
         <button type="button" id="listcoupons" class="btn btn-default" title="<?php echo _('Display existing coupons.'); ?>"><span class="glyphicon glyphicon-list-alt"></span> <?php echo _('List coupons'); ?></button>
         <button type="button" id="generatecoupons1" class="btn btn-success" title="<?php echo _('Generate new coupons.'); ?>"><span class="glyphicon glyphicon-plus"></span> <?php echo _('Generate'); echo ' ',$requiredcredit,$credit["currency"],' '; echo _('coupons'); ?></button>
         <button type="button" id="generatecoupons2" class="btn btn-success" title="<?php echo _('Generate new coupons.'); ?>"><span class="glyphicon glyphicon-plus"></span> <?php echo _('Generate'); echo ' ',$requiredcredit*5,$credit["currency"],' '; echo _('coupons'); ?></button>
         <button type="button" id="generatecoupons3" class="btn btn-success" title="<?php echo _('Generate new coupons.'); ?>"><span class="glyphicon glyphicon-plus"></span> <?php echo _('Generate'); echo ' ',$requiredcredit*10,$credit["currency"],' '; echo _('coupons'); ?></button>
         <div id="creditconsole"></div>
         </div>
      </div>
    </div>
<?php endif; ?>
    <div role="tabpanel" class="tab-pane" id="users">
      <div class="row">
         <div class="col-lg-12">
         <button type="button" id="userlist" class="btn btn-default" title="<?php echo _('Montrer la liste des utilisateurs.'); ?>"><span class="glyphicon glyphicon-user"></span> <?php echo _('Liste des utilisateurs'); ?></button>
         </div>
      </div>
      <form class="container" id="edituser">
         <div class="form-group"><label for="username" class="control-label"><?php echo _('Nom et prénom:'); ?></label> <input type="text" name="username" id="username" class="form-control" /></div>
         <div class="form-group"><label for="email"><?php echo _('Email:'); ?></label> <input type="text" name="email" id="email" class="form-control" /></div>
<?php if ($connectors["sms"]): ?>
         <div class="form-group"><label for="phone"><?php echo _('Numéro de téléphone :'); ?></label> <input type="text" name="phone" id="phone" class="form-control" /></div>
<?php endif; ?>
         <div class="form-group"><label for="privileges"><?php echo _('Privilèges:'); ?></label> <input type="text" name="privileges" id="privileges" class="form-control" /></div>
         <div class="form-group"><label for="limit"><?php echo _('Limite de vélos:'); ?></label> <input type="text" name="limit" id="limit" class="form-control" /></div>
         <input type="hidden" name="userid" id="userid" value="" />
         <button type="button" id="saveuser" class="btn btn-primary"><?php echo _('Sauvegarder'); ?></button>
      </form>
      <div id="userconsole"></div>
    </div>

    <div role="tabpanel" class="tab-pane" id="touristes">
      <div class="row">
         <div class="col-lg-12">
         <button type="button" id="touristeslist" class="btn btn-default" title="<?php echo _('Montrer la liste des locations.'); ?>"><span class="glyphicon glyphicon-user"></span> <?php echo _('Liste des locations'); ?></button>
         </div>
      </div>
	  <div id="touristesconsole"></div>
    </div>

    <div role="tabpanel" class="tab-pane" id="reports">
      <div class="row">
         <div class="col-lg-12">
         <button type="button" id="usagestats" class="btn btn-default" title="<?php echo _('Montre les statistiques du jour'); ?>"><span class="glyphicon glyphicon-road"></span> <?php echo _('Statistiques journalières'); ?></button>
         <div id="reportsconsole"></div>
         </div>
      </div>
    </div>
  </div>

   </div>

<?php endif; ?>
    </div><!-- /.container -->
</body>
<footer class="footer_area">
  <div class="foo_bottom_header_one section_padding_50 text-center">
    <div class="container">
      <div class="row">
        <p>OpenSourceBikeShare 2018 - Adapté de OpenSourceBikeShare par S.F. Evo Pods</p>
      </div>
    </div>
  </div>
</footer>
</html>
