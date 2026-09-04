  <?php 
  if ( !isset ( $_SESSION['client_id'] ) )
    {
    header('Location: https://stats.magicshoot.nl/index.php');
    exit;
    }
  ?>  
  <!-- CoreUI and necessary plugins-->
    <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <!--[if IE]><!-->
    <script src="vendors/@coreui/icons/js/svgxuse.min.js"></script>
    <!--<![endif]-->
    
  </body>
</html>   