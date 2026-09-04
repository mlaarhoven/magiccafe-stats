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
        <li class="breadcrumb-item">Printer uitleg</li>
        
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
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Printer papier vervangen</h2>
                    </div>
                    <div class="card-body">
                        <div class="card-title"><h2>Hoe vervang je een rol papier?</h2></div>
                       <h3> Tips:</h3>
						<ul>
                        	<li>Bekijk deze <a href="https://www.youtube.com/watch?v=jXERmoLK1lI&ab_channel=DNPPhotoImagingEurope">VIDEO</a></li>
                            <li>Vervang altijd en het papier en de folie. Er blijft altijd een beetje extra papier over als de folie al op is.</li>
						</ul>

                        <h3>Fotokast openen</h3>
                        <ul>
                            <li>open de deur aan de linkerzijde van de fotokast</li>
                            <li>draai en schuif het koperen schuifje/grendeltje dat de printerplank tegen houd omlaag</li>
                            <li>trek de plank met de printer naar links, totdat de printer een beetje uit de kast steekt</li>
                            
                        </ul>

						<img src="img/printer_uitleg.png" width="75%" alt="printer uitleg">
						<img src="img/printer_storingen.png" width="75%" alt="printer storingen">
                        <h3>Printerklep openen</h3>
                        <ul>
                            <li>aan de linkerkant onderaan de printer voel je een inkeping
                            <li>steek je linkerhand in die inkeping, druk de vergrendeling omhoog en trek de bak naar links</li></li>
                        </ul>

                        <h3>Folie vervangen</h3>
                        <ul>    
                            <li>haal het zwarte bakje waar de folie in zit eruit, til het naar links</li>
                            <li>haal de folie uit het bakje</li>
                            <li>monteer de nieuwe folie, kleine asje rechts achteraan</li>
                        </ul>    

                        <h3>Papier vervangen</h3> 
                        <ul> 
                            <li>open nu de klep voor het papier, linksachter zit een blauwe knopdruk deze met je duim in en open de klep omhoog</li>
                            <li>verwijder het opvangbakje voor papier restjes (gooi de smalle strookjes papier die in het opvangbakje zitten weg)</li>
                            <li>haal de rol papier eruit</li>
                            <li>trek de zwarte plastic rol gelijders uit de rol</li>
                            <li>steek deze in de nieuwe rol, druk ze goed aan</li>
                            <li>leg de nieuwe rol in de printer. <strong>pijltje</strong> op de rol <strong>omlaag</strong></li>
                            <li>draai het papier onderlangs de printer in, tot je <strong>piep piep</strong> hoort</li>
                            <li>sluit het deksel. er worden nu 5 witte prints gemaakt</li>
                        </ul>

                        <h3>Storingen</h3> 
                        <ul> 
                            <li>als het goed is gaan er nu geen rode en oranje lampjes meer branden</li>
                            <li>soms zit het folie aan het papier geplakt, door te hoge luchtvochtigheid in combinatie met koud papier</li>
                            <li>haal voorzichtig de folie bak eruit, trek het vastzittende folie los</li>
                            <li>haal het papier eruit, droog het en warm het eventueel een beetje op bij eeen kachel</li>
                            <li>plaats papier en folie</li>
                            <li>kijk of de storing verholpen is</li>
                            <li>gooi geen rollen folie of papier weg om deze reden. Als de rollen eenmaal warm zijn, werken ze weer prima.</li>
                            
                        </ul>


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