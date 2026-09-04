  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' || $_SESSION['client_role'] == 'owner' ) ) {
	header('Location: https://stats.magicshoot.nl/admin/index.php');
	exit;
  }
  ?>

<style>
		.image_icon{
			width: 64px;
			margin-top: 5px;
		}
		.image_icon_small{
			width: 24px;
			margin-top: 10px;
			margin-right: 20px;
		}
		.last{
			margin-right:0 !important;	
		}
		.image_manager {
			display: block;
			float: none;
			clear:both;
			overflow:hidden;
		}	
		.two_image_container{
			display: block;
			border: 1px solid #999;
			border-radius: 4px;
			padding: 5px;
			float: left;
			background-color: #eee;
			margin-left: 10px;
			margin-bottom: 20px;
		}
		.img_filename {
			font-size: 9px;
			margin-top: 5px;
			margin-bottom: 5px;
			display: block;
			width: 180px;
			overflow: none;
		}
		.missing {
			color: #ff0000;
			font-weight: bold;
			font-size: 12px;
			margin-top:60px;
			line-height: 26px;
		}
		.drop_text_fore {
			
			color: #dd44ee;
		}
		.drop_text_back {
			
			color: #3344ff;
		}
		.image_container{
			display:block;
			border:1px solid #aaa;
			border-radius: 4px;
			padding: 5px;
			width: 200px;
			height: 230px;
			background-color: #ddd;
			
			text-align: center;
			margin: 5px;
			margin-bottom: 10px;
		} 	


    .image_container_high{
			display:block;
			border:1px solid #aaa;
			border-radius: 4px;
			padding: 5px;
			width: 200px;
			height: 280px;
			background-color: #ddd;
			
			text-align: center;
			margin: 5px;
			margin-bottom: 10px;
		} 	

		.image_container_imagemanager_high{
			display:block;
			padding: 10px;
      padding-top:75px;
			width: 124px;
			height: 285px;
			text-align: center;
			margin-bottom: 10px;
			font-size:10px;
  	} 	

    .image_container_imagemanager{
			display:block;
			padding: 10px;
      padding-top:55px;    
			width: 124px;
			height: 233px;
			text-align: center;
			margin-bottom: 10px;
			font-size:10px;

		} 	
		.image_icon{
			width:32px;
		}
		.imagemanager_title
		{
			display: block;
			margin-top: 10px;
			margin-bottom: 10px;
			font-weight: bold;
		}
	</style>
  

    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Support</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">


       
         
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-9 col-md-9 col-lg-9">
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Support</h2>
                    </div>
                    <div class="card-body">
                        <div class="card-title"><h2>Support</h2></div>
                        <p>We leveren remote support en indien nodig op afspraak op locatie.</p>
                        <p>We kunnen met AnyDesk met u meekijken op de photobooth en dingen aanpassen, herstarten of aanwijzingen geven.</p>
                        <p>Mochten we er remote niet uitkomen dan plannen we een locatie bezoek in.</p>
                        <p>We willen u vragen de stekker van de kast niet te verwijderen, tenzij er echt iets fysieks mis is, dat gevaar op kan leveren,
                        omdat we anders geen remote support kunnen leveren.</p> 
						
						<p>U kunt de kast <strong>uit en aan</strong> zetten via <a href="https://stats.magicshoot.nl/page_shelly_control.php">deze pagina</a>.</p>

                        <table class="table table-responsive-sm table-bordered table-striped ">
                            <tr><td><img src="/img/sitan_van_sluis klein.jpg" width="100"></td>
                            <td><h4>Sitan van Sluis</h4>
                                <p>Stuur een WhatsAppje of bel (tijdens werktijden) naar <strong>+31 6 21220846</strong> of sitan@magicshoot.nl</p>
                            </td>    
                            </tr>    
                            <tr>
                                <td><img src="/img/martijn_laarhoven.jpg" width="100"></td> 
                                <td><h4>Martijn Laarhoven</h4>
                                    <p>Stuur een WhatsAppje naar +31 6 50904837 of support@magicshoot.nl</p>
                                </td>
                            </tr>
							<tr>
                                <td><img src="/img/imre_van_sluis.jpg" width="100"></td> 
                                <td><h4>Imre van Sluis - nachtdienst</h4>
                                    <p>Stuur een WhatsAppje naar +31 6 40448240</p>
                                </td>
                            </tr>
                        </table>                            
						<h2>Printerstoringen</h2>
						<p>Bij klachten van gasten over een printerstoring (paperjam of ribbon error) verzoeken we u om de storing zo snel als mogelijk te verhelpen.</p>
						<p>De strook wordt na een storing opnieuw afgedrukt en kan dan worden meegegeven (ipv het aankoopbedrag terug te geven.)</p>
						<p>De printerstatus kan eenvoudig op <a href="https://stats.magicshoot.nl/page_printer_overview.php">deze pagina</a> worden bekeken.</p>
                        </div>
                 
                </div>
              
              </div>


            
         
        </div>          
        </div>
      </main>
      <footer class="c-footer">
        <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> © <?php echo date('Y'); ?> MagicShoot.</div>
        <div class="ml-auto">Powered by&nbsp;<a href="https://www.MagicShoot.nl/">MagicShoot</a></div>
        </footer>
      </div>
    </div>  
<?php include("partial_footer.php"); ?>