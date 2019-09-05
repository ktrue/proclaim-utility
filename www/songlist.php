<?php
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness 
# songlist.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
include_once("settings-common.php");
// header('Content-type: text/plain;charset=UTF-8');
$legend = array("Title","Author","Hymnal","CCLI","VerseOrder","Copyright");

$filesDir = './lyricsxml/';
global $songListFile;
$songListFile = 'proclaim-songlist.csv';
$saveFile = false;

if(file_exists('last-update.txt') and file_exists($songListFile)) {
	$lastUpdate = filemtime('last-update.txt');
	$listUpdate = filemtime($songListFile);
	if($listUpdate < $lastUpdate) {$saveFile = true;}
}

if(!file_exists($songListFile)) {
	$saveFile = true;
}

if(file_exists($songListFile) and !$saveFile ) { // use cached version
  $outCSV = file_get_contents($songListFile);
	offer_download('proclaim-songlist.csv',$outCSV,$saveFile);
  return;
}
# no shortcut.. need to scan 'em all

	$fileList = glob($filesDir.'*.xml');
	$outCSV = '';
	$outCSV .= rfccsv($legend)."\n";
	
	foreach ($fileList as $n => $XMLfile) {

		$outCSV .= rfccsv(get_lyrics_file($XMLfile))."\n";
	}
	
  offer_download('proclaim-songlist.csv',$outCSV,$saveFile);
	return;
	
	
function rfccsv($arr){
     foreach($arr as &$a){
         $a=strval($a);
         if(true or strcspn($a,",\"\r\n")<strlen($a))$a='"'.strtr($a,array('"'=>'""')).'"';
     }
     return implode(',',$arr);
 }

function offer_download($file,$content,$saveFile) {
	global $songListFile;
	  header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Length: ". strlen($content).";");
    header("Content-Disposition: attachment; filename=\"$file\"");
    header("Content-Type: application/octet-stream; "); 
    header("Content-Transfer-Encoding: binary");
    print $content;
		if($saveFile) {
			file_put_contents($songListFile,$content);
		}
}

function get_lyrics_file($file) {
	global $SITE;
	$rawXML = file_get_contents($file);
	$rawXML = str_replace('<br/>',"\r\n",$rawXML);
	$rawXML = str_replace('\"','"',$rawXML);
	$rawXML = str_replace('\u2019',"'",$rawXML);
	$rawXML = str_replace('\u00a9','(c)',$rawXML);
	$rawXML = str_replace('\u00ae','TM',$rawXML);
	//$rawXML = str_replace('&amp;','&',$rawXML);

  $Xsong = simplexml_load_string($rawXML);
	//print "<pre>\n".print_r($Xsong,true)." </pre>\n";
	$XML = $Xsong->properties;
	
	if(isset($XML->titles->title)) {
		$title = (string)$XML->titles->title;
	} else {$title = '';}
	if(isset($XML->verseOrder)) {
		$verseorder = (string)$XML->verseOrder;
	} else {$verseorder = '';}
	if(isset($XML->authors->author)) {
		$author = (string)$XML->authors->author;
	} else {$author = '';}
	if(isset($XML->copyright)) {
		$copyright = (string)$XML->copyright;
	} else {$copyright = '';}
	if(isset($XML->ccliNo)) {
		$ccli = (string)$XML->ccliNo;
	} else {$ccli='';}
	if(isset($XML->comments->comment)) {
		$comments = (string)$XML->comments->comment;
	} else {$comments = '';}
	if(isset($XML->songbooks->songbook)) {
		$hymnalname = (string)$XML->songbooks->songbook['name'];
		$hymnal     = (string)$XML->songbooks->songbook['entry'];
		//print "<p>Hymnalname='$hymnalname' entry='$hymnal'</p>\n";
	} else {
		$hymnalname = '';
		$hymnal = '';
	}
	$verses = '';
	/* Verse, Chorus, Bridge, Pre-Chorus, Intro, Ending and Other */
	$vtypes = array(
	'v' => 'Verse ',
	'c' => 'Chorus ',
	'b' => 'Bridge ',
	'p' => 'Pre-Chorus ',
	'i' => 'Intro ',
	'e' => 'Ending ',
	'o' => 'Other '
	);
	$k = 0;
	foreach ($Xsong->lyrics->verse as $i => $v) {
		//print "<!-- verse '".print_r($v,true). " -->\n";
/*
<!-- verse 'SimpleXMLElement Object
(
    [@attributes] => Array
        (
            [name] => v1
        )

    [lines] => Let us talents and tongues employ,
reaching out with a shout of joy;
bread is broken, the wine is poured,
Christ is spoken and seen and heard.
)
 -->
 */
   $type = (string)$v{'name'};
	 $verse = (string)$v->lines;
	 $tlong = $vtypes[substr($type,0,1)]. substr($type,1).':'."\n";
	 if($k>0) {
		 $verses .= "\n\n";
	 }
	 $verses .= $tlong.$verse;
	 $k++;
	 //print "i=$i '$type' '$verse'\n";
	}
	
	if(!empty($hymnalname)) {
		$hymn = str_replace(
		  $SITE['hymnalList'],
			$SITE['hymnalListAbbrev'],
			$hymnalname);
		$hymn .= '#'.$hymnal;
		
	} else {
		$hymn = '';
	}
	return (array($title,$author,$hymn,$ccli,$verseorder,$copyright));
}