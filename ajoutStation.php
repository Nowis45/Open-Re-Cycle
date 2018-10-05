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
            <li><a href="<?php echo $systemURL; ?>registerTouriste.php"><?php echo _('Location touristique'); ?></a>
            <li><a href="<?php echo $systemURL; ?>admin.php"><?php echo _('Gestion'); ?></a></li>
            <li><a href="<?php echo $systemURL; ?>"><?php echo _('Carte'); ?></a></li>
            <li><a href="<?php echo $systemURL; ?>ajoutVelo.php"><?php echo _('Nouveau vélo'); ?></a></li>
            <li class="active"><a href="<?php echo $systemURL; ?>ajoutStation.php"><?php echo _('Nouvelle station'); ?></a></li>
            
<?php if (isloggedin()): ?>
            <?php echo '<li style="float:right;"><a>',getusername($_COOKIE["loguserid"]),'</a>';?>
<?php endif; ?>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>
    <div class="container">
      <div class="page-header">
        <h1><?php echo _('Ajout d\'une nouvelle station'); ?></h1>
        <div id="console"></div>
      </div>
      
      <form class="container" method="POST" action="ajoutStation?step=4">
         <div class="form-group"><label for="standLetter" class="control-label"><?php echo _('Lettre de la station :'); ?></label> <input required type="text" name="standLetter" id="standLetter" placeholder="Exemple : A " class="form-control" /></div>
         <div class="form-group"><label for="standName" class="control-label"><?php echo _('Nom complet de la station :'); ?></label> <input required type="text" name="standName" id="standName" placeholder="Exemple : Halte fluviale " class="form-control" /></div>
         <div class="form-group"><label for="standLat" class="control-label"><?php echo _('Latitude :'); ?></label> <input required type="text" name="standLat" id="standLat" placeholder="Exemple : 49.385873 " class="form-control" /></div>
         <div class="form-group"><label for="standLng" class="control-label"><?php echo _('Longitude :'); ?></label> <input required type="text" name="standLng" id="standLng" placeholder="Exemple : 3.3241162 " class="form-control" /></div>
         <button type="submit" id="register" class="btn btn-primary"><?php echo _('Ajouter la station'); ?></button>
      </form>
  <br>

  <?php
    if(isset($_POST['standLetter']) && isset($_POST['standName']) && isset($_POST['standLat']) && isset($_POST['standLng'])){
      $lat = trim($_POST['standLat']);
      $lng = trim($_POST['standLng']);
      $name = trim($_POST['standName']);
      $letter = trim($_POST['standLetter']);
      $result0=$db->query("SELECT * FROM stands WHERE standName='$letter'");
      $result1=$db->query("SELECT * FROM stands WHERE placeName='$name'");
      if($result0->num_rows!=0){
        echo '<div class="alert alert-danger" role="alert"><h3>Désolé la station '.$letter.' existe déjà !</h3></div>';
      } else {
        if($result1->num_rows!=0){
          echo '<div class="alert alert-danger" role="alert"><h3>Désolé la station '.$name.' existe déjà !</h3></div>';
        } else {
          $result=$db->query("INSERT INTO stands (standName, serviceTag, placeName, longitude, latitude) VALUES ('$letter', '0', '$name', '$lng', '$lat')");
        }
      }
    }
  $db->conn->commit();
  ?>
  </div>
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