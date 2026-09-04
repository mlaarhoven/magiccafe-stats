  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); ?>
   <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Homepage&nbsp;-&nbsp;<strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
    <style>
    #images{ display: block;  }
    .voorbeeld { display:  block; border: solid 2px #eee; padding:  10px; margin:  5px; float: left; width:  250px; height:  auto;}
    
    </style>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">
              <div class="row">
                 <!-- /.col-->
                <div class="col-sm-6 col-lg-3">

                


                  <div id="images">
                    <!--<img class="voorbeeld" src="/assets/img/beheer/fotokast_001_studio.png" width="250">-->
                    <img  src="img/fotokast_vrijstaand.jpg" width="350">
                   
                    <!--<img class="voorbeeld" src="/assets/img/beheer/kast.jpg" width="250">-->
             
                  </div>
                </div>
              
                <!-- end col -->
              </div>

          </div>
        </div>
      </main>
      <footer class="c-footer">
          <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> © <?php echo date('Y'); ?> MagicShoot.</div>
          <div class="ml-auto">Powered by&nbsp;<a href="https://www.magicshoot.nl/">MagicShoot</a></div>
        </footer>
      </div>
    </div>  
<?php include("partial_footer.php"); ?>