<?php
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness 
# make-openlp-inc.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
error_reporting(E_ALL);
ini_set('display_error','1');
include_once("settings-common.php");
/*
see: https://openlyrics.org/dataformat.html#basic-structure
for basic XML structure of the song file output.
Also http://api.openlp.io/api/openlp/plugins/songs/lib/openlyricsxml.html

The following verse “types” are supported by OpenLP:

        v
        c
        b
        p
        i
        e
        o

The verse “types” stand for Verse, Chorus, Bridge, Pre-Chorus, Intro, Ending and Other. Any numeric value is allowed after the verse type. The complete verse name in OpenLP always consists of the verse type and the verse number. If not number is present 1 is assumed. 
*/
$includeMode = isset($doInclude)?true:false;
$Version = 'make-openlp-inc.php - Version 1.12 - 01-Jul-2021';
$vars = array('priorfile','title','author','copyright','ccli','hymnal','hymnalname','verseorder','notes','verses');

$lyricsXMLfiles = glob($SITE['lyricsXMLdir'].'*.xml');

if(isset($_REQUEST['debug'])) { print "<pre>POST \n".var_export($_POST,true) . "\n</pre>";}

foreach ($vars as $i => $varname) {
	$$varname = '';
	if(isset($_POST["$varname"])) {
		$$varname = $_POST["$varname"];
	}
}

if(isset($_FILES['upload']['tmp_name'])) {
	$priorfile = $_FILES['upload']['tmp_name'];
} 

if (isset($_POST['uploadlocal']) and isset($_POST['uploadprior']) ) {
	 $tidx = trim($_POST['uploadlocal']) -1;
	 print "<!-- ridx='$tidx' -->\n";
	 if( isset($lyricsXMLfiles[$tidx]) ) {
		 $priorfile = $lyricsXMLfiles[$tidx];
		 print "<!-- priorfile='$priorfile' selected -->\n";
	 }
}

if(!empty($priorfile) and 
    file_exists($priorfile) and 
		!isset($_POST['download'])) { // load up the defaults from a prior saved file
  $rawXML = file_get_contents($priorfile);
	$rawXML = str_replace('<br/>',"\r\n",$rawXML);
	$rawXML = str_replace('\"','"',$rawXML);
	#$rawXML = str_replace('\u2019',"'",$rawXML);
	#$rawXML = str_replace('\u201c','"',$rawXML);
	#$rawXML = str_replace('\u201d','"',$rawXML);
	#$rawXML = str_replace('\u2029','',$rawXML);
	#$rawXML = str_replace('\u00a9','(c)',$rawXML);
	#$rawXML = str_replace('\u00ae','TM',$rawXML);
	#$rawXML = str_replace('&amp;','&',$rawXML);

  $Xsong = simplexml_load_string($rawXML);
	//print "<pre>\n".print_r($Xsong,true)." </pre>\n";
	$XML = $Xsong->properties;
	
	if(isset($XML->titles->title)) {
		$title = (string)$XML->titles->title;
	}
	if(isset($XML->verseOrder)) {
		$verseorder = (string)$XML->verseOrder;
	}
	if(isset($XML->authors->author)) {
		$author = (string)$XML->authors->author;
	}
	if(isset($XML->copyright)) {
		$copyright = (string)$XML->copyright;
	}
	if(isset($XML->ccliNo)) {
		$ccli = (string)$XML->ccliNo;
	}
	if(isset($XML->comments->comment)) {
		$ccli = (string)$XML->comments->comment;
	}
	if(isset($XML->songbooks->songbook)) {
		$hymnalname = (string)$XML->songbooks->songbook['name'];
		$hymnal     = (string)$XML->songbooks->songbook['entry'];
		//print "<p>Hymnalname='$hymnalname' entry='$hymnal'</p>\n";
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
   $type = (string)$v['name'];
	 $verse = (string)$v->lines;
	 $tlong = $vtypes[substr($type,0,1)]. substr($type,1).':'."\n";
	 if($k>0) {
		 $verses .= "\n\n";
	 }
	 $verses .= $tlong.$verse;
	 $k++;
	 //print "i=$i '$type' '$verse'\n";
	}
}
date_default_timezone_set($SITE['timezone']);
$timeStamp = date('c');
// print "<pre>".print_r($_POST,true)."</pre>\n";
if(isset($_POST['download'])  and
   isset($_POST['convertto']) and 
	 $_POST['convertto'] == 'OpenLP') { // do the conversion
//print "<p>Download XML entered</p>\n";
$output = "<?xml version='1.0' encoding='UTF-8'?>\n";
# note: don't change createdIn= or modifiedIn= contents below or Proclaim may not import the song correctly
$output .= '<song xmlns="http://openlyrics.info/namespace/2009/song" version="0.8" createdIn="OpenLP 2.4.6"' .
           ' modifiedIn="OpenLP 2.4.6" modifiedDate="'.$timeStamp.'">
  <properties>
    <titles>
      <title>'.utf8fix($title).'</title>
    </titles>
    <verseOrder>'.utf8fix($verseorder).'</verseOrder>
    <authors>
      <author>'.utf8fix($author).'</author>
    </authors>
    <copyright>'.utf8fix($copyright).'</copyright>
    <ccliNo>'.utf8fix($ccli).'</ccliNo>
';
if(strlen($hymnal)>0 ){
	$output .= '	  <songbooks>
		  <songbook name="'.utf8fix($hymnalname).'" entry="'.utf8fix($hymnal).'"></songbook>
		</songbooks>
';
}
$output .= '  </properties>
';
  if(strlen($notes) > 0) {
		$output .= "  <comments>\n";
		$output .= "    <comment>".utf8fix($notes)."</comment>\n";
		$output .= "  </comments>\n";
	}
  $output .= gen_xml_lyrics($verses);
  $output .= '
</song>
';
	$outFileName = str_replace('.','',utf8fix($title).' ('.utf8fix($author)).').xml';
	if(strlen( trim("$title") . trim("$author")) > 0 and isset($_POST['download'])) {
		//print "<p>offer_download outfile='$outfile' content length='".strlen($output)."' bytes.</p>\n";
		offer_download($outFileName,$output);
		return;
	}
	//print "<p>Download XML done.</p>\n";
}

if(isset($_POST['download'])  and
   isset($_POST['convertto']) and 
	 $_POST['convertto'] == 'Mediashout') { // do the conversion
  list($lyrics,$defaultorder) = gen_txt_lyrics($verses);
	$useorder = !empty($verseorder)?fixup_txt_verseorder($verseorder):$defaultorder;

  $output = 
'Title: '.$title.'
Author: '.$author.'
Copyright: '.$copyright.'
CCLI: '.$ccli.'
Song ID: 
Hymnal: '.$hymnal.'
Notes: '.$notes.'
PlayOrder: '.$useorder.'

';
  $output .= $lyrics;

	$outFileName = str_replace('.','',utf8fix($title).' ('.utf8fix($author)).').txt';
	if(strlen( trim("$title") . trim("$author")) > 0) {
		$outFileName = str_replace('"','',$outFileName);
		$outFileName = str_replace('?','',$outFileName);
		$outFileName = str_replace("'",'',$outFileName);

		offer_download($outFileName,$output);
		return;
	}
} // end mediashout .txt
//header('Content-type: text/html;charset=UTF8');
?><?php if(!$includeMode) { ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Music Lyrics Export Format Generator</title>
<style type="text/css">
body {
  font-family:Arial, Helvetica, sans-serif;
  color: black;
  background-color: white;
}
.label {
  width: 20% !important;
  text-align: right !important;
}
.input {
  text-align: left;
  width: 80%;
}
.button {
  text-align: center;
}
</style>
</head>
<body>
<?php } // end ! includeMode ?>
<div class="w3-codespan">
This utility is for use with Proclaim projection software to generate an XML file for
importing song lyrics into Proclaim as a Song file for the worship service.<br />
</div>

<h1>Music Lyrics Export Format Generator</h1>

<form action="#" method="post" multipart="" enctype="multipart/form-data">
<table style="border: 1px black solid; width: 800px;">
<tr>
  <td colspan="3" class="button">Note: you can upload a local OpenLP .xml song file for reprocessing.<br/>
  Just use the Browse... button, select the local .xml file, or select a server file from the drop-down list,<br/>then press Load OpenLP button to load this form with the song file for further editing if desired.</td>
</tr>
<tr>
  <td class="input">Local file:<br /><input type="file" accept=".xml" name="upload"></td>
  <td class="input">Server file:<br /><select name="uploadlocal" style="max-width: 250px; width: 250px;">
<?php
  print "<option value=\"0\">--select server file--</option>\n"; 
  foreach ($lyricsXMLfiles as $n => $fname) {
		$idx = $n+1;
		$tfname = format_filename_display($fname);
		print "<option value=\"$idx\">$tfname</option>\n";
	}
?>  
  </select>
  </td>
  <td class="button"><br/><input type="submit" value="Load OpenLP .xml file" name="uploadprior"></td>
  </tr>
</table>
</form>
<p>&nbsp;</p>
<form action="#" method="post">
<table style="border: none; width: 800px;">
<tr>
 <td class="label"><label for="convertto">Output format:</label></td>
 <td class="input"><select name="convertto">
  <option selected="selected" value="OpenLP">OpenLP XML</option>
  <!-- option value="Mediashout">MediaShout TXT</option -->
  </select><!-- br/>Note: Use OpenLP XML for import into Proclaim. -->
  </td>
</tr>
<tr>
 <td class="label"><label for="title">Title:</label></td>
 <td class="input"><input type="text" name="title" size="80" value="<?php echo $title; ?>" /></td>
</tr> 
<tr>
 <td class="label"><label for="author">Author:</label></td>
 <td class="input"><input type="text" name="author" size="80" value="<?php echo $author; ?>"/></td>
</tr>  
</td>
<tr>
 <td class="label"><label for="copyright">Copyright:</label></td>
 <td class="input"><input type="text" name="copyright" size="80" value="<?php echo $copyright; ?>"/></td>
</tr>  
<tr>
 <td class="label"><label for="ccli">CCLI #:</label></td>
 <td class="input"><input type="text" name="ccli" size="80" value="<?php echo $ccli; ?>"/></td>
</tr>
<tr>
 <td class="label"><label for="hymnal">Hymnal #:</label></td>
 <td class="input">Optional for songs from a Hymnal:<br />
 <select name="hymnalname">
<?php 
 foreach ($SITE['hymnalList'] as $i => $hname) {
	 print "  <option value=\"$hname\"";
	 if($hymnalname == $hname) { print " selected=\"selected\""; }
	 print ">$hname</option>\n";
 }
?>
 </select><br/>
 <input type="text" name="hymnal" size="80" value="<?php echo $hymnal; ?>"/></td>
</tr>  
<tr>
 <td class="label"><label for="verseorder">VerseOrder:</label></td>
 <td class="input">Optional: use like <strong>v1 c1 v2 c1 e1</strong> to set order of verses with blank separators as shown<br/><input type="text" name="verseorder" size="80" value="<?php echo $verseorder; ?>"/></td>
</tr>  
<tr>
 <td class="label"><label for="verses">Verses:</label></td>
 <td class="input">Note: delimit verses with <strong>Verse 1: Chorus 1: Pre-Chorus 1: Bridge 1: Intro 1: Ending 1: Other 1:</strong> before verse paragraph, and end with blank line between verses. If marking omitted, <strong>v1 v2 v3</strong>... will be assumed.<br/><textarea name="verses" cols="80" rows="10"><?php echo $verses; ?></textarea></td>
</tr> 
<tr>
 <td class="label"><label for="notes">Comments:<br/>(Optional)</label></td>
 <td class="input"><textarea name="notes" cols="80" rows="4"><?php echo $notes; ?></textarea></td>
</tr> 
<tr>
 <td class="button" colspan="2"> <input type="submit" name="download" value="Download" /></td>
</tr>
</table> 
</form>
<p>&nbsp;</p>

<?php
function gen_xml_lyrics($text) {
/*
  <lyrics>
    <verse name="v1">
      <lines>Angels, from the realms of Glory,<br/>Wing your flight o’er all the earth;<br/>Ye who sang creation’s story,<br/>Now proclaim Messiah’s birth:</lines>
    </verse>
    <verse name="c1">
      <lines>Come and worship<br/>Christ, the new-born King.<br/>Come and worship<br/>Worship Christ, the new-born King.</lines>
    </verse>
    <verse name="v2">
      <lines>Shepherds, in the field abiding,<br/>Watching o’er your flocks by night,<br/>God with man is now residing,<br/>Yonder shines the infant-light:</lines>
    </verse>
    <verse name="v3">
      <lines>Sages, leave your contemplations,<br/>Brighter visions beam afar;<br/>Seek the great desire of nations,<br/>Ye have seen His natal star:</lines>
    </verse>
    <verse name="v4">
      <lines>Saints, before the altar bending,<br/>Watching long in hope and fear,<br/>Suddenly the Lord descending<br/>In His temple shall appear:</lines>
    </verse>
  </lyrics>
*/
  // file_put_contents('test-post-verses.txt',$text);
	
	$out = "  <lyrics>\n";
	$debug = '';
	$verses = explode("\r\n\r\n",$text."\r\n\r\n");
	
	foreach ($verses as $i => $verse) {
		$tverse = trim($verse);
		$debug .= "$i '$tverse'\n";
		if(preg_match('!^\s*(verse|chorus|ending|bridge|pre-chorus|intro|other)\s*(\d+)\s*(\:?)!Ui',$tverse,$m)) {
			$vnum = $m[2];
			$vtype = strtolower(substr($m[1],0,1));
			$tverse = str_replace($m[0],'',$tverse);
			if(isset($m[3])) { $tverse = substr($tverse,1,strlen($tverse)-1); }
			$debug .= "Matched $vtype $vnum\n";
		} else {
			$vnum = $i+1;
			$vtype = 'v';
			$debug .= "not matched $vtype $vnum\n";
		}
		$tverse = trim($tverse);
		$tverse = str_replace("\r\n","<br/>",$tverse);
		if(strlen($tverse) > 1) {
			$out .= "    <verse name=\"$vtype$vnum\">\n";
			$out .= "       <lines>".utf8fix($tverse)."</lines>\n";
			$out .= "    </verse>\n";	
		}
	}
	$out .= "  </lyrics>\n";
//	file_put_contents('debug.txt',$debug);
	return ($out);
}

function gen_txt_lyrics($text) {
# generate MediaShout Text lyrics + order info
  $verseOrder = '';
	$out = '';
	$verses = explode("\r\n\r\n",$text."\r\n\r\n");
	$debug = '';
	
	foreach ($verses as $i => $verse) {
		$tverse = trim($verse);
		$debug .= "$i '$tverse'\n";
		if(strlen($tverse) < 1) { break; }
		if(preg_match('!^\s*(verse|chorus|ending|bridge|pre-chorus|intro|other)\s*(\d+)\s*(\:?)!Ui',$tverse,$m)) {
			$vnum = $m[2];
			$vtype = ucfirst($m[1]);
			$debug .= "Matched $vtype $vnum\n";
		} else {
			$vnum = $i+1;
			$vtype = 'Verse';
			$tverse = "$vtype $vnum:\r\n".$tverse;
			$debug .= "not matched $vtype $vnum\n";
		}
		$tverse = trim($tverse)."\r\n\r\n";
		$verseOrder .= "$vtype $vnum,";
    $out .= $tverse;
	}
  if(strlen($verseOrder) > 1) {
		$verseOrder = substr($verseOrder,0,strlen($verseOrder)-1); // remove trailing comma
	}
//		file_put_contents('debug.txt',$debug);

	return(array($out,$verseOrder));
}

function fixup_txt_verseorder($text) {
	# Replace abbreviations with real words for txt file use
	$names = array(
	 'v' => 'Verse',
	 'c' => 'Chorus',
	 'b' => 'Bridge',
	 'e' => 'Ending',
	 'i' => 'Intro',
	 'p' => 'Pre-Chorus',
	 'o' => 'Other'
	);
	
	$orders = explode(' ',$text.' ');
	$out = '';
	
	foreach ($orders as $i => $abbrev) {
		if(strlen($abbrev) < 1) { break; }
		if(preg_match('!^([vceb])(\d+)$!i',$abbrev,$m)) {
			$t = $m[1];
			$n = $m[2];
			if(isset($names[$t])) {
				$out .= $names[$t].' '.$n.',';
			}
		}
	}
	if(strlen($out) > 0) {
		$out = substr($out,0,strlen($out)-1);
	}
	return ($out);
}

function utf8fix($txt) {
	$t = json_encode($txt,JSON_UNESCAPED_UNICODE);
	$t = substr($t,1,strlen($t)-2);
	$t = str_replace('<br\/>','<br/>',$t);
#	$t = str_replace('\u2019',"'",$t);
#	$t = str_replace('\u00a9','(c)',$t);
#	$t = str_replace('\u00ae','TM',$t);
#	$t = str_replace('\u201c','"',$t);
#	$t = str_replace('\u201d','"',$t);
	$t = str_replace('\u2029','',$t);
	$t = str_replace('\"','"',$t);
	$t = str_replace('&','&amp;',$t);

	return($t);
}

function offer_download($file,$content) {
	  header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Length: ". strlen($content).";");
    header("Content-Disposition: attachment; filename=\"$file\"");
    header("Content-Type: application/octet-stream; "); 
    header("Content-Transfer-Encoding: binary");
    print $content;
		if(strpos($file,'.xml') !== false) { // only save the .xml versions
		  if(file_exists('./lyricsxml/'.$file)) {
				$tdate = date('Ymd-his',filemtime('./lyricsxml/'.$file));
				rename('./lyricsxml/'.$file,'./lyricsxml/'.$file.'-'.$tdate.'.bak');
			}
		  file_put_contents('./lyricsxml/'.$file,$content);
		  file_put_contents('last-update.txt',time());
		}
}

function format_filename_display($fname) {
		$tfname = str_replace('./lyricsxml/','',$fname);
		$tfname = str_replace('.xml','',$tfname);
		preg_match('!^([^\(]+)\(([^\)]+)\)!',$tfname,$m);
		if(!isset($m[1])) {
			return ($fname);
		}
		$song = $m[1];
		$author = $m[2];
		if(strlen($author) > 22) {
			$author = substr($author,0,22).'...';
		}
		return("$song ($author)");
}
?>
<p><?php echo $Version; ?></p>
<?php if(!$includeMode) { ?>
</body>
</html>
<?php } // end ! includeMode ?>