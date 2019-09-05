<?php
# ------------------------------------------------
# https://github.com/ktrue/proclaim-utility general webpage harness 
# bottom-parts.php
# License:  GNU GPLV3
# Author: Ken True
# ------------------------------------------------
include_once("settings-common.php");
# ------------------------------------------------
?>
<!-- begin bottom-parts.php -->
    </div>
  </div> <!-- end main content -->


  <footer id="myFooter">
    <div class="w3-container w3-theme-l2 w3-padding-32">
      <h4>Copyright &copy; <?php echo $NOWyear . ' '. $SITE['churchName']; ?><br/>Site contents are for Staff/Choir use only.</h4>
      <p><small><a class="w3-text-white" href="https://github.com/ktrue/proclaim-utility">proclaim-utility scripts GPL V3.0</a></small></p>
    </div>
  </footer>

<!-- END MAIN -->
</div>

<script>
// Get the Sidebar
var mySidebar = document.getElementById("mySidebar");

// Get the DIV with overlay effect
var overlayBg = document.getElementById("myOverlay");

// Toggle between showing and hiding the sidebar, and add overlay effect
function w3_open() {
    if (mySidebar.style.display === 'block') {
        mySidebar.style.display = 'none';
        overlayBg.style.display = "none";
    } else {
        mySidebar.style.display = 'block';
        overlayBg.style.display = "block";
    }
}

// Close the sidebar with the close button
function w3_close() {
    mySidebar.style.display = "none";
    overlayBg.style.display = "none";
}
</script>

</body>
</html>
