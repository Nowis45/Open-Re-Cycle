<?php
require("config.php");
require("db.class.php");
require("common.php");

$db=new Database($dbserver,$dbuser,$dbpassword,$dbname);
$db->connect();
checksession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title><?php echo $systemname; ?> <?php echo _('registration'); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/bootstrapValidator.min.js"></script>
<script type="text/javascript" src="js/translations.php"></script>
<script type="text/javascript" src="js/register.js"></script>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css" />
<link rel="stylesheet" type="text/css" href="css/bootstrapValidator.min.css" />
<link rel="stylesheet" type="text/css" href="css/footer.css" />
<?php if (file_exists("analytics.php")) require("analytics.php"); ?>
</head>
<body>
    <!-- Fixed navbar -->
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only"><?php echo _('Toggle navigation'); ?></span>
          </button>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="<?php echo $systemURL; ?>register.php"><?php echo _('Inscription'); ?></a></li>
            <li><a href="<?php echo $systemURL; ?>registerTouriste.php"><?php echo _('Location touristique'); ?></a>
            <li><a href="<?php echo $systemURL; ?>admin.php"><?php echo _('Gestion'); ?></a></li>
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
        <h1><?php echo _('Inscription'); ?></h1>
        <div id="console"></div>
      </div>

      <?php
      if (isset($_GET["error"]) AND $_GET["error"]==0) echo '<div class="alert alert-success" role="alert"><h3>',_('L\'utilisateur a été correctement inscrit !'),'</h3></div>';
      else if (isset($_GET["error"]) AND $_GET["error"]==2) echo '<div class="alert alert-danger" role="alert"><h3>',_('Session terminée ! Reconnectez vous.'),'</h3></div>';
      ?>

      <form  method="POST" action="command.php?action=register" enctype="multipart/form-data">
        <h2 id="step2title"><?php echo _('Création d\'un compte utilisateur'); ?></h2>
         <div class="form-group">
            <label for="number" class="control-label"><?php echo _('Numéro de téléphone:'); ?></label> <input x-moz-errormessage="Ecrivez le numéro de téléphone (ex: 0256895689)" required type="tel" name="number" id="number" class="form-control" placeholder="33615161890"/>
         </div>
         <div class="form-group">
            <label for="fullname"><?php echo _('Nom et Prénom:'); ?></label> <input x-moz-errormessage="Ecrivez le nom puis le prénom séparé d'un espace (ex: John Doe)" required type="text" name="fullname" id="fullname" class="form-control" placeholder="<?php echo _('Nom Prénom'); ?>" /></div>
         <div class="form-group">
            <label for="useremail"><?php echo _('Email:'); ?></label> <input x-moz-errormessage="Ecrivez l'adresse Email (ex: exemple@exemple.com)" required type="email" name="useremail" id="useremail" class="form-control" placeholder="email@domaine.com" /></div>
          <div class="form-group">
            <label for="profile_pic">Pièce d'Identité de l'utilisateur: (Taille maximale 5Mo)</label>
            <input x-moz-errormessage="Importez la photocopie de la pièce d'identité de l'utilisateur" required type="file" id="profile_pic" name="profile_pic" accept=".jpg, .jpeg, .png">
          </div>
          
          <div class="form-group">
            <label for="terms">Conditions d'utilisation:</label>
            <p><input x-moz-errormessage="Après que l'utilisateur ai pris connaissance des conditions d'utilisation vous pouvez cocher cette case" required type="checkbox" name="terms"> L'utilisateur à lu et accèpte les <a href="#">conditions d'utilisation</a></p>
          </div>
          <input type="hidden" name="existing" id="existing" value="0" />
         <button type="submit" id="register" class="btn btn-primary"><?php echo _('Créer le compte'); ?></button>
      </form>
      <br>
    </div>
   <!-- /.container -->
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
