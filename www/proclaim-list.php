<?php 
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness 
# proclaim-list.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
$includeDataTables = true;
include_once("settings-common.php");
include("top-parts.php"); 
?>
<h2>Proclaim Songs Available</h2>
      
<p>Here is the complete list of the <?php echo $SITE['churchName']; ?> Worship Songs that have been generated and should be available now in Proclaim (after the song .xml file is imported there).<br>
The songs have been formatted for projection, and are arranged in the way we usually sing them.<br>
<br/>When you use the <a href="make-openlp.php">Make Lyrics</a> page, those entries will be automatically added to this list when you press Download on that page.  Note that you will need to use Proclaim, Media, Import songs... to add the new song to the Proclaim library itself.<br/>

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
    csv_path: './songlist.php', 
    element: 'table-container', 
    allow_download: true,
    csv_options: {separator: ',', delimiter: '"'},
    datatables_options: {
  "paging": true,
  "processing": true,
  "pageLength": 25,
  "order": [[0,"asc"],[1,"asc"]],
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