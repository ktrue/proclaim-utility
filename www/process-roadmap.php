<?php
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness 
# roadmap.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
ini_set('post_max_size','200M');
ini_set('upload_max_filesize','175M');
#
# Purpose: decode a Proclaim Presentation backup .prs file (which is .zip encoded) and process
# the BackupPresentation.json file to list the service order in HTML5 for viewing/printing.
#
# This program supports separate FTP/SCP uploads of a large .prs file to ./temp-proclaim
# and to process that file for use by roadmap.php.
# When the .prs file is large, the HTTP POST upload via make-roadmap.php/roadmap.php may fail<br />
# due to time or excessive post size.
#
# If that happens, just upload the .prs file to ./temp-proclaim/backup.prs , and load this script<br />
# to have it be processed and ready for roadmap.php to use.
#
# Author: Ken True - webmaster@saratoga-weather.org 
# Copyright: 2021 - Ken True/Campbell United Church of Christ, Campbell, CA, 95008
# Permission is granted to use/modify as required for your church's use.
#
# Input:  a Faithlife Proclaim Backup Presentation (*.prs) file.
# Output: the JSON files needed for roadmap.php to process.
#
# Version 1.00 - 05-Jul-2021 - initial release
#
include_once("settings-common.php");

$Version = 'process-roadmap.php - Version 1.00 - 05-Jul-2021';
date_default_timezone_set($SITE['timezone']);
$includeMode = isset($doInclude)?true:false;
$testMode = false;
$priorfile = '';
$saveFiles = false;
$archiveDir = $SITE['$JSONfilesDir'];
$archiveFiles = glob($archiveDir.'*.json');
$nextService = date('Y-m-d',strtotime('this sunday')).'_10';
$availableFiles = array();
$extraText = '';
if ( file_exists($archiveDir.'filelist.txt') ) {
  $t = file_get_contents($archiveDir.'filelist.txt');
  $availableFiles = unserialize( $t );
}

if(file_exists('./temp-proclaim/backup.prs')) {
	$priorfile = './temp-proclaim/backup.prs';
	$saveFiles = true;
}

header('Content-type: text/plain;charset=UTF-8');
print "$Version\n";
if(!empty($priorfile)) {
	$zip = new ZipArchive;
	$zip->open($priorfile);
	$rawJSON = $zip->getFromName('BackupPresentation.json');
	$allJSON = array();
	$allJSON = get_zipfile_json($zip);
	$zip->close();
	if(strlen($rawJSON) > 100) {
	  $JSON = json_decode($rawJSON,true);
		$fileName = $JSON['dateGiven'].'_'.substr($JSON['startTime'],0,2);
		$proclaimName = $JSON['title'];
		$availableFiles[$proclaimName] = "$archiveDir$fileName.json";
	  file_put_contents($archiveDir.$fileName.'.json',$rawJSON);
		if(count($allJSON) > 0) {
			file_put_contents($archiveDir.$fileName.'-alljson.txt',
				 serialize($allJSON) );
		}
		if(file_exists($priorfile)) {
			copy($priorfile,$archiveDir.$fileName.'.prs');
			if(file_exists($priorfile)) { unlink($priorfile); }
		}
		arsort($availableFiles);
		file_put_contents($archiveDir.'filelist.txt',serialize($availableFiles));
	}
  print ".. handled $priorfile copied to $archiveDir$fileName.prs \n";
} else {
	print ".. no ./temp-proclaim/backup.prs file found to process.\n";
}

#---------------------------------------------------------  
# scan zipfile for updated files >= sinceDate
#---------------------------------------------------------  

function get_zipfile_json ($za) {
  global $ignoreFiles;
  	
  $dirsExcluded = 0;
  $FLIST = array();
  // read the directory of the zipfile
  for ($i=0; $i<$za->numFiles;$i++) {
	  $z = $za->statIndex($i);
	  
	  $fdate = date("Y-m-d H:i T",$z['mtime']);
	  
	  $t = $z['name'].'|'.$z['size'].'|'.$z['mtime'].'|'.$fdate;
	  if($z['size'] > 0 and 
		   strpos($z['name'],'.json') !== false and
		   strpos($z['name'],'Backup') == false) { // only print non-directory entries
		$key = $z['name'];
		$FLIST[$key] = $fdate."\t".$z['size']."\t".$i;
	  } else {
		$dirsExcluded++;
	  }
  }

  ksort($FLIST);
  
  $RFLIST = array();
  
  foreach ($FLIST as $key => $val) {
	  $fname =  $key;
	  list($fdate,$size,$index) = explode("\t",$val);
	  
	  if(preg_match('/\.json$/i',$fname) and $index >= 0) {
		  $tcontents = preg_replace('|\r|is','',$za->getFromIndex($index));
			$tJ = json_decode($tcontents,true);
			if(isset($tJ['id'])) {
	      $RFLIST[$tJ['id']] = $tcontents;
			}
	  }
  }

  return($RFLIST);

} // end get_zipfile_json
