<?php 
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness 
# make-openlp.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
# Driver page for make-openlp-inc.php
#
include_once("settings-common.php");

if(!isset($_POST['download'])) {include("top-parts.php");} 

$doInclude = true;
include_once("make-openlp-inc.php");

if(!isset($_POST['download'])) {include("bottom-parts.php");} 
?>