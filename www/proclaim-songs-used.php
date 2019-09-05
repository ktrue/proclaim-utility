<?php 
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness 
# proclaim-songs-used.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
$includeDataTables = true;
include_once("settings-common.php");
include("top-parts.php"); 
?>
<h2>Proclaim Songs Used</h2>
      
<p>Here is the complete list of the <?php echo $SITE['churchName']; ?> Worship Songs that have been included in Proclaim worship slides.<br>
Note: Search for a month by using YYYY-MM (e.g. 2019-08 for August, 2019)<br />

<span id="status" class="dataTables_processing"><img src="psongs/Spinner-1s-35px.gif" height="35" width="35" alt="loading..." style="alignment-baseline:middle;"/>
<strong>Fetching the data... please wait.  It can take a looong time.</strong></span></p>
      
      
<div id='table-container'>Loading the table of songs. It's a big table and can take a while.</div>

<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script src="psongs/js/bootstrap.min.js"></script>
<script src="psongs/js/jquery.csv.min.js"></script>
<script src="psongs/js/jquery.dataTables.min.js"></script>
<script src="psongs/js/dataTables.bootstrap.js"></script>
<script src='psongs/js/csv_to_html_table.js'></script>

<script>
  init_table({
    csv_path: './songs-usedcsv.php', 
    element: 'table-container', 
    allow_download: true,
    csv_options: {separator: ',', delimiter: '"'},
    datatables_options: {
  "paging": true,
  "processing": true,
  "pageLength": 25,
  "order": [[0,"desc"],[1,"asc"]],
  "lengthMenu": [ 10, 25, 50 ],
  "buttons": {
            "buttons": [
               'download',
                   { extend: 'csv', enabled: false }
            ]
         }
}
  });
  
  $('#table-container').on( 'draw.dt', function () {
   // alert( 'Table redrawn' );
   $('#status').hide();
} );

  
</script>

<?php
include("bottom-parts.php");
?>