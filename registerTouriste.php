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
            <li><a href="<?php echo $systemURL; ?>register.php"><?php echo _('Inscription'); ?></a></li>
            <li class="active"><a href="<?php echo $systemURL; ?>registerTouriste.php"><?php echo _('Location touristique'); ?></a>
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
        <h1><?php echo _('Location Touristique'); ?></h1>
        <div id="console"></div>
      </div>

      <form  method="POST" action="registerTouriste.php" enctype="multipart/form-data">
        <h2 id="step2title"><?php echo _('Enregistrement d\'une location touristique'); ?></h2>
         <div class="form-group">
            <label for="number" class="control-label"><?php echo _('Numéro de téléphone:'); ?></label> <input x-moz-errormessage="Ecrivez le numéro de téléphone (ex: 0256895689)" required type="tel" name="number" id="number" class="form-control" placeholder="exemple : 33615171290"/>
         </div>
         <div class="form-group">
            <label for="nation"><?php echo _('Nationalité:'); ?></label> <input x-moz-errormessage="Ecrivez la nationalité (ex: Francais)" required type="text" name="nation" id="nation" class="form-control" placeholder="<?php echo _('Francais'); ?>" /></div>
         <div class="form-group">
            <label for="nbBikes"><?php echo _('Nombre de vélos loué:'); ?></label> <input x-moz-errormessage="Ecrivez le nombre de vélos loué (ex: 3)" required type="text" name="nbBikes" id="nbBikes" class="form-control" placeholder="exemple : 1" /></div>
         <button type="submit" id="register" class="btn btn-primary"><?php echo _('Enregistrer la location'); ?></button>
      </form>
      <br>
      <?php
        if(isset($_POST['number']) && isset($_POST['nation']) && isset($_POST['nbBikes'])){
          $number = trim($_POST['number']);
          $nation = trim($_POST['nation']);
          $nbBikes = trim($_POST['nbBikes']);
          $result=$db->query("INSERT INTO touristes (number, nation, nbBikes) VALUES ('$number', '$nation', '$nbBikes')");
          echo '<div class="alert alert-success" role="alert"><h3>La location a correctement été enregistrée.</h3></div>';
        }
      $db->conn->commit();
      ?>
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