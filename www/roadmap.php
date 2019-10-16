<?php
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness 
# roadmap.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
#
# Purpose: decode a Proclaim Presentation backup .prs file (which is .zip encoded) and process
# the BackupPresentation.json file to list the service order in HTML5 for viewing/printing.
#
# Author: Ken True - webmaster@saratoga-weather.org 
# Copyright: 2019 - Ken True/Campbell United Church of Christ, Campbell, CA, 95008
# Permission is granted to use/modify as required for your church's use.
#
# Input:  a Faithlife Proclaim Backup Presentation (*.prs) file.
# Output: HTML5 page with summary of the worship service (omitting the pre-loop, warmup and post-loop slides).
#   Output page may be viewed on any HTML5 capabile browser and printed if need be.
#
# Version 1.00 - 29-Jul-2019 - initial release
# Version 1.10 - 31-Jul-2019 - added '****' delim for Notes to include in roadmap printout
# Version 1.20 - 02-Aug-2019 - additional formatting
# Version 1.21 - 03-Aug-2019 - add support for rich-text notes
# Version 1.30 - 06-Aug-2019 - add support for lists and simplify the XML->HTML processes
# Version 1.40 - 08-Aug-2019 - change text formatting logic for text emphasis directives
# Version 1.50 - 09-Aug-2019 - add support for archival roadmaps by service date
# Version 1.60 - 30-Aug-2019 - add support for additonal stage cues
# Version 1.70 - 07-Sep-2019 - use positive indexing to select only Service slides
# Version 1.71 - 15-Oct-2019 - change starting '--' in content to blank line
# Version 1.72 - 16-Oct-2019 - fix lyrics display with embedded blank-line marker '--'
#
include_once("settings-common.php");

$Version = 'roadmap.php - Version 1.72 - 16-Oct-2019';
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
//var_export($archiveFiles);
//return;

if(isset($_GET['show'])) {
	$tFile = $archiveDir.$_GET['show'].'.json';
	if(file_exists($tFile)) {
		$priorfile = '';
		$rawJSON = file_get_contents($tFile);
	  $JSON = json_decode($rawJSON,true);
	}
} else {
	$tFile = $archiveDir.$nextService.'.json';
	if(file_exists($tFile)) {
		$priorfile = '';
		$rawJSON = file_get_contents($tFile);
	  $JSON = json_decode($rawJSON,true);
	} else {
		$_GET['list'] = 'list';
		$extraText = "<p>Sorry, the roadmap for the coming Sunday is not yet available.</p>\n";
	}
}

if(isset($_GET['list']) and !isset($_FILES['upload']['tmp_name'])) {
	do_print_header('Worship Roadmap List');
	if(!empty($extraText)) { print $extraText; }
	print "<p>Listing of available roadmaps</p>\n";
	
	print "<ul>\n";
	foreach ($availableFiles as $name => $file) {
		$link = str_replace($archiveDir,'',$file);
		$link = str_replace('.json','',$link);
		
		print "<li><a href=\"?show=$link\">$name</a></li>\n";
	}
	print "</ul>\n";
	do_print_footer("<small><small>$Version</small></small>");
	return;
}

if(!$testMode) {
	if(isset($_FILES['upload']['tmp_name'])) {
		$priorfile = $_FILES['upload']['tmp_name'];
		$saveFiles = true;
	}
} else {
 // $priorfile = 'Kens tests for features.prs';
  //$priorfile = 'August 11, 2019 Slides.prs';
//	$priorfile = 'August 4, 2019 Slides.prs';
}
header('Content-type: text/html;charset=UTF-8');

if(!empty($priorfile)) {
	$zip = new ZipArchive;
	$zip->open($priorfile);
	$rawJSON = $zip->getFromName('BackupPresentation.json');
	$zip->close();
	if(strlen($rawJSON) > 100) {
	  $JSON = json_decode($rawJSON,true);
		$fileName = $JSON['dateGiven'].'_'.substr($JSON['startTime'],0,2);
		$proclaimName = $JSON['title'];
		$availableFiles[$proclaimName] = "$archiveDir$fileName.json";
	  file_put_contents($archiveDir.$fileName.'.json',$rawJSON);
		if(is_uploaded_file($priorfile)) {
			move_uploaded_file($priorfile,$archiveDir.$fileName.'.prs');
			if(file_exists($priorfile)) { unlink($priorfile); }
		}
		arsort($availableFiles);
		file_put_contents($archiveDir.'filelist.txt',serialize($availableFiles));
	}

} else {
	//$rawJSON = file_get_contents('BackupPresentation.json');
	//$JSON = json_decode($rawJSON,true);
}

$serviceDate = date('F d, Y',strtotime($JSON['dateGiven']));

$latestModifiedDate = '';

$title = "Worship Roadmap - $serviceDate";

do_print_header($title);
print "<h3 style=\"text-align:center;margin: 0 auto !important;\">$title</h3>\n";

for ($kIndex=$JSON['startIndex'];$kIndex<$JSON['postServiceStartIndex'];$kIndex++) {
  $item = $JSON['items'][$kIndex];
	$title = $item['title'];
	$kind  = $item['kind'];
	$lmod  = $item['modifiedDate'];
	if($lmod > $latestModifiedDate) {
		$latestModifiedDate = $lmod;
	}

	$extraText = '';
	list($notes,$roadmapText) = decode_notes($item);

	if($kind == "AudioRecordingCue") {
		print "<p class=\"avcue\">Automatic: $title</p>\n";
		continue;
		
	}
	if($kind == "SongLyrics") {
		 list($lyrics,$other) = decode_lyrics($item);
		 if(strlen($roadmapText) > 0) {
			 $roadmapText .= "<br/><br/>\n";
		   $extraText = $roadmapText.$lyrics;
			 $roadmapText = '';
		 } else {
		   $extraText = $lyrics;
		 }
		 $hymn =      isset($item['content']['_textfield:Hymn Number'])?$item['content']['_textfield:Hymn Number']:'';
		 if(strlen($hymn) > 0) { 
		   $title = "Hymn: $title ($hymn)";
		 } else {
		   $title = "Song: $title";
		 }
			 
	}
	if($kind == "BiblePassage") {
		 list($bible,$other) = decode_bible_passage($item);
		 $extraText = $bible;
		 $bibleVersion = $item['content']['BibleVersion'];
	   $verses = $item['content']['_textfield:BibleReference'];
     $title .= ": $verses ($bibleVersion)";
	}
	
	if($kind == "Content") {
		 list($contentText,$other) = decode_content($item);
		 $extraText = str_replace("\n","<br/>\n",$contentText);
	}
	if($kind == "Announcement") {
	   list($contentText,$other) = decode_announcement($item);
		 $extraText = str_replace("\n","<br/>\n",$contentText);
	}
	if($kind == "WebPage") {
		$play = $item['content']['AutoPlay']=='true'?'Autoplay':'Manual play';
		$extraText = "($play of <a href=\"" . $item['content']['PageUrl']."\" target=\"_blank\">".
		             $item['content']['PageUrl']."</a> )";
	}
	if($kind == 'StageDirectionCue') {
			$rawXML = '<xmlstuff>'.(string)$item['content']['StageDirectionDetails'].'</xmlstuff>';
	    $rawXML = xml_spanfix($rawXML);

		list($extraText,$other) = xml_to_html($rawXML);
		$extraText = cleanup_html($extraText);
		$extraText = str_replace("\n","<br/>\n",$extraText);
	}

	if(strlen($notes)> 1) { // AV cues before heading
		$notes = str_replace("\n","<br/>\n",$notes);
	  print "<p class=\"avcue\">$notes</p>\n";
	}
	if(strlen($roadmapText) > 0) { // add any extra text to the roadmap
		$roadmapText = str_replace("\n","<br/>\n",$roadmapText);
		$extraText .= "<br/>\n".$roadmapText;
	}
	if($kind !== 'StageDirectionCue') {
		print "<p class=\"section\"><strong>$title</strong></p>\n";  // slide name is underlined heading
		if(strlen($extraText) > 1) { // print all following stuff in slide if need be
			$extraText = cleanup_html($extraText);
			print "<p class=\"service\">$extraText</p>\n";
		}
	} else { // special handling for stage direction
		if(strlen($extraText) > 0) {
		  print "<p class=\"stage\">($extraText)</p>\n";
		}
		if(strlen($other) > 0) {
			$other = cleanup_html($other);
			print "<p class=\"service\">$other</p>\n";
		}
	}
	print "\n";
}
$footerText = "<small><small>This roadmap generated by $Version<br/>";
$footerText .= "from Proclaim slides '<strong>".$JSON['title']."</strong>' for the <strong>".
               $JSON['startTime']."</strong> worship service.<br/>";
$footerText .= "Slide set was last modified on <strong>".date('l, F d, Y g:i:sa',strtotime($latestModifiedDate)).
               "</strong></small></small>";

do_print_footer($footerText);

# ----------------------------------------------------------
# internal functions
# ----------------------------------------------------------

function decode_notes($item) {
	if(empty($item['notes'])) {
		return(array('',''));
	}
	$rawXML = '<xmlstuff>'.(string)$item['notes'].'</xmlstuff>';
	$rawXML = xml_spanfix($rawXML);
	//$rawXML = preg_replace('!<span[^>]*>!Uis','',$rawXML);
	//$rawXML = preg_replace('!</span>!Uis','',$rawXML);
	$notesText = '';
	$roadmapText = '';
  if(strlen($rawXML) > 10) {
    list($notesText,$roadmapText) = xml_to_html($rawXML);
	}
		
	return(array($notesText,$roadmapText));
}

# ----------------------------------------------------------

function decode_lyrics($item) {

  if(!isset($item['content']['_richtextfield:Lyrics'])) {
	  return(array('',''));
  }
	$rawLyricsXML = '<xmlstuff>'.(string)$item['content']['_richtextfield:Lyrics'].'</xmlstuff>';
	$rawLyricsXML = xml_spanfix($rawLyricsXML);
	//$rawLyricsXML = preg_replace('!<span[^>]*>!Uis','',$rawLyricsXML);
	//$rawLyricsXML = preg_replace('!</span>!Uis','',$rawLyricsXML);
	$copyright = isset($item['content']['_textfield:Copyright'])?$item['content']['_textfield:Copyright']:'';
	$hymn =      isset($item['content']['_textfield:Hymn Number'])?$item['content']['_textfield:Hymn Number']:'';
	$useVerseOrder = isset($item['content']['CustomOrderSlides'])?$item['content']['CustomOrderSlides']:'';
	$verseOrder = isset($item['content']['CustomOrderSequence'])?$item['content']['CustomOrderSequence']:'';
	$lyricsText = '';
	$other = '';
  if(strlen($rawLyricsXML) > 10) {
    list($lyricsText,$other) = xml_to_html($rawLyricsXML,false);
	}
	
	$formattedSong = format_song($lyricsText,$verseOrder,$copyright,$hymn);
	$formattedSong = str_replace("\n","<br/>\n",$formattedSong);
	return (array($formattedSong,$other));
	
}

# ----------------------------------------------------------

function decode_content($item) {
		// print "\n-------------\n".print_r($item,true)."\n-------------\n";
  if(empty($item['content']['_richtextfield:Main Content'])) {
	  return(array('',''));
  }
  $rawContent = '<xmlstuff>'.trim((string)$item['content']['_richtextfield:Main Content']).'</xmlstuff>';
	$rawContent = xml_spanfix($rawContent);
  $main = '';
	$extra = '';
	
  if(strlen($rawContent) > 10 ) {
		list($main,$extra) = xml_to_html($rawContent);
		
	}
	return( array( trim($main),trim($extra) ) );
}

# ----------------------------------------------------------

function decode_announcement($item) {
  //print "\n-------------\n".print_r($item,true)."\n-------------\n";

  if(empty($item['content']['_richtextfield:Description'])) {
	  return(array('',''));
  }

  $rawContent = '<xmlstuff>'.trim((string)$item['content']['_richtextfield:Description']).'</xmlstuff>';
	$rawContent = xml_spanfix($rawContent);
  $main = '';
	$extra = '';
	
  if(strlen($rawContent) > 10 ) {
		list($main,$extra) = xml_to_html($rawContent);
		
	}
	return( array( trim($main),trim($extra) ) );

}

# ----------------------------------------------------------

function decode_bible_passage($item) {
	if(!isset($item['content']['_richtextfield:Passage'])) {
		return('');
	}
	
	$rawContent = '<bible>'.(string)$item['content']['_richtextfield:Passage'].'</bible>';
	$rawContent = xml_spanfix($rawContent);
	//$rawContent = preg_replace('!<span[^>]+>!Uis','',$rawContent);
	//$rawContent = preg_replace('!</span>!Uis','',$rawContent);
	$contentText = '';
  $main = '';
	$extra = '';
	
  if(strlen($rawContent) > 10 ) {
		list($main,$extra) = xml_to_html($rawContent);
		
	}
	$main = str_replace("\n","<br/>\n",trim($main));
	return( array( trim($main),trim($extra) ) );
/*	
	$contentText = str_replace("\n","<br/>\n",trim($contentText));
	return($contentText);
*/
}

# ----------------------------------------------------------

function xml_spanfix($inXML) {
	$t = $inXML;
	
  # create printable for debugging use
	$p = str_replace('<','&lt;',$t);
	$p = str_replace('>',"&gt;\n",$p);
	if(isset($_GET['debug']) and strlen($p) > 3 ) {
	  print "<pre style=\"border: 1px blue solid;font-size: 10pt;\">Before:\n\n$p</pre>\n";
	}
	# processing: 
  # remove all the Language=".." attributes (mostly from <span> elements
	$t = preg_replace('! Language="[^"]+"!Uis','',$t);

	# associate a <span> with font to the following <run> directive
	$t = preg_replace('|<Span Font([^>]+)>\s*<Run([^>]+)>\s*</Span>|Uis',"<Run Font$1$2>",$t);
	
	# remove all remaining <span> and </span> tags
	$t = preg_replace('!<span>!Uis','',$t);
	$t = preg_replace('!</span>!Uis','',$t);

  # create printable for debugging use
	$p = str_replace('<','&lt;',$t);
	$p = str_replace('>',"&gt;\n",$p);
	if(isset($_GET['debug'])) {
		if(strlen($p) < 3) {$p = 'empty'; }
	  print "<pre style=\"border: 1px blue solid;font-size: 10pt;\">After:\n\n$p</pre>\n";
	}
	return ($t);
}

# ----------------------------------------------------------

function format_song($lyricsText,$verseOrder,$copyright,$hymn) {
	$out = '';
	$text = str_replace("\n--\n","<br/>\n",trim($lyricsText));
	//$text = trim($lyricsText);
	$text = str_replace("\n\n",'|',$text);
	$rawVerses = explode("|",trim($text)."|");
	$Verses = array();

	  # create printable for debugging use
	$p = str_replace('<','&lt;',var_export($rawVerses,true));
	$p = str_replace('>',"&gt;\n",$p);
	if(isset($_GET['debuglyrics'])) {
		if(strlen($p) < 3) {$p = 'empty'; }
	  print "<pre style=\"border: 1px blue solid;font-size: 10pt;\">Before: lyricsText\n".var_export($lyricsText,true)."\n\nrawVerses:\n\n$p</pre>\n";
	}

  foreach ($rawVerses as $i => $v) {
		$t = explode("\n",trim($v)."\n");  // first one is the name of the verse
		if(strlen($t[0]) > 0) {
			$tv = $t;
			$junk = array_shift($tv);
		  $Verses[trim($t[0])] = join("\n",$tv);
		}
	}
	if(strlen($verseOrder) > 0) {
	  $useVerses = explode(", ",$verseOrder.', ');
	} else {
		$useVerses = array_keys($Verses);
	}
	#fix leading \n in useVerses
	$tv = $useVerses;
	foreach ($tv as $i => $v) {
		$useVerses[$i] = trim($v);
	}
  # create printable for debugging use
	$p = str_replace('<','&lt;',var_export($useVerses,true));
	$p = str_replace('>',"&gt;\n",$p);
	if(isset($_GET['debuglyrics'])) {
		if(strlen($p) < 3) {$p = 'empty'; }
	  print "<pre style=\"border: 1px blue solid;font-size: 10pt;\">After: useVerses\n\n$p\nVerses:\n".var_export($Verses,true)."</pre>\n";
	}
	
//	$out .= "--------\nDebug:\n";
//	$out .= "VerseOrder: $verseOrder\n";
//	$out .= "useVerses: ".print_r($useVerses,true)."\n";
	foreach ($useVerses as $i => $key) {
		if(isset($_GET['debuglyrics'])) {
			$out .= "([$i]='$key')";
		}
		if(isset($Verses[$key])) {
			$out .= $Verses[$key]. "\n";
		}
	}

//	$out .= print_r($Verses,true)."\n";
	$out .= "<small>$copyright</small>\n";
	
//	$out .= "--------\n";
	
	
	return($out);
}

# ----------------------------------------------------------

function xml_to_html($XML,$removeBlankLines=true) {
  
	# main routine to parse the XML, extract and apply HTML formatting to the text 
	# by walking the array of the XML in the array $vals from xml_parse_into_struct call

	$rawXML = str_replace('\"','"',$XML);
	$errormessage = '';

	//$rawXML = str_replace('‘',"'",$rawXML);
	$p = xml_parser_create('UTF-8');
	$rc = xml_parse_into_struct($p, $rawXML, $vals, $index);
	if($rc == 0) {
		$at = xml_get_current_byte_index($p);
		$lineno = xml_get_current_line_number($p);
		$colnum = xml_get_current_column_number($p);
		$errcode = xml_get_error_code($p);
		$errText = xml_error_string($errcode);
		$txt = substr($rawXML,$at-10,11);
		$lines = explode("\n",$rawXML);
		
		$errormessage .= "<pre>XML error after char $at : '".$txt."'\n";
		$errormessage .= "Line: ".$lineno." Column: ".$colnum."\n";
		//$errormessage .= $lines[$lineno]."\n";
		$errormessage .= "RC=$rc errcode=$errcode '$errText'</pre>\n";
		$errormessage .= "</pre>\n";
		print $errormessage;
	}
	
	xml_parser_free($p);
	//echo "Index array\n";
	//print_r($index);
	//echo "\nVals array\n";
	//print_r($vals);
	
	
	$output = '';
	$listClose = '';
	$inListItem = false;
	$closedList = false;
	$paraFont = false;
	$paraBold = false;
	$paraItalic = false;
	$paraCaps = false;
	$paraColor = false;
	$paraColorValue = '';
	
	foreach ($vals as $i => $T) {
	
	if($T['tag'] == 'LIST' and $T['type'] == 'open') {
		$inListItem = true;
		$closedList = false;
		if($T['attributes']['KIND'] == 'Disc') {
			$listClose = '</ul>';
			$output .= "<ul>\n";
		} else {
			$listClose = "</ol>\n";
			$output .= "<ol>\n";
		}
	}
	if($T['tag'] == 'LIST' and $T['type'] == 'close') {
	  $output .= $listClose;
		$listClose = '';
		$inListItem = false;
		$closedList = true;
	}
	
	if($T['tag'] == 'LISTITEM' and $T['type'] == 'open') {
		$output .= "<li>";
		$inListItem = true;
	}
	if($T['tag'] == 'LISTITEM' and $T['type'] == 'close') {
		$output .= "</li>\n";
		$inListItem = false;
	}
	
	if($T['tag'] == 'RUN') {
		$S = '';
		$E = '';
		list($runBold,$runItalic,$runCaps,$runColorValue,$runSet) = text_attrib($T);
		$doBold = false;
		$doItalic = false;
		$doCaps = false;
		$doColorValue = '';
		
		if($paraSet and !$runSet ) { // use paragraph attributes
			$doBold = $paraBold;
			$doItalic = $paraItalic;
			$doCaps = $paraCaps;
			$doColorValue = $paraColorValue;
		}
		
		if($runSet) { // override with Run values
		  $doBold = $runBold;
			$doItalic = $runItalic;
			$doCaps = $runCaps;
			$doColorValue = $runColorValue;
		}
  # apply the formatting as needed
		if($doItalic) {
			$S .= '<em>';
			$E = '</em>'. $E;
		}
		if($doBold) {
			$S .= '<strong>';
			$E = '</strong>'. $E;
		}
		if($doCaps) {
			$S .= '<span style="text-transform: uppercase;font-size: smaller;">';
			$E = '</span>'. $E;
		}
		if(strlen($doColorValue)>0) {
			$S .= '<span style="color: #'.$doColorValue.';">';
			$E = '</span>'. $E;
		}
		$rText = $T['attributes']['TEXT'];
		if(trim($rText) === '--' and $removeBlankLines) {
			$rText = ''; // remove new-page markers
		}
		$output .= $S.$rText.$E;
	}
	
	
	if($T['tag'] == 'PARAGRAPH' and $T['type'] !== 'close') { // paragraph open tag
    list($paraBold,$paraItalic,$paraCaps,$paraColorValue,$paraSet) = text_attrib($T);
    $paraColor = strlen($paraColorValue)>0?true:false;
	}
	
	if($T['tag'] == 'PARAGRAPH' and $T['type'] == 'close') {
#		$output .= "<br>\n";
		$paraFont = false;
		$paraBold = false;
		$paraItalic = false;
		$paraCaps = false;
		$paraColor = false;
		$paraColorValue = '';
	}
	if($T['tag'] == 'PARAGRAPH' and $T['type'] == 'close' and ! $inListItem and $closedList) {
		$closedList = false;
	}

	if($T['tag'] == 'PARAGRAPH' and $T['type'] !== 'close') {
    $output .= "\n";
	}
}

  list($main,$extra) = explode('****',$output.'****');
	return(array(trim($main),trim($extra)));
	
}

# ----------------------------------------------------------

function text_attrib($T) {
	$bold = false;
	$italic = false;
	$caps = false;
	$color = '';
	$anySet = false;
	
	if( isset($T['attributes']['FONTITALIC']) ) {
	 $italic = $T['attributes']['FONTITALIC'] == 'True'?true:false;
	 $anySet = true;
 	}
	if(isset($T['attributes']['FONTBOLD']) ) {
		$bold = $T['attributes']['FONTBOLD'] == 'True'?true:false;
		$anySet = true;
	}
	if(isset($T['attributes']['FONTCAPITALS']) ) {
		$caps = $T['attributes']['FONTCAPITALS'] == 'SmallCaps'?true:false;
		$anySet = true;
	}
	if(isset($T['attributes']['FONTCOLOR']) ) {
		$color = get_rgb($T['attributes']['FONTCOLOR']);
		$anySet = true;
	}

  return(array($bold,$italic,$caps,$color,$anySet));
	
}
# ----------------------------------------------------------

function get_rgb($in) {
	if(strlen($in) == 8) {
		$out = substr($in,2,6);
	} else {
		$out = '000000'; // black is the default
	}
	if(isset($_REQUEST['debugcolor'])) {
	 list($rH,$gH,$bH) = str_split($out,2);
	 $r = (integer)hexdec($rH);
	 $g = (integer)hexdec($gH);
	 $b = (integer)hexdec($bH);
	 $rHex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
	 $gHex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
	 $bHex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
	 
	 echo "input FontColor='$in' output RGB='$out' rgb($r,$g,$b)<br>\n";
	}
	return($out);
}

# ----------------------------------------------------------

function cleanup_html($input) {
	$t = str_replace('li><br/>','li>',$input);
	$t = str_replace('ol><br/>','ol>',$t);
	$t = str_replace('ul><br/>','ul>',$t);
	return($t);
	
}

# ----------------------------------------------------------

function do_print_header($title) {
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="author" content="Ken True">
<meta name="copyright" content="&copy; 2019, CampbellUCC.org">
<meta name="Keywords" content="worship service roadmap faithlife proclaim">
<meta name="Description" content="Display worship service roadmap from backup Proclaim .prs slide backup">
<meta name="Robots" content="index,nofollow">
<title><?php echo $title; ?></title>
<style>
<!--
body {
	font-family: Arial, Helvetica, sans-serif;
  font-size: 16pt;
	background-color: #fff;
	margin: 0;
	padding: 0;
	color: #000;
  /*width: 600px; */
}

/* ~~ Element/tag selectors ~~ */
h1, h2, h3, h4, h5, h6, p, div, ol, ul, dl {
	margin-top: 5px;	 
	padding-right: 5px;
	padding-left: 5px; 
}

ol, ul, dl {
  padding-left: 40px;
  padding-right: 10px;
}
li {
  padding-left: 0px;
  padding-right: 10px;
}

a img { 
	border: none;
}

a:link {
	color: #42413C;
	text-decoration: underline; 
}
a:visited {
	color: #6E6C64;
	text-decoration: underline;
}
a:hover, a:active, a:focus { 
	text-decoration: none;
}

/* ~~ this fixed width container surrounds the other divs ~~ */
.container {
	/*width: 800px; */
	background-color: #FFF;
	/*margin: 0 auto; /* the auto value on the sides, coupled with the width, centers the layout */
}

.header {
	background-color: #ADB96E;
}

/* ~~ This is the layout information. ~~ 
*/

.content {
	padding: 10px 0;
}

.avcue {
  text-align: center;
  color: #39F;
  font-weight: bold;
  margin: 5px auto !important;
}
.service {
  text-align: left;
  color: #000;
  margin-bottom: 1em;
}

.section {
  text-align: left;
  color: #000;
  text-decoration: underline;
  font-weight: bold;
}
.stage {
  text-align: center;
  color: #C30;
  font-weight: normal;
  font-style: italic;
  margin: 5px auto !important;
}

/* ~~ The footer ~~ */
.footer {
	padding: 10px 0;
	background-color: #CCC49F;
}

-->
</style>
</head>

<body>
<div class="container">
  <div class="header"> 
    <!-- end .header -->
  </div>
  <div class="content">

<?php
}

# ----------------------------------------------------------

function do_print_footer($text) {
?>
    <!-- end .content --></div>
  <div class="footer">
    <p><?php echo $text; ?></p>
    <!-- end .footer --></div>
  <!-- end .container --></div>
</body>
</html>
<?php	
}