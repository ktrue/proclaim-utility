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
# Version 1.73 - 22-Oct-2019 - added CCLI song+license display to songs in roadmap output.
# Version 1.80 - 23-Oct-2019 - add support for imported PPT native and slide images
# Versopm 1.81 - 03-Dec-2019 - fix CCLI song display
# Version 1.82 - 19-Feb-2020 - fix CCLI song display (again)
# Version 1.90 - 24-Jun-2021 - add support for audio and video listings
# Version 1.91 - 19-Jul-2021 - added ?avtech CSS option
# Version 1.92 - 20-Jul-2021 - added ?summary CSS option
# Version 1.93 - 23-Jul-2021 - added Updated: at top of display, vsignal index
# Version 1.94 - 24-Jul-2021 - additional info on Lighting Signals
# Version 1.95 - 26-Jul-2021 - added display of AutoAdvance slides in titles with vsignal format
# Version 1.96 - 30-Jul-2021 - changes for Video switching support via MIDI Lighting Scenes
# Version 1.97 - 03-Aug-2021 - avtech display tweaks for readability
# Version 1.98 - 17-Sep-2021 - hymn# override of CCLI data on display
# Version 1.99 - 22-Sep-2021 - change display of signals.txt entries
# Version 1.100 - 08-Oct-2021 - improve HTML comments on Lighting Signals found
# Version 1.101 - 01-Nov-2021 - improve listing for local Video media display
# Version 1.102 - 02-Nov-2021 - improve ?summary display for Songs: w/verses and copyright info display
# Version 1.103 - 14-Dec-2021 - some minor HTML fixes for better validation
# Version 1.104 - 22-Dec-2021 - style copyright info as italic
# Version 1.105 - 31-Dec-2021 - add details to Summary for Announcement with Prelude/Postlude/Anthem
# Version 1.106 - 16-Jan-2022 - add detail of audio file to Summary for Song file 
# Version 1.107 - 16-Mar-2022 - add verse# display in ?avtech listing
# Version 1.200 - 09-Apr-2022 - add highlight feature for Pastor, Lay Reader, Song Leader, Comm. Asst.
# Version 1.201 - 29-Apr-2022 - minor tweak to <title> when highlighting is used
# Version 1.202 - 23-May-2022 - add display of audio track in Announcement type slide 
# Version 1.203 - 09-Jun-2022 - add Video info on Summary display
# Version 1.204 - 19-Jun-2022 - modified <title> for better information display
# Version 1.205 - 06-Jul-2022 - added ?listall and 2 month filter for ?list for past roadmap displays
# Version 1.206 - 06-Jul-2022 - highlight next Sunday in ?list and ?listall
# Version 1.207 - 19-Oct-2022 - highlight for Stage Direction text after **** in roadmap display
# Version 1.208 - 19-Dec-2022 - modify logic to look for next service
# Version 1.209 - 22-Feb-2023 - added Author/Credit display to SongLyrics
# Version 1.210 - 17-Apr-2023 - added ?avdetail option and simplified ?avtech to omit signals display
# Version 1.211 - 18-Sep-2023 - fixed Notice errata re $item['content']['AutoPlay'] missing
# Version 1.212 - 14-Nov-2023 - fixed Notice errata on $play
# Version 1.213 - 22-Mar-2024 - fixed Song notes to honor new-lines
# Version 1.214 - 25-Mar-2024 - added avtech display for scene and mixer unmuted channels
# Version 1.215 - 27-Mar-2024 - added audio decoding for pre/warm-up/service/post loops to avsummary
# Version 1.216 - 15-Nov-2024 - added verse displays to songs in song leader highlight display


include_once("settings-common.php");

global $lookfor;
$lookfor = array( # service participants in open text
	'lay reader:'  => 'L', # Note: Scripture will generally be highlited for Lay Reader/Lay Leader
	'lay leader:'  => 'L',
#	'congregation:'  => 'L',
	'song leader:' => 'S', # Note: Songs will always be highlited for Song Leader
	'pastor:'		  => 'P',
#	'Chancel:'		  => 'P',
#	'communion assistant:' => 'C',
);


$Version = 'roadmap.php - Version 1.216 - 15-Nov-2024';
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
if(isset($SITE['summaryAnnounce'])) {
	$summaryAnnounce = $SITE['summaryAnnounce'];
} else {
	$summaryAnnounce = array('prelude','postlude','anthem');
}
if ( file_exists($archiveDir.'filelist.txt') ) {
  $t = file_get_contents($archiveDir.'filelist.txt');
  $availableFiles = unserialize( $t );
}
file_put_contents($archiveDir.'filelist-full.txt',var_export($archiveFiles,true));
//return;
$nowYMD = date('Y-m-d');

foreach ($archiveFiles as $i => $fn) {
	$t = str_replace($archiveDir,'',$fn);
	if(substr($t,0,10) >= $nowYMD) {
		$nextService = str_replace('.json','',$t);
		break;
	}
}

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
		$extraText = "<p>Sorry, the roadmap for the coming Sunday ($nextService) is not yet available.</p>\n";
	}
}

$aFile = str_replace('.json','-alljson.txt',$tFile);
$allJSON = array();
if (file_exists($aFile)) { $allJSON = unserialize(file_get_contents($aFile)); }

if((isset($_GET['list']) or isset($_GET['listall'])) and !isset($_FILES['upload']['tmp_name'])) {
	$filterList = isset($_GET['listall'])?false:true;
	if($filterList) {
		$displayOldest = date('Y-m-d',strtotime('-2 months'));
		$Oldest = "List of available roadmaps starting $displayOldest ";
	} else {
		$displayOldest = '2018-01-01';
		$Oldest = "List of all roadmaps";
	}
	do_print_header('Worship Roadmap List',$Oldest);
	if(!empty($extraText)) { print $extraText; }
	print "<p>$Oldest</p>\n";
	
	print "<ul>\n";
	foreach ($availableFiles as $name => $file) {
		$link = str_replace($archiveDir,'',$file);
		$tDate = substr($link,0,10);
		if($tDate < $displayOldest) { continue; }
		$link = str_replace('.json','',$link);
		print "<!-- tDate = '$tDate' nextService = '$nextService' -->";
		if($tDate == substr($nextService,0,10)) {
			$tNextStart = "<strong>Next Service: ";
			$tNextEnd   = "</strong>";
		} else {
			$tNextStart = "";
			$tNextEnd   = "";
		}
		print "<li>$tNextStart<a href=\"?show=$link\">$name Roadmap</a> | <small><small> ";
		print "<a href=\"?show=$link&amp;avtech\">A/V operator</a> | ";
		print "<a href=\"?show=$link&amp;summary\">Summary/Outline</a> | Highlite: ( ";
		print "<a href=\"?show=$link&amp;hilite=past\">Pastor</a> | ";
		print "<a href=\"?show=$link&amp;hilite=lay\">Lay Reader</a> | ";
		print "<a href=\"?show=$link&amp;hilite=song\">Song Leader</a>";
#		print "<a href=\"?show=$link&amp;hilite=ca\">Comm. Asst.</a> ";
		print ")";
		print " | <a href=\"?show=$link&amp;avdetail\">detailed A/V cues</a>";
		print " | <a href=\"?show=$link&amp;avsummary\">summary A/V cues</a>";
		print "</small></small>$tNextEnd</li>\n";
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
$Signals = array();
if(file_exists("./signals-list.txt")) {
	include_once("./signals-list.txt");
} else {
	$SignalsList = array();
}

$SignalsListType = array();
$MixerChan = array();
$OBSscene = 'Undefined';

global $SignalsListType,$MixerChan,$OBSscene;

foreach ($SignalsList as $key => $val) {
	/*
	# customized for Campbellucc list which appears as
	'192ef931-90c3-4fd4-99e3-2fa725dca27f' => 'signal: OBS scene change to <strong>Standby</strong>',
	'48297e2f-8be5-4fa5-852c-cbc8a174cf8d' => 'signal: sound mixer <strong>MUTE 1 Choir Left</strong>',
  '12e87e16-ad7b-4545-b542-741782d8cca0' => 'signal: sound mixer <strong>UNMUTE 1 Choir Left</strong>',
*/
  if(strpos($val,' OBS ') !== false) {
		if(preg_match('|<strong>(.*)</strong>|i',$val,$M)) {
			$SignalsListType[$key] = "OBS|".$M[1];
		}
	}
	
	if(strpos($val,' mixer ') !== false) {
		if(preg_match('|<strong>(\S+) ([\d-]+) (.*)</strong>|i',$val,$M)) {
			$SignalsListType[$key] = "MIX|".$M[1]."|".$M[2]."|".$M[3];
			$MixerChan[$M[2]] = array('unk',$M[3]);
		}
	}
}

for ($kIndex=$JSON['startIndex'];$kIndex<$JSON['postServiceStartIndex'];$kIndex++) {
  $item = $JSON['items'][$kIndex];
	$lmod  = $item['modifiedDate'];
	if($lmod > $latestModifiedDate) {
		$latestModifiedDate = $lmod;
	}
}
$highlite= '';
global $highlite;

if (isset($_GET['hilite']) ) {
	$t = strtolower($_GET['hilite']);
	$highlite = (strpos($t,'lay')  !== false)?'L':$highlite;
	$highlite = (strpos($t,'song') !== false)?'S':$highlite;
	$highlite = (strpos($t,'pas')  !== false)?'P':$highlite;
	$highlite = (strpos($t,'ca')   !== false)?'C':$highlite;
}

$title = "Worship Roadmap - $serviceDate";
if(isset($_GET['avtech'])) {
	$title = "A/V Operator - " . $title;
}
if(isset($_GET['avdetail'])) {
	$title = "A/V Details - " . $title;
}
if(isset($_GET['summary'])) {
	$title = "Summary " . $title;
}
if(isset($_GET['avsummary'])) {
	$title = "A/V Summary " . $title;
}
if($highlite == 'L') {
	$title .= "<br><small>Highlighted for <mark>Lay Reader</mark></small>";
}
if($highlite == 'S') {
	$title .= "<br><small>Highlighted for <mark>Song Leader</mark></small>";
}
if($highlite == 'P') {
	$title .= "<br><small>Highlighted for <mark>Pastor</mark></small>";
}
if($highlite == 'C') {
	$title .= "<br><small>Highlighted for <mark>Communion Assistant</mark></small>";
}

do_print_header($title,'Updated: '.date('l, F d, Y g:i:sa',strtotime($latestModifiedDate)));

if(isset($_GET['avsummary']) or isset($_GET['avtech'])) {
  print "<!-- SignalsListType = \n".var_export($SignalsListType,true). " -->\n";
  print "<!-- MixerChan = \n".var_export($MixerChan,true). " -->\n";
}

print "<h3 style=\"text-align:center;margin: 0 auto !important;\">$title</h3>\n";
print "<h5 style=\"text-align:center;margin: 0 auto !important;\">Updated: ".date('l, F d, Y g:i:sa',strtotime($latestModifiedDate))."</h5>\n";
print "<!-- ".count($allJSON)." allJSON entries loaded -->\n";
if(isset($_GET['avsummary'])) {
	$kIndexStart = 0;
	$kIndexEnd = count($JSON['items']);
} else {
	$kIndexStart =  $JSON['startIndex'];
	$kIndexEnd  =  $JSON['postServiceStartIndex'];
}
print "<!-- processing entries $kIndexStart up to $kIndexEnd -->\n";
for ($kIndex=$kIndexStart;$kIndex<$kIndexEnd;$kIndex++) {
  $item = $JSON['items'][$kIndex];
	$title = $item['title'];
	$kind  = $item['kind'];
	$lmod  = $item['modifiedDate'];
  if(isset($_GET['avsummary'])) {
		print_avsection(
		  $kIndex,
		  $JSON['preServiceStartIndex'],
		  $JSON['startIndex'],
		  $JSON['postServiceStartIndex'],
		  count($JSON['items'])
		);
	}
	$extraText = '';
	list($notes,$roadmapText) = decode_notes($item);

	if($kind == "AudioRecordingCue") {
		print "<p class=\"vsignal\"><small><em>[Proclaim event: $title]</em></small></p>\n";
		continue;
		
	}
	if($kind == "SongLyrics") {
		 list($lyrics,$other) = decode_lyrics($item);
		 if(strlen($roadmapText) > 0) {
       $roadmapText = do_highlite($roadmapText,$title);
			 $roadmapText = str_replace("\n","<br>\n",$roadmapText);
			 $roadmapText .= "<br><br>\n";
		   $extraText = $roadmapText.$lyrics;
			 $roadmapText = '';
		 } else {
		   $extraText = $lyrics;
		 }
		 $hymn =      isset($item['content']['_textfield:Hymn Number'])?$item['content']['_textfield:Hymn Number']:'';
		 if(strlen($hymn) > 0) { 
		   $title = "Song: \"<em>$title</em>\" ($hymn)";
		 } else {
		   $title = "Song: \"<em>$title</em>\"";
		 }
		 if(isset($_GET['summary']) or isset($_GET['avsummary'])) {
			 $title .= "<br>$other";
		 }
		 if(isset($_GET['avdetail'])) {
			 $title .= "<br><p class=\"vsignal\"$other</p>";
		 }
		 if(isset($item['content']['Audio'])) {
			 $tJ = json_decode($item['content']['Audio'],true);
			 if(isset($tJ['audioTracks']) and count($tJ['audioTracks']) > 0) {
				$t = get_audio_info($tJ['audioTracks'],$allJSON);
				if(isset($item['content']['AutoPlay'])) {
		      $play = $item['content']['AutoPlay']=='true'?'Autoplay':'Manual play';
				} else {
		      $play = '';
				}
				if(isset($_GET['avtech']) or isset($_GET['avdetail'])) {
			    $extraText = " <small><em>[$play Audio Track $t]</em></small><br>" . $extraText;
				}
				if(isset($_GET['summary']) or isset($_GET['avsummary'])) {
					$title .= '<span style="font-size: 12px;color: green;display: block; padding-left: 2em;">';
					$title .= "<small><em>[$play Audio Track $t]</em></small></span>";
				}
			 }
		 }
			 
	}
	if($kind == "BiblePassage") {
		 list($bible,$other) = decode_bible_passage($item);
		 if(preg_match('!^Scripture!i',$title) ) {
			 $extraText = do_highlite($bible,"Lay Reader: ".$title);
		 } else {
			 $extraText = do_highlite($bible,$title);
		 }
		 $bibleVersion = $item['content']['BibleVersion'];
	   $verses = $item['content']['_textfield:BibleReference'];
     $title .= ": $verses ($bibleVersion)";
	}
	
	if($kind == "Content") {
		 list($contentText,$other) = decode_content($item);
		 $contentText = do_highlite($contentText,$title);
		 $extraText = str_replace("\n","<br>\n",$contentText);
	}
	if($kind == "Announcement") {
	   list($contentText,$other) = decode_announcement($item);
		 $contentText = do_highlite($contentText,$title);
		 $extraText = str_replace("\n","<br>\n",$contentText);
		 if((isset($_GET['summary']) or isset($_GET['avsummary'])) 
		    and is_array($summaryAnnounce) and !empty($summaryAnnounce[0]) ) {
			 foreach ($summaryAnnounce as $i => $tstr) {
				 if(stripos($title,$tstr) !== false) {
					 $title .= " <em>".str_replace("\n",', ',$contentText)." </em>";
					 break;
				 }
			 }
			 
		 }
		 if(isset($item['content']['Audio'])) {
			 $tJ = json_decode($item['content']['Audio'],true);
			 if(isset($tJ['audioTracks']) and count($tJ['audioTracks']) > 0) {
				$t = get_audio_info($tJ['audioTracks'],$allJSON);
		    if(isset($item['content']['AutoPlay'])) {
  		    $play = $item['content']['AutoPlay']=='true'?'Autoplay':'Manual play';
			    $extraText = " <small><em>[$play Audio Track $t]</em></small><br>" . $extraText;
				} else {
					$play = '';
			    $extraText = " <small><em>[Audio Track $t]</em></small><br>" . $extraText;
				}
				if(isset($_GET['summary']) or isset($_GET['avsummary'])) {
					$title .= '<span style="font-size: 12px;color: green;display: block; padding-left: 2em;">';
					$title .= "<small><em>[$play Audio Track $t]</em></small></span>";
				}
			 }
		 }

	}
	if($kind == "WebPage") {
		if(isset($item['content']['AutoPlay'])) {
  		$play = $item['content']['AutoPlay']=='true'?'Autoplay':'Manual play';
		  $extraText = "($play of <a href=\"" . $item['content']['PageUrl']."\" target=\"_blank\">".
		             $item['content']['PageUrl']."</a> )";
		} else {
		  $extraText = "(webpage <a href=\"" . $item['content']['PageUrl']."\" target=\"_blank\">".
		             $item['content']['PageUrl']."</a> )";
		}
		
	}
	if($kind == 'PowerPointDriven') {
		if(isset($item['content']['AutoPlay'])) {
			$play = ($item['content']['AutoPlay']=='true')?'Autoplay':'Manual play';
		  $extraText = "($play of " . $item['content']['FilePath']." )";
		} else {
			$extraText = "(".$item['content']['FilePath']." )";
		}
	}
	if($kind == 'Video') {
		$play = (isset($item['content']['AutoPlay']) and $item['content']['AutoPlay']=='true')?'Autoplay':'Manual play';
		$t = get_video_info($item['media'],$allJSON);
		if($t == '') {
			$t = get_local_video_info($item['content'],$allJSON);
		}
		$extraText .= " <small><em>[$play of Video$t]</em></small><br>";
		if(isset($item['content']['VideoEndOptions'])) {
					$endOpt = $item['content']['VideoEndOptions'];
		} else {
			$endOpt = '';
		}
		if($endOpt == 'AutoAdvance') {
			$title .= " <span class=\"vsignal\"><em>[Note: will auto advance to next slide at end of video]</em></span>";
		}
		if(isset($_GET['summary']) or isset($_GET['avsummary'])) {
					$title .= '<span style="font-size: 12px;color: green;display: block; padding-left: 2em;">';
					$title .= "<small><em>[$play Video $t]</em></small></span>";
		}

	}
	if($kind == 'StageDirectionCue') {
			$rawXML = '<xmlstuff>'.(string)$item['content']['StageDirectionDetails'].'</xmlstuff>';
	    $rawXML = xml_spanfix($rawXML);

		list($extraText,$other) = xml_to_html($rawXML);
		$extraText = cleanup_html($extraText);
		$extraText = str_replace("\n","<br>\n",$extraText);
	}
	if(strlen($notes)> 1) { // AV cues before heading
		$notes = str_replace("\n","<br>\n",$notes);
		$notes = cleanup_html($notes);
	  print "<p class=\"avcue\">$notes</p>\n";
	}
  if(isset($item['signals']) and count($item['signals']) > 0) {
		$txt = decode_signal($item,$kIndex);
		if(strlen($txt) > 1 and isset($_GET['avdetail'])) {
		 print "<p class=\"vsignal\">$txt</p>\n";
		}
	}
	if(strlen($roadmapText) > 0) { // add any extra text to the roadmap
	  $roadmapText = do_highlite($roadmapText,$title);
		$roadmapText = str_replace("\n","<br>\n",$roadmapText);
		$extraText .= "<br>\n".$roadmapText;
	}
	$autoAdvanceText = decode_autoadvance($item);
	if(strlen($autoAdvanceText) > 0) {
		$autoAdvanceText = "  <span class=\"vsignal\"><small>(".$autoAdvanceText.")</small></span>";
	}
	
	if($kind !== 'StageDirectionCue') {
		$avstate = isset($_GET['avsummary'])?display_avstate():'';
		print "<p class=\"section\">$avstate<strong>$title</strong>$autoAdvanceText\n<!-- end class=section -->\n</p>\n";  // slide name is underlined heading
		if(strlen($extraText) > 1) { // print all following stuff in slide if need be
		  print "<!-- extraText='$extraText' -->\n";
			$extraText = cleanup_html(trim($extraText));
			print "<p class=\"service\"><!-- extraText -->$extraText\n<!-- end class=service -->\n</p>\n";
		}
	} else { // special handling for stage direction
		if(strlen($extraText) > 0) {
		  print "<p class=\"stage\">($extraText)\n<!-- end class=stage -->\n</p>\n";
		}
		if(strlen($other) > 0) {
			$other = cleanup_html($other);
			$other = do_highlite($other,$title);
			$other = str_replace("\n","<br>\n",$other);
			if(substr($other,0,9) == '</strong>') {
				$other = substr($other,10);
			}
			print "<p class=\"service\"><!-- other -->$other\n<!-- end class=service -->\n</p>\n";
		}
	}
	print "\n";
}
$footerText = "<small><small>This roadmap generated by $Version<br>";
$footerText .= "from Proclaim slides '<strong>".$JSON['title']."</strong>' for the <strong>".
               $JSON['startTime']."</strong> worship service.<br>";
$footerText .= "Slide set was last modified on <strong>".date('l, F d, Y g:i:sa',strtotime($latestModifiedDate)).
               "</strong></small></small>";
# print "<!-- Lighting Signals Found\n".var_export($Signals,true)." -->\n";
print "<!-- Lighting Signals found\n\n";

foreach ($Signals as $key => $list) {
	print "key='".$key."'";
	if(isset($SignalsList[$key])) { 
	  print "\n  (as '".$SignalsList[$key]."')\n";
	} else {
		print "\n  (key is not in signals-list.txt)\n";
	}
	print "  found on slide: title\n";
	foreach ($list as $i => $val) {
		print "    $val\n";
	}
	print "\n";
}
print " -->\n";

if(count($SignalsList) > 0) {
	print "<!-- Lighting Signals known via ./SignalsList.txt\n".var_export($SignalsList,true)." -->\n";
}
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
	$copyright  = isset($item['content']['_textfield:Credits'])?$item['content']['_textfield:Credits'].'; ':'';
	$copyright .= isset($item['content']['_textfield:Copyright'])?$item['content']['_textfield:Copyright']:'';
	$hymn =      !empty($item['content']['_textfield:Hymn Number'])?$item['content']['_textfield:Hymn Number']:'';
  if(strlen(trim($hymn)) < 1 and !empty($item['content']['_textfield:Song Number'])) {
		$copyright .= ' CCLI Song #'.$item['content']['_textfield:Song Number'];
	}
	if(strlen(trim($hymn)) < 1 and !empty($item['content']['_textfield:License Number']) and 
	   !empty($item['content']['_textfield:Song Number']) ) {
		$copyright .= ' used under CCLI license #'.$item['content']['_textfield:License Number'];
	}
	$useVerseOrder = isset($item['content']['CustomOrderSlides'])?$item['content']['CustomOrderSlides']:'';
	$verseOrder = isset($item['content']['CustomOrderSequence'])?$item['content']['CustomOrderSequence']:'';
	$lyricsText = '';
	$other = '<span style="font-size: 12px;color: green;display: block; padding-left: 2em;">';
	if($verseOrder == '') {
		$other .= '[all verses]'; 
	} else { 
	  $other .= '['.$verseOrder.']'; 
	}
	if(!isset($_GET['avtech'])) {
	  $other .= "<br><span style=\"font-style: italic;\">$copyright</span></span>";
	} else {
		$other .= "</span>";
	}
	
  if(strlen($rawLyricsXML) > 10) {
    list($lyricsText,$other2) = xml_to_html($rawLyricsXML,false);
	}
	
	$formattedSong = format_song($lyricsText,$verseOrder,$copyright,$hymn);
	$formattedSong = str_replace("\n","<br>\n",$formattedSong);
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
	$main = str_replace("\n","<br>\n",trim($main));
	return( array( trim($main),trim($extra) ) );
/*	
	$contentText = str_replace("\n","<br>\n",trim($contentText));
	return($contentText);
*/
}

# ----------------------------------------------------------

function decode_signal($item,$slideNumber) {
	global $Signals,$SignalsList,$SignalsListType,$MixerChan,$OBSscene;
/*
SignalsListType = 
array (
  '192ef931-90c3-4fd4-99e3-2fa725dca27f' => 'OBS|Standby',
  '88661193-b6ef-4b58-9ffa-32570cddb20d' => 'OBS|Slides only',
  'baffb97c-3abe-434d-990d-d7b7dbaa1671' => 'OBS|Lectern-Wide',
  'c1514aab-786e-4dc4-a5a2-ef0c1beafd61' => 'OBS|Lectern-Wide+Slides',
  '831196ad-d216-46db-a19f-57321007076c' => 'OBS|Lectern-Zoom',
  '0466979b-0542-44bb-b605-a7122b4b852c' => 'OBS|Lectern-Zoom+Slides',
  'a1c024ce-3d00-4282-abf4-a5b94add8739' => 'OBS|Piano-Choir',
  '42cb04d0-0bb4-46b5-9392-a1722e234f3f' => 'OBS|Piano-Choir+Slides',
  'c8db08a3-4a6c-446c-a641-798ffc038657' => 'OBS|Ending',
  '48297e2f-8be5-4fa5-852c-cbc8a174cf8d' => 'MIX|MUTE|1|Choir Left',
  '12e87e16-ad7b-4545-b542-741782d8cca0' => 'MIX|UNMUTE|1|Choir Left',
  '87786834-07c8-4fcc-b8dc-fe4d44a3106f' => 'MIX|MUTE|2|Choir Right',
  '53ce7297-238d-4908-bffd-6a121c99a90b' => 'MIX|UNMUTE|2|Choir Right',
  '0a031103-6112-42a1-b0fc-12616b78129b' => 'MIX|MUTE|3|Guitar',
  'a8c331a0-fa8c-4adc-b9eb-046b68e5f8e7' => 'MIX|UNMUTE|3|Guitar',
  '9a71381a-325b-4fe8-8128-7b49de7d5d0f' => 'MIX|MUTE|4|Podium Mic',
  '59058d25-1205-492b-826b-032f94076150' => 'MIX|UNMUTE|4|Podium Mic',
  '9e22cba9-74a4-44df-82ca-1143ae23d28a' => 'MIX|MUTE|5|Piano Vocal Mic',
  '058de6b5-5715-476c-81df-0cf5b8060e0d' => 'MIX|UNMUTE|5|Piano Vocal Mic',
  '3596876e-3abc-47da-a081-a46d092f5cee' => 'MIX|MUTE|8|Red Mic (OLD)',
  '2403d64c-0eb6-441e-9f7b-24ae6a68eabd' => 'MIX|MUTE|8|Red Mic',
  '76c35bed-7860-43fd-9f4a-f6c233171fea' => 'MIX|UNMUTE|8|Red Mic',
  '585e9733-e7c3-4804-bb57-c9230493d068' => 'MIX|MUTE|9|Yellow Mic',
  '5f607710-7172-43ad-887c-5a4e54c05ba2' => 'MIX|UNMUTE|9|Yellow Mic',
  '4fcfc798-e502-4679-8d67-3a022076af44' => 'MIX|MUTE|10|Blue Mic',
  '2d97db14-8791-42fb-a171-40402d62a310' => 'MIX|UNMUTE|10|Blue Mic',
  'f7da4cf5-11c6-456e-875f-aad3d7fed016' => 'MIX|MUTE|11|Song Leader Mic',
  '11f33e69-e1fc-43bf-bf49-56d38dac2fd3' => 'MIX|UNMUTE|11|Song Leader Mic',
  'ff2f5c5f-0418-48e1-aadb-1266af708761' => 'MIX|MUTE|12|Lay Reader Mic',
  '437c78f2-e8c4-45e9-8c4a-5eb84b8050fe' => 'MIX|UNMUTE|12|Lay Reader Mic',
  '8e098337-81b8-4521-a754-f98bc14a3c5a' => 'MIX|MUTE|13|Pastor Mic',
  '1d2cfdf9-6765-4b25-b898-bffeb8ba727e' => 'MIX|UNMUTE|13|Pastor Mic',
  '7a529b21-af13-4b60-88dc-93a5726e8f67' => 'MIX|MUTE|14|Comm. Assist. Mic',
  '3ea027ca-4160-47a9-855d-c4fba6a4c6a5' => 'MIX|UNMUTE|14|Comm. Assist. Mic',
  'e7eb9e32-30aa-47d3-b40c-f94a0f6e83a3' => 'MIX|MUTE|15-16|CD Audio L-R',
  '027762d3-6a3c-4918-9828-c63958426dae' => 'MIX|UNMUTE|15-16|CD Audio L-R',
  'fd79ef32-a856-4031-8174-a3c51fd10ad4' => 'MIX|MUTE|17-18|Stereo MiniJack L-R',
  'e9dcfd93-a9d0-4d47-ad36-02a7202f37b8' => 'MIX|UNMUTE|17-18|Stereo MiniJack L-R',
)
*/	
	$out = '';
	if(!isset($item['signals']) or count($item['signals']) < 1) {
		return '';
	}
	foreach ($item['signals'] as $i => $signal) {
/*
        {
          "id": "741c22b7-09b9-4bb2-9b0b-361d243b90ea",
          "signalKind": "SceneSignal",
          "parameters":
          {
            "SceneId": "0466979b-0542-44bb-b605-a7122b4b852c",
            "FirePerSlide": "false"
          },
          "validationState": "none",
          "isValid": false
        }
*/
		if ($signal['signalKind'] !== 'SceneSignal') { continue; }
		$out .= "<!-- signal: \n".var_export($signal,true)." -->\n";
		//$out .= 'Signal for ID: '.$signal['id'].' ';
		$key = $signal['parameters']['SceneId'];
		$title = $item['title'];
		if(isset($SignalsList[$key])) {
			$out .= "[".$SignalsList[$key]."]<br> ";
		} else {
		  $out .= '[Signal: SceneId: '.$signal['parameters']['SceneId'].'] ';
		}
		#$out .= $signal['parameters']['FirePerSlide']=="true"?'Fire-Per-Slide':'Fire-Once';
		$out .= "<!-- (validationState=".$signal['validationState']." ;";
		$out .= "isValid=";
		$out .= $signal['isValid']==true?'true':'false';
		$out .= ") --> \n";
		if(!isset($Signals[$key])) {
			$Signals[$key]= array("$slideNumber: $title");
		} else {
			$Signals[$key][] = "$slideNumber: $title";
		}
		if(isset($SignalsListType[$key]) ) {
			$S = $SignalsListType[$key];
			if(substr($S,0,3) == 'OBS') {
				# OBS Signal - 'OBS|Lectern-Zoom+Slides'
				list($type,$view) = explode('|',$S);
				$OBSscene = $view;
			} # end OBS type
			
			if(substr($S,0,3) == 'MIX') {
				# MIX Signal - 'MIX|UNMUTE|13|Pastor Mic'
				list($type,$action,$channel,$name) = explode('|',$S);
				if($action == 'MUTE' or $action == 'UNMUTE') {
					$state = ($action == 'UNMUTE')?'ON':'off';
				} else{
					$state = 'unk';
				}
				$MixerChan[$channel][0] = $state;
				
			} # end MIX type
			
		} # end one signal processing
	}
	$out .= "<br>\nCurrent Scene: <b>$OBSscene</b><br>\nMixer UNMUTED: ";
	foreach ($MixerChan as $chan => $S) {
		if($S[0] == 'ON') {
		  $out .= $S[1]."(<b>$chan</b>) ";
		}
	}
	$out .= "<br>\n";
	
	return $out;
}
# ----------------------------------------------------------

function display_avstate() {
	global $OBSscene,$MixerChan;
	$out = "<span class=\"avstate\">A/V Scene: \"<span class=\"avstateb\">$OBSscene</span>\" - Mixer UNmuted: ";
	foreach ($MixerChan as $chan => $S) {
		if($S[0] == 'ON') {
		  $out .= $S[1]."(<span class=\"avstateb\">$chan</span>) ";
		}
	}
	$out .= "<br></span>\n";
  return $out;
}
# ----------------------------------------------------------

function decode_autoadvance($item) {
	$out = '';
/*
    {
      "id": "7579573e-34c1-4865-8ca4-e27e07d4c38c",
      "kind": "Content",
      "title": "Goodbye",
      "modifiedDate": "2021-07-19T02:37:46Z",
      "content":
      {
        "ShowCountdownClock": "false",
        "CountdownTime": "0001-01-01T00:00:00+00:00",
        "AdvanceWhenCountdownEnds": "Hide",
        "AutoAdvance": "true",
        "AutoAdvanceTime": "PT3S",
        "Shuffle": "false",
        "Repeat": "false",
*/	
  if(isset($item['content']['AutoAdvance']) and $item['content']['AutoAdvance']=="true") {
		$out .= "Note: <strong>".$item['title']."</strong> slide will ";
		$autoTimeInterval = $item['content']['AutoAdvanceTime'];
		$interval = new DateInterval($autoTimeInterval);
		$out .= 'Advance after '.$interval->format('%s seconds');
		//$out .= 'advance after '.$autoTimeInterval;
		if($item['content']['Repeat']=="true") {
			$out .= ", and repeat";
		} else {
			$out .= "";
		}
	}
	return($out);
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
	$text = str_replace("\n--\n","<br>\n",trim($lyricsText));
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
  global $highlite;
	if($highlite == 'S') {
		$out = '<mark>'.trim($out).'</mark><br><br>';
	}
//	$out .= print_r($Verses,true)."\n";
	$out .= "<span style=\"font-size: 11pt;font-style:italic;\">$copyright</span>\n";
	
//	$out .= "--------\n";
	
	$verseString = ($highlite == 'S')?"<span class=\"verses\">[$verseOrder]</span>\n\n":'';
	return($verseString.$out);
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
	$listCount = 0;
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
		$listCount++;
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
		$listCount--;
		if($listCount < 1) {
		  $listClose = '';
		  $inListItem = false;
		  $closedList = true;
		}
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
	if(isset($_GET['debugcolor'])) {
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
	$t = str_replace('li><br>','li>',$input);
	$t = str_replace('ol><br>','ol>',$t);
	$t = str_replace('ul><br>','ul>',$t);
	$t = str_replace('<p></p>','',$t);
	$t = str_replace('<strong></strong>','',$t);
	$t = str_replace("\n</strong>\n",'',$t);
	$t = str_replace('<em></em>','',$t);
	$t = preg_replace('!^<br>\s+!is','',$t);
	$t = preg_replace('!<br>\s+$!is','',$t);
	if($t == '<strong>') {$t = '';}
	return($t);
	
}

# ----------------------------------------------------------

function do_print_header($title,$subtitle='') {
	$useTitle = str_replace('<br>',' - ',$title);
	$useTitle = strip_tags($useTitle);
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="author" content="Ken True">
<meta name="copyright" content="&copy; <?php echo gmdate('Y'); ?>, CampbellUCC.org">
<meta name="Keywords" content="worship service roadmap faithlife proclaim">
<meta name="Description" content="Worship service roadmap <?php echo $subtitle; ?>">
<meta name="Robots" content="index,nofollow">
<title><?php echo $useTitle; ?></title>
<style>
<!--
<?php print_css(); ?>
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
	  
	  if(preg_match('/\.(json)$/i',$fname) and $index >= 0) {
		  $tcontents = preg_replace('|\r|is','',$za->getFromIndex($index));
			$tJ = json_decode($tcontents,true);
			if(isset($tJ['id'])) {
	      $RFLIST[$tJ['id']] = $tcontents;
			}
	  }
  }

  return($RFLIST);

} // end get_zipfile_json

#---------------------------------------------------------  
# scan text and highlite if requested
#---------------------------------------------------------  

function do_highlite($intxt,$title) {
	global $highlite;
	if($highlite == '' or
	   stripos($title,'credit') !== false ) {
		 return($intxt);
	}

	$whoDoesSlide = who_does($title);
	print "<!-- do_highlite title='$title'\n whoDoesSlide='$whoDoesSlide' -->\n";
	$matches = preg_split('!\n\n!Uis',$intxt."\n\n");
	if($matches == false) {
		return($intxt);
	}
	
	$newtxt = '';
	$doHighlite = false;
	
	foreach ($matches as $i => $txt) {
		if(strlen(trim($txt)) < 1) {continue;}
		$whoDoesText = who_does(trim($txt));
		print "<!-- do_highlite txt whoDoesText='$whoDoesText' -->";
		$doHighlite = false;
#		if($highlite = '') {
#			$newtxt .= $txt . "\n\n";
#			continue;
#		}
		if($whoDoesSlide == $highlite) {$doHighlite = true; }
#		if($whoDoesSlide == $highlite and 
#		   $whoDoesText !== '' and $whoDoesText !== $highlite) {$doHighlite = $doHighlite; }
		if($whoDoesText == $highlite) {$doHighlite = true; }
		
		if($doHighlite and stripos($txt,'<li>') !== false) { # handle lists differently than plain text
			$t = str_replace("<li>\n",'<li><mark>',$txt);
			$t = str_replace('</li>','</mark></li>',$t);
			$newtxt .= $t;
			continue;
		}
		if ($doHighlite) { # plain text highlite
			$newtxt .= "<mark>".trim($txt).'</mark>'."\n\n";
		} else {
			$newtxt .= $txt . "\n\n";
		}
	}
	$newtxt = str_replace('<mark><strong></strong></mark>','',$newtxt);
	$newtxt = str_replace('<mark><em></em></mark>','',$newtxt);
	$newtxt = str_replace('<mark></mark>','',$newtxt);
	
	return(trim($newtxt));
} # end do_highlite

#---------------------------------------------------------  
# determine who does based on title or text for highlite
#---------------------------------------------------------  

function who_does($text) {
	global $lookfor;
	
	foreach ($lookfor as $key => $who) {
		if(stripos($text,$key) !== false) {
			return($who);
		}
	}
	return('');
}

#---------------------------------------------------------  

function get_audio_info($list,$allJSON) {
	$t = '';
	foreach ($list as $i => $key) {
		if(isset($allJSON[$key])) {
			$tJ = json_decode($allJSON[$key],true);
			if(isset($tJ['audio']['title'])) {
				$t .= " '".$tJ['audio']['title'];
			}
			if(isset($tJ['audio']['audioFile']['mediaFileExtensions'][0]['extension'])) {
				$t .= ".".$tJ['audio']['audioFile']['mediaFileExtensions'][0]['extension'];
			}
			$t .= "' ";
			if(isset($tJ['audio']['duration'])) {
				$t .= "(".$tJ['audio']['duration'].")";
			}
			
		}
		
	}
	
	return($t);
}

# ----------------------------------------------------------

function get_local_video_info($content,$allJSON) {
	$t = '';
	foreach ($content as $key => $data) {
		if(strpos($key,'localfile') !==false) {
			print "<!-- get_local_video_info key='$key'\n".var_export($data,true)." -->\n";
			list($junk,$num,$name) = explode(':',$key);
			$tJ = json_decode($data,true);
/*
{
  "name": "B51.2_ The Twenty-Fifth Sunday after Pentecost, Year B (2018)",
  "path": "d7cf1f25-ca68-4a99-b775-9cb167128a7f.mp4",
  "videoPreviewImagePath": "5476f2fc-2e5f-4adb-820d-6c6043a4304e.png",
  "loopVideo": false,
  "videoDuration": "00:11:48",
  "isEmptyForegroundImagePlaceholder": false
}
*/
			list($path,$extension) = explode('.',$tJ['path']);
			
			if(isset($allJSON[$path])) {
				print "<!-- allJSON path='$path'\n".var_export($allJSON[$path],true)." -->\n";
			} else {
				print "<!-- allJSON path='$path' not found -->\n";
			}
			
			$t = " \"<strong>".$tJ['name'].'.'.$extension."\"</strong> (local) ".$tJ['videoDuration'];
			if($tJ['loopVideo']) {
				$t .= " loop at end";
			}
		}
	}
	return($t);
}

# ----------------------------------------------------------

function get_video_info($list,$allJSON) {
	$t = '';
	$foundKey = false;
	foreach ($list as $i => $key) {
		if(isset($allJSON[$key])) {
			$foundKey = true;
			$tJ = json_decode($allJSON[$key],true);
			print "<!-- get_video_info key='$key'\n".var_export($tJ,true)." -->\n";
			if(isset($tJ['about']['name'])) {
				$t .= " '".$tJ['about']['name'];
			}
			if(isset($tJ['formats']['FourByThree']['fullMediaFile']['mediaFileExtensions'][0]['extension'])) {
				$t .= ".".$tJ['formats']['FourByThree']['fullMediaFile']['mediaFileExtensions'][0]['extension'];
			}
			$t .= "' ";
			if(isset($tJ['formats']['FourByThree']['videoInfo']['duration'])) {
				$t .= "(".$tJ['formats']['FourByThree']['videoInfo']['duration'].")";
			}
			
		}
		
	}
//  
  if(!$foundKey) {
		print "<!-- get_video_info .. allJSON ".var_export($list,true)." not found -->\n";
	}
	return($t);
}


# ----------------------------------------------------------

function 	print_avsection(
		  $kIndex,
		  $preServiceStartIndex,
		  $startIndex,
		  $postServiceStartIndex,
		  $itemCount
		) {
	$type = '';
	$dashes = str_repeat('-',15);
	if($kIndex == 0 and 
	   $preServiceStartIndex > 0) {
		$type = "PreService Loop";
		$audio = decode_section_audio('PreServiceLoopAudio');
	}
  if($kIndex == $preServiceStartIndex) {
		$type = "Warm-Up";
		$audio = decode_section_audio('PreServiceAudio');
	}
	if($kIndex == $startIndex) {
		$type = "Service";
		$audio = decode_section_audio('ServiceAudio');
	}
	if($kIndex == $postServiceStartIndex) {
		$type = "PostService Loop";
		$audio = decode_section_audio('PostServiceAudio');
	}
	if ($type == '') { return; }
	print "<p class=\"avservice\">$dashes $type $dashes </p>\n";
	if(strlen($audio) > 0) {
		print "<p class=\"avstate\">$audio</p>\n";
	}
			
}

# ----------------------------------------------------------

function decode_section_audio($key) {
	global $JSON,$allJSON;
/*	
  "content": {

    "PreServiceLoopAudio": "{\"setting\":0,\"continueSetting\":0,\"audioTracks\":[],\"uploadJobIds\":[],\"isDividerItem\":true,\"overrideUseAudio\":false,\"localFileContents\":{}}",
    "PreServiceLoopShuffleAudio": "false",

    "PreServiceAudio": "{\"setting\":0,\"continueSetting\":0,\"audioTracks\":[],\"uploadJobIds\":[],\"isDividerItem\":true,\"overrideUseAudio\":false,\"localFileContents\":{}}",
    "WarmUpShuffleAudio": "false",

    "ServiceAudio": "{\"setting\":0,\"continueSetting\":0,\"audioTracks\":[],\"uploadJobIds\":[],\"isDividerItem\":true,\"overrideUseAudio\":false,\"localFileContents\":{}}",
    "ServiceLoopShuffleAudio": "false",

    "PostServiceAudio": "{\"setting\":0,\"continueSetting\":0,\"audioTracks\":[],\"uploadJobIds\":[],\"isDividerItem\":true,\"overrideUseAudio\":false,\"localFileContents\":{}}",
    "PostServiceLoopShuffleAudio": "false"
  }
	
*/

  $out = '';
	if(isset($JSON['content'][$key])) {
		
		$tJ = json_decode($JSON['content'][$key],true);
		if(count($tJ['audioTracks']) > 0) {
		  $out .= "<!-- Audio for $key<br> = <pre class=\"avstate\">".var_export($tJ,true)."</pre> -->\n";
		} else {
			$out .= "No Audio tracks for $key";
		}
		$br = '';
		foreach ($tJ['audioTracks'] as $i => $id) {
			if(isset($allJSON[$id])) {
				$track = json_decode($allJSON[$id],true);
				$ext = isset($track['audio']['audioFile']['mediaFileExtensions'][0]['extension'])?
				 ".".$track['audio']['audioFile']['mediaFileExtensions'][0]['extension']:'';
				$out .= "<!-- <br><br>id $id:<br><pre class=\"avstate\">".var_export($track,true)."</pre> -->\n";
				$out .= isset($track['audio']['title'])?"{$br}PLAY: \"<span class=\"avstateb\">".$track['audio']['title']."$ext</span>\"":'';
				$out .= isset($track['audio']['duration'])?" (duration ".substr($track['audio']['duration'],0,10).")":'';
				$out .= isset($track['audio']['artist'])?"<br>Artist: ".$track['audio']['artist']:'';
				$out .= isset($track['audio']['album'])?"<br>Album: ".$track['audio']['album']:'';
				$out .= isset($track['audio']['trackNumber'])?" Track: ".$track['audio']['trackNumber']:'';
			}
			$br = '<br>';
		}

	} # end .. got a key
  return($out);
}

# ----------------------------------------------------------

function print_css() {
	if(isset($_GET['avtech']) or isset($_GET['avdetail'])) {
    print '/* AVTECH/AVDETAIL special CSS for printing */

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
  padding-left: 20px;
  padding-right: 10px;
}
li {
  padding-left: 0px;
  padding-right: 10px;
  margin-left: 2em;
  font-size: 12pt;
}
li ul {
  padding-left:0px;
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
  font-size: 12pt;
}

/* ~~ This is the layout information. ~~ 
*/

.content {
	padding: 10px 0;

}

.avcue {
  text-align: left;
  color: #39F;
  font-weight: bold;
  margin: 5px auto !important;
}
.service {
  text-align: left;
  color: #000;
  margin-bottom: 1em;
  margin-left: 2em;
  font-size: 12pt;
}

.section {
  text-align: left;
  color: #000;
  text-decoration: underline overline; 
  font-weight: bold;
  margin-left: 0em;
  font-size: 16pt;

}
.stage {
  text-align: center;
  color: #C30;
  font-weight: normal;
  font-style: italic;
  margin: 5px auto !important;
  font-size: 12pt;
}
.vsignal {
	text-align: left;
	color: green;
	font-weight: normal;
	font-style: italic;
	font-size: 12pt;
}
.verses {
  display: none;
}

/* ~~ The footer ~~ */
.footer {
	padding: 10px 0;
	background-color: #CCC49F;
}		
';	
  return;	
	} 

	elseif (isset($_GET['summary'])) {
		
    print '/* summary CSS for order-of-worship summary only */

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
	display: none;
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
	display: none;
}
.avstate {
  color: #39F;
  font-weight: bold;
  margin: 5px auto !important;
	display: none;
}
.avstate {
	color: brown;
	font-size:9pt;
	font-style:normal;
	font-weight: normal;
	padding-left:20px;
  text-align: left;
}
.avstateb {
	color: red;
	font-size:10pt;
	font-style: normal;
	font-weght: bold;
}

.service {
  text-align: left;
  color: #000;
  margin-bottom: 1em;
	display: none;
}

.section {
  text-align: left;
  color: #000;
  text-decoration: none;
  font-weight: lighter;
	padding: 0px !important;
	margin: 5px !important;
}

.stage {
  text-align: center;
  color: #C30;
  font-weight: normal;
  font-style: italic;
  margin: 5px auto !important;
	display: none;
}
.vsignal {
	display: none;
}
.verses {
  display: none;
}
/* ~~ The footer ~~ */
.footer {
	padding: 10px 0;
	background-color: #CCC49F;
}
';
  return;
} 
	elseif(isset($_GET['avsummary'])) {
		
    print '/* summary CSS for order-of-worship sumary and AVState only */

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
	display: none;
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
	display: none;
}

.avstate {
	color: gray;
	font-size:9pt;
	font-style:normal;
	font-weight: normal;
	padding-left:20px;
  text-align: left;
	display: block;
}
.avstateb {
	color: red;
	font-size:10pt;
	font-style: normal;
	font-weight: bold;
}

.avservice {
	color: blue;
	font-size: 12pt;
	font-weight: bold;
}

.service {
  text-align: left;
  color: #000;
  margin-bottom: 1em;
	display: none;
}

.section {
  text-align: left;
  color: #000;
  text-decoration: none;
  font-weight: lighter;
	padding: 0px !important;
	margin: 5px !important;
}

.stage {
  text-align: center;
  color: #C30;
  font-weight: normal;
  font-style: italic;
  margin: 5px auto !important;
	display: none;
}
.vsignal {
	display: none;
}
.verses {
  display: none;
}

/* ~~ The footer ~~ */
.footer {
	padding: 10px 0;
	background-color: #CCC49F;
}
';
  return;
} else {

		print '/* normal CSS for general printing */
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
	display: none;
}
.service {
  text-align: left;
  color: #000;
  margin-bottom: 1em;
}
.mark {
  background-color: yellow !important;
  color: black !important;
	box-sizing: content-box !important;
	display: inline !important;
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
.vsignal {
	display: none;
}
.verses {
	text-align: left;
	color: green;
	font-weight: normal;
	font-style: italic;
	font-size: 12pt;
}

/* ~~ The footer ~~ */
.footer {
	padding: 10px 0;
	background-color: #CCC49F;
}
';
}
	
}
