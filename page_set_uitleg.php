  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
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
        <li class="breadcrumb-item">Uitleg en specificaties</li>
        
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
                      <h2 class="text-white text-uppercase">Uitleg sets en specificaties</h2>
                    </div>
                    <div class="card-body">
                        <div class="card-title"><h2>HELE KORTE UITLEG STROOKJE MAKEN</h2></div>
                        <ul>
                          <li>Maak een nieuw plaatje aan met afmeting van exact: 2480 pixels hoog x3688 pixels breed</li>
                          <li>Voeg een achtergrondkleur toe, van rand tot rand</li>
                          <li>Plaats logo en/of tekstje onderaan. Plaats dit links en rechts.</li>
                          <li>Sla het bestand op als PNG met de naam: <strong>strook.png</strong></li>
                          <li>Maak een nieuwe Set (<a href="https://stats.magicshoot.nl/page_set_overview.php">Set overzicht</a>) aan en sleep dit plaatje naar: 'stroken'.</li>
                          <li>Plan de set in met de set planner.</li>



                        </ul>
                        <h2>VOORBEELD</h2><p>Dit is hoe een strook.png eruit hoort te zien. Volledige achtergrondkleur, logo en text onderaan geplaatst.</p>
                      <img src="/strook_templates/strook.png" alt="Strook voorbeeld" class="img-fluid" width="400"><br>
<br>

 <h2>VOORBEELD met testfotos en snijrand</h2>
 <p>Plaats geen tekst of logo in het paarse gedeelte, dit kan eraf gesneden worden! <br>De plek van de foto's is vast. Dus geen logo bovenaan zetten!<br> Let op: dit is alleen een voorbeeldje, de echte strook.png moet geen randen en testfoto's bevatten (die hier boven staat is goed)!</p>
                      <img src="/strook_templates/strook_met_testfotos_en_snijrand.png" alt="Strook voorbeeld" class="img-fluid" width="400"><br>
<br>

<p>Het is beter/handiger/mooier om de Photoshop template te gebruiken, die kan je downloaden bij het <a href="https://stats.magicshoot.nl/page_set_overview.php">Set overzicht</a>. <br>
Daarin staan guides, snijlijnen en andere hulpmiddelen om het strookje correct op te maken.
</p>
<br><br>

                        <div class="card-title"><h2>LANGE UITLEG</h2></div>
                        <h3>Wat is een Set?</h3>
                        <p>Een Set is een verzameling van alle plaatjes die nodig zijn om een fotostrip te maken.<br>
                           <ul>
                              <li>Maak  een nieuwe set aan: Set overzicht -> Nieuwe Set aanmaken</li>
                              <li>Geef de nieuwe set een naam en klik op Opslaan</li>
                              <li>De nieuwe set bevat de plaatjes die je <i>minimaal</i> nodig hebt:</p>
                         
                            <strong>STROOK</strong>
                            <ul>
                          <li>Maak een nieuw plaatje aan met afmeting van exact: 2480 pixels hoog x3688 pixels breed</li>
                              <li><i>strook.png </i>(afmeting: 2480x3688 pixels) dat is het achtergrond plaatje. De foto's worden hier bovenop gezet.</li>
                              <li><i>strook_foreground.png</i> (standaard is dit een leeg, transparant plaatje)</li>
                            </ul>
                            <strong>PLAATJES</strong>
                            <ul>
                              <li>Achtergrondplaatjes: <i>1600x1200.jpg</i> (volledig wit plaatje)</li>
                            </ul>
                            
                          <br>

                         <h3>Hoe verander ik de achtergrond?</h3>  
                         <p>   
                            <ul>
                              <li>Ga naar <strong>Set Overzicht</strong> en klik op <strong>Download strook template</strong></li>
                              <li>Zet alle lagen <i>uit</i>, behalve de ondergrond</li>
                              <li>Exporteer het bestand als PNG en sla het op met de naam: <strong>strook.png</strong> (letterlijk deze naam!)</li>
                              <li>Ga naar: <strong>Set overzicht</strong> en klik op <strong>Edit Set</strong> en sleep strook.png op het witte vakje: Stroken</li>
                              <li>Laat <i>strook_foreground.png</i> gewoon staan, dat is een transparant plaatje</li>
                            </ul>
                        </p>
                       

                         <h3>Hoe plan ik een tijdelijke Set in?</h3>  
                         <p> 
                          <ul>
                            <li>Ga naar <strong>Set planner</strong></li>
                            <li>Zoek de juiste dag op in de kalender en klik op de tijd waarop je de nieuwe Set wilt starten</li>
                            <li>Kies in de Dropdown evt de juiste starttijd en kies een eind datum en eindtijd</li>
                            <li>Kies in de setdropdown de juiste set en klik op Opslaan</li>
                          </ul>  
                         </p>

                          <h3>Hoe gebruik ik een nieuw gemaakte set als basisset?</h3>  
                         <p> 
                          <ul>
                            <li>Ga naar <strong>Set overzicht</strong></li>
                            <li>Klik op de rode knop: <strong>Gebruik deze set als basisset</strong></li>
                           
                          </ul>  
                         </p>


                        <h3>Hoe verwijder ik een Set?</h3>
                        <p>Je kunt een set alleen verwijderen als hij nooit gebruik is. Anders kan je hem wel verbergen.<br>
                         <ul>
                            <li>Ga naar <strong>Set overzicht</strong></li>
                            <li>Klik op de rode knop: <strong>Verwijder set</strong></li>
                        </ul>  
                        </p>
                        <br>
                        <h3>Voor gevorderden</h3>
                        <p> Strook en strook_foreground <i>versus</i> achtergrond en voorgrond plaatjes plaatjes</p>
                        
                        <h4>Optie 1. Een voorgrond plaatje over alles heen</h4>
                        <p>strook.png bevat de achtergrond van de strook. Er staan altijd twee stroken naast elkaar op dit plaatje. <br>
                        De printer snijdt het papier door miden waardoor er twee stroken ontstaan.<br>
                        Je kunt over het geheel van de achtergrond en de drie fotootjes een voorgrond leggen. Dit is de <strong>strook_foreground.png</strong><br>
                        Dat samenvoegen gebeurt achteraf, je klant ziet dat dus niet tijdens het fotos maken.<br>
                        Een paar kleine beeld elementjes, zeg hartjes die net op de rand van de foto en de achtergrond staan zou hiermee goed kunnen,<br>
                        mits deze niet teveel over de foto staan, waardoor mensen niet meer zichtbaar zijn.</p>

                        <h4>Optie 2. Een voorgrond per fotootje</h4>
                        <p>Als je wel wilt dat je klant de voorgrond ziet tijdens het foto's maken, dan kan je beter de drie losse foregrounds gebruiken.<br>
                        Per foto kan er een background en een foreground plaatjes worden geupload.<br>
                        De background is een wit plaatje. <br>
                        De foreground kan je gebruiken om een wat groter elementje op de foto te plaatsen. <br>
                        Je klant ziet dit live in beeld en kan zijn of haar hoofd even een beetje verplaatsen zodat de voorgrond niet voor z'n hoofd zit.<br>
                        Als je foregrounds gebruikt moet je ook drie maal een (wit) achtergrondplaatje uploaden.<br>
                        Dus dan krijg je: img_1.png, img_2.png en img_3.png voor de voorgrondplaatjes en dan img_1.jpg, img_2.jpg en img_3.jpg als achtergrondplaatjes.</p>

                      
                    </div>
                 
                    <div class="card-body">
                        <div class="card-title"><h2>SPECIFICATIES</h2></div>
                        <h3>STROOK</h3>
                        <table class="table table-responsive-sm table-bordered table-striped ">
                                <tr>
                                    <th>Functie</td>
                                    <th>Filename</td>
                                    <th>Type</td>
                                    <th>Breedte x hoogte</td>
                                    <th>Verplicht</td>
                                </tr>
                                <tr>
                                    <td>Strook achtergrond</td>
                                    <td>strook.png</td>
                                    <td>PNG</td>
                                    <td>2480 x 3688px</td>
                                    <td>Ja</td>
                                </tr>
                                <tr>
                                    <td>Strook voorgrond</td>
                                    <td>strook_foreground.png</td>
                                    <td>PNG</td>
                                    <td>2480 x 3688px</td>
                                    <td>Nee</td>
                                </tr>
                            </table>
                            <br>

                        <h3>PLAATJES</h3>
                        <table class="table table-responsive-sm table-bordered table-striped ">
                                <tr>
                                    <th>Functie</td>
                                    <th>Filename</td>
                                    <th>Type</td>
                                    <th>Breedte x hoogte</td>
                                    <th>Verplicht</td>
                                    <th>Inhoud</td>
                                </tr>
                                <tr>
                                    <td>1e achtergrond plaatje</td>
                                    <td>img_1.jpg</td>
                                    <td>JPG</td>
                                    <td>1600 x 1200 px</td>
                                    <td>Ja</td>
                                    <td>gevuld met wit</td>
                                </tr>
                                <tr>
                                    <td>2e achtergrond plaatje</td>
                                    <td>img_2.jpg</td>
                                    <td>JPG</td>
                                    <td>1600 x 1200 px</td>
                                    <td>Nee, tenzij je een foreground gebruikt</td>
                                    <td>gevuld met wit</td>
                                </tr>
                                <tr>
                                    <td>3e achtergrond plaatje</td>
                                    <td>img_3.jpg</td>
                                    <td>JPG</td>
                                    <td>1600 x 1200 px</td>
                                    <td>Nee, tenzij je een foreground gebruikt</td>
                                    <td>gevuld met wit</td>
                                </tr>
                                <tr>
                                    <td>1e voorgrond plaatje</td>
                                    <td>img_1.png</td>
                                    <td>PNG</td>
                                    <td>1600 x 1200 px</td>
                                    <td>Nee</td>
                                    <td>transparant met enkele elementen</td>
                                </tr>
                                <tr>
                                    <td>2e voorgrond plaatje</td>
                                    <td>img_2.png</td>
                                    <td>PNG</td>
                                    <td>1600 x 1200 px</td>
                                    <td>Nee</td>
                                    <td>transparant met enkele elementen</td>
                                </tr>
                                <tr>
                                    <td>3e voorgrond plaatje</td>
                                    <td>img_3.png</td>
                                    <td>PNG</td>
                                    <td>1600 x 1200 px</td>
                                    <td>Nee</td>
                                    <td>transparant met enkele elementen</td>
                                </tr>
                            
                            </table>
                            <br>
                      
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