<?php
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness 
# top-parts.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
include_once("settings-common.php");

$NOWyear = gmdate("Y",time());
?><!DOCTYPE html>
<html lang="en">
<title><?php echo $SITE['churchName'].' Utilities'; ?></title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="Keywords" content="<?php echo $SITE['churchKeywords']; ?>" />
<meta name="robots" content="noindex,nofollow" />
<meta name="distribution" content="Local" />
<meta name="rating" content="General" />
<meta name="copyright" content="<?php echo $NOWyear . ' '. $SITE['churchName']; ?>" />
<meta name="author" content="Ken True" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link rel="stylesheet" href="./css/w3.css">
<link rel="stylesheet" href="./css/w3-theme-black.css">
<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto">
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<?php
if(isset($includeDataTables) and $includeDataTables) {
?>
<!-- Bootstrap core CSS -->
<link href="psongs/css/bootstrap.min.css" rel="stylesheet">
<link href="psongs/css/dataTables.bootstrap.css" rel="stylesheet">

<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->

<?php } ?>
<style>
html,body,h1,h2,h3,h4,h5,h6 {font-family: "Roboto", sans-serif}
</style>
<body>

<!-- Navbar -->
<div class="w3-top">
  <div class="w3-bar w3-theme w3-top w3-left-align w3-large">
    <a class="w3-bar-item w3-button w3-right w3-hide-large w3-hover-white w3-large w3-theme-l1" href="javascript:void(0)" onclick="w3_open()"><i class="fa fa-bars"></i></a>
    <a href="index.php" class="w3-bar-item w3-button w3-theme-l1">Utilities</a>
  </div>
</div>

<!-- Sidebar -->
<nav class="w3-sidebar w3-bar-block w3-collapse w3-large w3-theme-l5 w3-animate-left" style="z-index:3;width:250px;margin-top:43px;" id="mySidebar">
  <a href="javascript:void(0)" onclick="w3_close()" class="w3-right w3-xlarge w3-padding-large w3-hover-black w3-hide-large" title="Close Menu">
    <i class="fa fa-remove"></i>
  </a>
  <h4 class="w3-bar-item"><b>Menu</b></h4>
  <a class="w3-bar-item w3-button w3-hover-black" href="index.php">Home</a>
  <a class="w3-bar-item w3-button w3-hover-black" href="make-openlp.php">Make Lyrics</a>
  <a class="w3-bar-item w3-button w3-hover-black" href="proclaim-list.php">Lyrics Available</a>
  <a class="w3-bar-item w3-button w3-hover-black" href="proclaim-songs-used.php">Songs Used</a>
  <a class="w3-bar-item w3-button w3-hover-black" href="make-roadmap.php">Make Roadmap</a>
  <a class="w3-bar-item w3-button w3-hover-black" href="roadmap.php">Latest Roadmap</a>
</nav>

<!-- Overlay effect when opening sidebar on small screens -->
<div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" title="close side menu" id="myOverlay"></div>

<!-- Main content: shift it to the right by 250 pixels when the sidebar is visible -->
<div class="w3-main" style="margin-left:250px">

  <div class="w3-row w3-padding-64">
    <div class="w3-col w3-container">
<!-- end top-parts.php -->