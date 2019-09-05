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
$SITE['CCLILicenseNumber'] = '1111111'; // church's CCLI number for SongSelect
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
