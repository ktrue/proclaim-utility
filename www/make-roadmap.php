<?php 

if(!isset($_POST['download'])) {include("top-parts.php");} 
?>
<div class="w3-codespan">
This utility is for use with Proclaim projection software to generate a Worship Roadmap
from a Proclaim backup .prs file.<br />
Questions: Contact Ken True for more information.
</div>

<h1>Generate Roadmap from Proclaim .prs backup file</h1>

<form action="roadmap.php" method="post" multipart="" enctype="multipart/form-data">
<table style="border: 1px black solid; width: 800px;">
<tr>
  <td colspan="2" class="button">Note: you can upload a local Proclaim .prs backup file for processing.<br/>
  Just use the Browse... button, select the .prs file, then press Upload to generate a Worship Roadmap.</td>
</tr>
<tr>
  <td class="input"><input type="file" accept=".prs" name="upload"></td>
  <td class="button"><input type="submit" value="Upload Proclaim .prs file and generate roadmap" name="uploadprior"></td>
  </tr>
</table>
</form>

<?
if(!isset($_POST['download'])) {include("bottom-parts.php");} 
?>