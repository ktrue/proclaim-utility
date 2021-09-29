# proclaim-utility
Utility PHP/AJAX support scripts for [Faithlife Proclaim](https://proclaim.faithlife.com/) projection software

## Background

Our church (Campbell United Church of Christ) began using [Faithlife Proclaim](https://proclaim.faithlife.com/) projection software to produce slides for our weekly worship service in July, 2019.  Previously, we had a fairly complicated and labor intensive process using MicroSoft Word™ documents to create a 'Worship Roadmap' and from that, a MicroSoft Powerpoint™ slide set for projection of worship service order with song/hymn lyrics.  Faithlife Proclaim has greatly simplified the process of creating projection-ready slides, but it didn't have the ability to create a written order of worship (roadmap) for the service, nor the ability to import our existing song library (in Word documents) easily into Proclaim for projection.

As a result, several utility PHP scripts were generated to allow:
* Creation/management of songs into [OpenLP](https://openlp.org/)/[OpenLyrics](http://www.openlyrics.org/contents.html) XML format for easy import into Proclaim
* Query scripts to show songs in the library and show song usage in Proclaim presentations
* Creation of worship roadmap HTML page based on a Proclaim slide set (the local Backup slide set .PRS file uploaded to the website)

Our worship service preparation process is now:
* Pastor selects liturgy elements to use (generally in Word format)
* Music Director selects songs/hymns appropriate for the liturgy message
* Office Administrator copy/pastes content from the Word format liturgy into slides in Proclaim, and inserts songs based on Music Director choices.
* A/V crew adds/adjusts audio/visual instructions
* Proclaim slides are backed up to local copy, and uploaded to website where the worship roadmap is generated.
* The roadmap may be viewed directly in any browser (it is HTML5) or optionally printed via the browser for hard-copy output.

## 2021 Additions to the script set

The __roadmap.php__ script now (by default) displays the information for use by the worship participants (Pastor, Lay Reader, Song Leader, etc.) without the clutter of the A/V cues.  Using __roadmap.php?avtech__ now produces a roadmap for use by the A/V Tech with the audio and video cues highlighted and the other participant text included so the techs can follow along the service order.  A __roadmap.php?summary__ is now available to show just the slide names/order.

The __roadmap.php?list__ now includes links for the basic, A/V tech and summary roadmaps available.

In 2021, we began using Audio and Video files embedded in the slides, so the __roadmap.php__ script was modified to collect all the JSON files from a .prs backup and save to a corresponding ./proclaimarchive/YYYY-MM-DD_HH-alljson.txt file so the script could extract details about each Audio or Video file as needed.
We also began to explore using Lighting Signals (MIDI) commands to control our OBS software scene selections.  Unfortunately, Proclaim doesn't store details about Lighting Signals in the __BackupPresentation.json or other JSON files, so to correlate/display 'what' the signal does, a __signals-list.txt__ can be used to associate the SceneSignal, SceneId with some text to display.

Given the larger .prs files when embedding Audio or Video files in the presentation, the make-roadmap process would sometimes fail due to either max POST size or max time.  To fix this, we upload the large presentation to ./temp-proclaim/backup.prs using FTP or SCP copy, then run __process-roadmap.php__ script to add it to the set for __roadmap.php__ to display.

## Caveat

The author of these scripts is not affiliated with Faithlife Proclaim software developers in any way other than as a user of the Proclaim PC/Mac software.

The roadmap scripts use the local Backup Presentation file (.PRS) file for data, and specifically, the contents of the __BackupPresentation.json__ file inside that ZIPped archive is used.
Note that the format of that file is not documented and is likely subject to change as updates to Proclaim software are released.  The __BackupPresentation.json__ file is a manifest containing the order of slides with detailed content specifications for each slide in the presentation.

This script set was based on decoding by inspection the JSON data for each slide contained in the __BackupPresentation.json__ file.

## Contents

This script set is released under the GNU GPL-V3 license, and the [DataTables](https://datatables.net/) scripts (in __./www/psongs/__ directory) are under the MIT license.  You are free to use/modify the scripts as allowed by the license(s).

The scripts are all located in the __./www/__ directory in this distribution which contains a functional website and may be placed in any convenient directory in your website (preserving the relative directory structure/contents)

__LICENSE__ - text of GNU GPL-V3 license for these scripts

__README.md__ - this document

Contents of the __./www/__ directory:

__./css/__ - CSS files for the included website structure

__./psongs/__ - support files for __DataTables__ queries

__./lyricsxml/__ - storage for the OpenLyrics .XML
files

__./proclaimarchive/__ - storage for the Proclaim Backup Presentation (.prs and .json) files submitted for roadmap generation.  The files use `YYYY-MM-DD_HH.{prs|json}` as the filenames with _YYYY-MM-DD\_HH_ the date and hour of the service.  A _YYYY-MM-DD\_HH-alljson.txt_ file will contain the contents of all the other .json files in the .prs. The __roadmap.php__ script only uses the _.json_ file and the .txt file for creating the roadmap.

__./temp-proclaim/__ - storage for a uploaded __backup.prs__ file (uploaded by FTP or SCP).  It would then be processed by running __process-roadmap.php__.

* sample website structure scripts
  * __settings-common.php__ - overall settings for the scripts
  * __top-parts.php__ - common top of HTML page
  * __bottom-parts.php__ - common bottom/footer of HTML page
  * __index.php__ - homepage
* Song lyrics support scripts
  *  __make-openlp.php__ - driver page for __make-openlp-inc.php__
  *  __make-openlp-inc.php__ - handle generation/editing of OpenLP/OpenLyrics format XML lyrics files
  * __songlist.php__ - list/query current .XML songs in the library (uses the DataTables capability)
  * __proclaim-songs-used.php__  - list/query songs included in the uploaded Proclaim .PRS slides in roadmaps
* roadmap generation and display scripts
  * __make-roadmap.php__ - script to upload Proclaim .prs file and display (via __roadmap.php__) the roadmap.
  * __process-roadmap.php__ - script to read ./temp-proclaim/backup.prs file uploaded by FTP/SCP and create the needed .json and .txt files for __roadmap.php__ to use.
  * __roadmap.php__ - main script to parse selected BackupPresentation.json file and generate the HTML5 roadmap display
* files automatically maintained:
  * __last-update.txt__ - modified with timestamp of last upload done through __make-roadmap.php__ script.
  * __proclaim-songlist.csv__ - automatically updated by __songs-usedcsv.php__ when a new lyric is downloaded by the __make-openlp.php__ page.
  * __./lyricsxml/__ - contains the updated OpenLP/OpenLyrics .XML file for the song Downloaded by __make-openlp.php__.  If a song is updated on the server, the prior copy is renamed with a timestamp and a .bak extension before storing the updated song.
  * __./proclaimarchive/filelist.txt__ - automatically updated when a new Proclaim .prs file is uploaded by the __make-roadmap.php__ script.

## Installation

Copy the entire __www/__ directory to a website directory of your choice.  Be sure to preserve the relative directory structure.

Edit the contents of __settings-common.php__ for your church's data.

```
<?php
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness
# settings-common.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
# Please change the below to match your local church's information and
# website customization.
# This file is included by all scripts to provide this info uniformly throughout
# the website.
# ------------------------------------------------
#
global $SITE;

$SITE['churchName'] = 'Your Church';
$SITE['churchKeywords'] = 'Your Church, United Church of Christ';
$SITE['oneLicenceNumber'] = '999999'; // church's OneLicense.net number
$SITE['CCLILicenseNumber'] = '11111111'; // church's CCLI number for SongSelect
$SITE['timezone'] = 'America/Los_Angeles';
# ------------------------------------------------
# settings for relative location of Proclaim backups (from make-roadmap)
$SITE['$JSONfilesDir'] = './proclaimarchive/';
$SITE['songlistCSVfile'] = './proclaim-songlist.csv';
# ------------------------------------------------
# settings for make-openlp
$SITE['lyricsXMLdir'] = './lyricsxml/';
$SITE['hymnalList'] = array('New Century Hymnal','The Faith We Sing','Sing! Prayer and Praise','United Methodist Hymnal');
$SITE['hymnalListAbbrev'] = array('NCH','TFWS','SPAP','UMH');
```

You can delete the contents of the ./proclaimarchive/ directory now.

Use __make-roadmap.php__ to upload a Proclaim Backup presentation to the website and create a roadmap display.  This will automatically create the updated support files for you.

## Worship Roadmap details

The worship roadmap is basically the script for a single worship service that contains the liturgy, the song lyrics and stage/AV cues so the worship team can conduct the service.

The Proclaim slides have content that is projected (in the Content/Lyrics/etc. tabs), and content in the Notes tabs that appear only in the roadmap.
Our convention using the Notes tab on a slide is:
* text at the top appears in the roadmap before the slide-name.  It is used for AV cues (centered blue text) or participant cues (italic red text).
* text following a `****` line (4 asterisks) is for liturgy - readings by the worship team.  That text appears inline in the roadmap.

The text formatting of __bold__, _italic_, ___bold-italic___, and small-caps is supported by the roadmap script for all content.

The roadmap is a pure HTML5 page that may be easily printed from the browser, or simply viewed online.

## Display of Signals in avtech roadmaps

Unfortunately, Proclaim does not store the Lighting Signals in a format that can be accessed by a program.  The _signals-list.txt_ file provides a lookup from the SceneId to human-readible text to display.  Our sample one is included -- you'll need to construct your own version (as SceneIds are unique per Faithlife group).  If you embed your Lighting Signals in slides, then upload and view-source of the _roadmap.php?avtech_, near the bottom of the page in HTML comments will appear the listing of all Lighting Signals encountered in the slides.  Using that, and the current _signals-list.txt_ as a model, you can construct your custom _signals-list.txt_ for your use.

Example:  the view-source shows:
```
'1d2cfdf9-6765-4b25-b898-bffeb8ba727e' =>
array (
  0 => '41: 13 Pastor Mic UNMUTE',
  1 => '49: Combination signals',
  2 => '50: Communion',
),
```
which indicates that SceneId '1d2cfdf9-6765-4b25-b898-bffeb8ba727e' was seen on slides 41, 49, and 50 with the corresponding slide titles shown.

That unmute for the Pastor Mic should appear in the _signals-list.txt file array as
```
'1d2cfdf9-6765-4b25-b898-bffeb8ba727e' => 'signal: sound mixer <strong>UNMUTE 13 Pastor Mic</strong>',
```

Fortunately, you only have to do this correspondence table once as Proclaim stores it (likely in the cloud) so all the Proclaim users in your presentation group have the same set of Lighting signals available.

## Additional documentation

I've included a PDF (and Word™) document that was used for training at our church.  Feel free to modify/expand it for your church's use.

* Proclaim Utility Guide
  * [Word format](https://github.com/ktrue/proclaim-utility/raw/master/www/docs/Proclaim-Utility-Guide.docx)
  * [PDF format](https://github.com/ktrue/proclaim-utility/raw/master/www/docs/Proclaim-Utility-Guide.pdf)
* Sample Roadmap (original format)
  * [HTML format](https://github.com/ktrue/proclaim-utility/raw/master/www/docs/Roadmap-sample.html)
  * [browser printed PDF](https://github.com/ktrue/proclaim-utility/raw/master/www/docs/Roadmap-sample.pdf)
* Sample Roadmaps (new format)
  * Participant roadmap [HTML format](https://github.com/ktrue/proclaim-utility/raw/master/www/docs/Roadmap-sample2.html) or [browser printed PDF](https://github.com/ktrue/proclaim-utility/raw/master/www/docs/Roadmap-sample2.pdf)
  * AV Tech roadmap [HTML format](https://github.com/ktrue/proclaim-utility/raw/master/www/docs/Roadmap-sample2-avtech.html) or [browser printed PDF](https://github.com/ktrue/proclaim-utility/raw/master/www/docs/Roadmap-sample2-avtech.pdf)
  * Summary roadmap [HTML format](https://github.com/ktrue/proclaim-utility/raw/master/www/docs/Roadmap-sample2-summary.html) or [browser printed PDF](https://github.com/ktrue/proclaim-utility/raw/master/www/docs/Roadmap-sample2-summary.pdf)

 ## Reporting problems/issues/suggestions

Please use the GitHub Issues area to report issues or make suggestions for additions to the script set.
