<?php
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness 
# settings-common.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
#
# Read the JSON presentation files, extract the Song entries, match to our proclaim-songlist.csv and produce a report
#
include_once("settings-common.php");

$songlistFields = array("Title","Author","Hymnal","CCLI","VerseOrder","Copyright");
$data = csv_to_array($SITE['songlistCSVfile'],',',false,$songlistFields);
header ('Content-type: text/plain;charset=UTF-8');

$CSVsongs = array();

foreach ($data as $n => $item) {
	$CSVsongs[$item['Title']] = join('|',
	  array($item['Author'],$item['Hymnal'],$item['CCLI'],$item['VerseOrder'],$item['Copyright']));
}
# now current CSV songs are loaded into a convienent array for lookup:
/*
array (
  'A Song Must Rise' => 'Paul B. Svenson|||(c)1995 Dad\'s Songbook Music.  (Admin. by Dad\'s Songbook Music LLC)  All Rights Reserved. Reprinted under CCLI License # 2541334. |v1 c1 v2 c1 v3 e1',
  'Amazing Grace' => 'John Newton||||',
  'Amazing Grace-My Chains are Gone' => 'Chris Tomlin,John Newton,Louie Giglio||4768151|(c) Copyright 2006 Worship Together.com songs, sixsteps Music, Vamos Publishing. All Rights Reserved, Reprinted under CCLI License # 2541334|v1 v2 c1 v3 c1 e1',
  'Amen, siyakudmisa' => 'S. C. Molefe|NCH#760||Trad. South African song, Iona Community, Words and Music - (c)1990 Iona Community, WGRG, Reprinted under OneLicense.net A-726183|',

*/

//var_export($CSVsongs);

$JSONfiles = glob($SITE['$JSONfilesDir'].'*.json');
$output = '';
  	 $output .= rfccsv(array(
				"slidesDate",
				"slidesName",
				"Song",
				"proclaim-hymn",
		//		"our-hymn",
				"proclaim-CCLI",
				"OneLicense",
		//		"our-CCLI",
				"proclaim-author",
		//		"our-author",
				"proclaim-copyright",
		//		"our-copyright",
		    )
			)."\n";
			
foreach ($JSONfiles as $n => $JSONfileName) {
	# print ".. JSON file '$JSONfileName' found.\n";

  $JSON = json_decode(file_get_contents($JSONfileName),true);
	$slidesName = $JSON['title'];
	$slidesDate = $JSON['dateGiven'];
	
	foreach ($JSON['items'] as $k => $item) {
		$title = $item['title'];
		$kind  = $item['kind'];
		if($item['content']['AutoAdvance'] == 'true') { // skip the pre/post loops+warmup slides
			//print "$title\t$kind\t(auto-advance)\n";
			continue;
		}
		if($kind == "SongLyrics") {
		 $hymn =      isset($item['content']['_textfield:Hymn Number'])?$item['content']['_textfield:Hymn Number']:'';
		 $ccli =      isset($item['content']['_textfield:Song Number'])?$item['content']['_textfield:Song Number']:'';
	   $copyright = isset($item['content']['_textfield:Copyright'])?$item['content']['_textfield:Copyright']:'';
	   $author = isset($item['content']['_textfield:Credits'])?$item['content']['_textfield:Credits']:'';
	   $useVerseOrder = isset($item['content']['CustomOrderSlides'])?$item['content']['CustomOrderSlides']:'';
	   $verseOrder = isset($item['content']['CustomOrderSequence'])?$item['content']['CustomOrderSequence']:'';
		
		 if(isset($CSVsongs[$title])) {
			 list($Author,$Hymnal,$CCLI,$VerseOrder,$Copyright) = explode('|',$CSVsongs[$title].'|||||');
		 } else {
			 list($Author,$Hymnal,$CCLI,$VerseOrder,$Copyright) = explode('|','|||||||');
		 }
		 
		 if(preg_match('!'.$SITE['oneLicenceNumber'].'!i',$copyright) or 
		               preg_match('!'.$SITE['oneLicenceNumber'].'!i',$Copyright)) {
				$oneLicense = 'Yes';
			} else {
				$oneLicense = '';
			}
									 
		 if(!empty($Hymnal) and empty($hymn)) {
			 $hymn = $Hymnal.' missing';
		 }
		 
		 if(!empty($CCLI) and empty($ccli)) {
			 $ccli = $CCLI . ' missing';
		 }
		  
  	 $output .= rfccsv(array(
				$slidesDate,
				$slidesName,
				$title,
				$hymn,
			//	$Hymnal,
				$ccli,
				$oneLicense,
			//	$CCLI,
				$author,
			//	$Author,
				$copyright,
			//	$Copyright
			  )
			)."\n";  
	  } // end SongLyrics

		
		

	}


}
  offer_download('songs-used.csv',$output,true);

# ----------------------------------------------------------

function offer_download($file,$content,$saveFile=false) {
	global $songListFile;
	  header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Length: ". strlen($content).";");
    header("Content-Disposition: attachment; filename=\"$file\"");
    header("Content-Type: application/octet-stream; "); 
    header("Content-Transfer-Encoding: binary");
    print $content;
		if($saveFile) {
			file_put_contents($file,$content);
		}
}

# ----------------------------------------------------------

function rfccsv($arr){
     foreach($arr as &$a){
         $a=strval($a);
         if(true or strcspn($a,",\"\r\n")<strlen($a))$a='"'.strtr($a,array('"'=>'""')).'"';
     }
     return implode(',',$arr);
 }

# ---------------------------------------------------------------------
function csv_to_array(
       $filename = '', $delimiter = ',',
       $boolean_include_title_row = false,
			 $field_names = array()){

 try {

		 if (!file_exists($filename) || !is_readable($filename)) {
				 return false;
		 }

		 if (is_array($field_names) && !empty($field_names)) {
				 $header = $field_names;
		 } elseif (is_string($field_names) && (strlen($field_names) > 0)) {
				 $header = explode(",", $field_names);
		 } else {
				 $header = null;
		 }

		 $csv = array_map('str_getcsv', file($filename));

		 $data = array();

		 foreach ($csv as $key => $row) {
				 $data[] = array_combine($header, $row);
		 }

		 if (!$boolean_include_title_row) {
				 unset($data[0]);
				 $data = array_values($data);
		 }
		 
		 return $data;

 } catch (Exception $e) {
		 return false;
 }

}