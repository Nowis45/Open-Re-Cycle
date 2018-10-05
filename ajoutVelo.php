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
            <li class="active"><a href="<?php echo $systemURL; ?>ajoutVelo.php"><?php echo _('Nouveau vélo'); ?></a></li>
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
        <h1><?php echo _('Ajout d\'un nouveau vélo'); ?></h1>
        <div id="console"></div>
      </div>
      
      <form class="container" method="POST" action="ajoutVelo.php?step=4" enctype="multipart/form-data">
         <div class="form-group"><label for="bicycleNumber" class="control-label"><?php echo _('Numéro du vélo :'); ?></label> <input required pattern="[0-9]{1,3}" type="text" name="bicycleNumber" id="bicycleNumber" placeholder="Exemple : 26 " class="form-control" /></div>
         <div class="form-group"><label for="bicycleCode" class="control-label"><?php echo _('Code du cadenas (4 chiffres) :'); ?></label> <input required pattern="[0-9]{4}" type="text" name="bicycleCode" id="bicycleCode" placeholder="Exemple : 2859 (premier code sur lequel le cadenas est initialisé) " class="form-control" /></div>
         <div class="form-group"><label for="bicycleStand" class="control-label"><?php echo _('Station  d\'intégration :'); ?></label> <input required pattern="[A-Z]" type="text" name="bicycleStand" id="bicycleStand" placeholder="Exemple : B " class="form-control" /></div>
         <button type="submit" id="submit" class="btn btn-primary"><?php echo _('Ajouter le vélo'); ?></button>
      </form>

	  <br>

		<?php
		  if(isset($_POST['bicycleNumber']) && isset($_POST['bicycleCode']) && isset($_POST['bicycleStand'])){
			$bicycleNumber = trim($_POST['bicycleNumber']);
			$bicycleCode = trim($_POST['bicycleCode']);
			$bicycleStand = trim($_POST['bicycleStand']);
			$result0=$db->query("SELECT * FROM bikes WHERE bikeNum='$bicycleNumber'");
			$result1=$db->query("SELECT standId FROM stands WHERE standName='$bicycleStand'");
			if($result1->num_rows!=1){
				echo '<div class="alert alert-danger" role="alert"><h3>Désolé la station '.$bicycleStand.' n\'éxiste pas !</h3></div>';
			} else {
				$row = $result1->fetch_assoc();
				$standId = $row['standId'];
				if($result0->num_rows!=0){
					echo '<div class="alert alert-danger" role="alert"><h3>Désolé le vélo '.$bicycleNumber.' existe déjà !</h3></div>';
				} else {
					$result=$db->query("INSERT INTO bikes (bikeNum, currentStand, currentCode) VALUES ('$bicycleNumber', '$standId', '$bicycleCode')");
					echo '<div class="alert alert-success" role="alert"><h3>Le vélo '.$bicycleNumber.' à bien été ajouté.</h3></div>';
				}
			}
		  } else if ($_POST['submit']) {
			echo '<div class="alert alert-danger" role="alert"><h3>Informations incomplète, veuillez remplir tous les champs.</h3></div>';
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