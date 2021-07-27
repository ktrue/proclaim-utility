<?php
header("Content-type: text/plain,charset=UTF-8");
$f = unserialize (file_get_contents('2021-07-11_10-alljson.txt'));
var_export($f);