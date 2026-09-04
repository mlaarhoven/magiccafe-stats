<?php 
 include 'inc/inlog_functions.php';
 if ( !isset ( $_SESSION['client_id'] ) )
    {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
    }

    $shelly_switch_only =  get_shelly_switch_only ( $_SESSION['event_id']);  
  ?>

      <div class="c-sidebar c-sidebar-dark c-sidebar-fixed c-sidebar-lg-show" id="sidebar">
      <div class="c-sidebar-brand d-lg-down-none">
        <img src="assets/brand/magicshoot_logo_zonder_url.svg#full" class="c-sidebar-brand-full" width="167" height="48" alt="MagicShoot Logo">
        <img src="assets/brand/magicshoot_logo_zonder_url.svg#signet" class="c-sidebar-brand-minimized" width="118" height="46" alt="MagicShoot Logo">
        
      </div>
      <ul class="c-sidebar-nav">
        <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_dashboard.php">
            <svg class="c-sidebar-nav-icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-home"></use>
            </svg> Home</a></li>
            <li class="c-sidebar-nav-title">Fotokast:</li>
            <li class="c-sidebar-nav-item"><span class="c-sidebar-nav-link">
            <svg class="c-sidebar-nav-icon">
                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-arrow-thick-right"></use>
              </svg> <?php 
                echo get_event_name ( $_SESSION['event_id']);
               ?></span></li>
           <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_dashboard.php">
            <svg class="c-sidebar-nav-icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-grid"></use>
              </svg> Uitgebreid overzicht</a></li>     

          <?php if ( $_SESSION['client_role'] == 'admin'  ) { ?>
           
         
          <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_dashboard_fast.php">
            <svg class="c-sidebar-nav-icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-list"></use>
              </svg> Kort overzicht  </a></li>     
        <?php } ?>

         

          <?php 
          if ( $_SESSION['client_role'] == 'employee' ) {
            ?><li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_shelly_control.php">
              <svg class="c-sidebar-nav-icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-alarm"></use>
              </svg> Fotokast aan- en uitzetten</a></li><?php
          }
          ?>

          <?php 
          if ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner'  ) { ?>
           <li class="c-sidebar-nav-title">Hardware</li>    
           




          <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_shelly_control.php">
            <svg class="c-sidebar-nav-icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-alarm"></use>
              </svg> Fotokast aan- en uitzetten</a></li>      
              
               <?php if ( $_SESSION['client_role'] == 'admin' ) { ?>
            <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_anydesk.php">
            <svg class="c-sidebar-nav-icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-external-link"></use>
              </svg> Anydesk  </a></li>     
        <?php } ?>

          <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_power_planner.php">
            <svg class="c-sidebar-nav-icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-calendar"></use>
              </svg> Power planner</a></li>          
              
              

    <?php if ( $shelly_switch_only == 0 ) { ?>
          <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_printer_overview.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-print"></use>
                  </svg> Printer</a>
            </li> 
          <?php if ( $_SESSION['client_role'] == 'admin'  ) { ?>
            <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_payter_overview.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-money"></use>
                  </svg> Betaalterminal</a>
            </li> 
          <?php } ?>

          <?php if ( $_SESSION['client_role'] == 'admin'  ) { ?>
          <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_modem_overview.php">
            <svg class="c-sidebar-nav-icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-phone"></use>
              </svg> Modem en SIM</a></li>             
          <?php } ?>

          <?php if ( $_SESSION['client_role'] == 'admin'  ) { ?>
          <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_api_admin_shelly.php">
            <svg class="c-sidebar-nav-icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-swap-horizontal"></use>
              </svg> API settings</a></li>             
          <?php } ?>

            <?php if ( $_SESSION['client_role'] == 'admin'  ) { ?>
          <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_test_mode.php">
            <svg class="c-sidebar-nav-icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-bolt"></use>
              </svg> Test mode aan/uit</a></li>             
          <?php } ?>
         
           
         
         

                  
          <li class="c-sidebar-nav-title">Strook set manager</li>       
            <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="https://api2.magicshoot.nl/dashboard/pixazo.html">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-brightness"></use>
                  </svg> AI Strook maker - beta-test</a></li>  

             <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_set_overview.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-grid"></use>
                  </svg> Set overzicht</a></li>      
                    
             <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_set_planner.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-calendar"></use>
                  </svg> Set planner</a></li>  
 <?php if ( $_SESSION['client_role'] == 'admin' ) { ?>
           <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_strip_layout_overview.php">
                 <svg class="c-sidebar-nav-icon">
                   <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-object-group"></use>
                 </svg> Layouts</a></li>   
<?php } ?>

            <li class="c-sidebar-nav-title">Resultaten</li>
                
                <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_invoice_overview.php">
                    <svg class="c-sidebar-nav-icon">
                      <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-bar-chart"></use>
                    </svg> Aantallen & Financieel </a></li>
              <?php if ( $_SESSION['client_role'] == 'admin' ) { ?>
               <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_total_verrekening_overview.php">
                    <svg class="c-sidebar-nav-icon">
                      <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-euro"></use>
                    </svg> Reservering </a></li>     
                    <?php } ?>
              <?php if ( $_SESSION['client_role'] == 'admin' ) { ?>
               <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="facerecognition/index.php">
                    <svg class="c-sidebar-nav-icon">
                      <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-euro"></use>
                    </svg> FaceRecog </a></li>     
                    <?php } ?>

               <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_gallery.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-view-column"></use>
                  </svg> Strookjes laatste maand</a></li> 
              <?php 
              //if ( $_SESSION['client_role'] == 'admin'  ) { ?>     
              <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_event_zip.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-cloud-download"></use>
                  </svg> Foto zip</a></li>

               
        
          <?php //} ?>

        
       <?php } // end 
              
             } // end shelly_switch_only ?>



  



             <li class="c-sidebar-nav-title">Support</li>

                  <?php
                  // Count open tickets for badge
                  $nav_open_tickets = 0;
                  if ($_SESSION['client_role'] === 'admin') {
                      $nav_tq = mysqli_query($cn, "SELECT COUNT(*) AS cnt FROM support_ticket WHERE ticket_status NOT IN ('closed','cancelled')");
                      if ($nav_tq) { $nav_tr = $nav_tq->fetch_object(); $nav_open_tickets = (int)$nav_tr->cnt; }
                  } else {
                      $nav_cid = (int)$_SESSION['client_id'];
                      $nav_tq = mysqli_query($cn, "SELECT COUNT(*) AS cnt FROM support_ticket WHERE created_by_client_id='$nav_cid' AND ticket_status NOT IN ('closed','cancelled')");
                      if ($nav_tq) { $nav_tr = $nav_tq->fetch_object(); $nav_open_tickets = (int)$nav_tr->cnt; }
                  }
                  ?>

                  <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_tickets.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-task"></use>
                  </svg> Support Tickets<?php if ($nav_open_tickets > 0) { ?> <span class="badge badge-danger badge-pill ml-auto"><?php echo $nav_open_tickets; ?></span><?php } ?></a></li>

                  <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_ticket_new.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-plus"></use>
                  </svg> Nieuw ticket</a></li>

                  <?php if ($_SESSION['client_role'] === 'admin') { ?>
                  <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_ticket_topics.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-list"></use>
                  </svg> Edit ticket subjects</a></li>
                  <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_ticket_notify.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-bell"></use>
                  </svg> Notificaties</a></li>
                  <?php } ?>

                  <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_support_printer.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-print"></use>
                  </svg> Printer uitleg</a></li>

                  <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="page_support.php">
                  <svg class="c-sidebar-nav-icon">
                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                  </svg> Contact</a></li>          

                
      </ul>
      <button class="c-sidebar-minimizer c-class-toggler" type="button" data-target="_parent" data-class="c-sidebar-minimized"></button>
    </div>
    <div class="c-wrapper c-fixed-components">
      <header class="c-header c-header-light c-header-fixed c-header-with-subheader">
        <button class="c-header-toggler c-class-toggler d-lg-none mfe-auto" type="button" data-target="#sidebar" data-class="c-sidebar-show">
          <svg class="c-icon c-icon-lg">
            <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-menu"></use>
          </svg>
        </button><a class="c-header-brand d-lg-none" href="https://www.magicshoot.nl">
          <svg width="118" height="46" alt="MagicShoot">
            <use xlink:href="assets/brand/magicshoot_logo_zonder_url.svg#full"></use>
          </svg></a>
        <button class="c-header-toggler c-class-toggler mfs-3 d-md-down-none" type="button" data-target="#sidebar" data-class="c-sidebar-lg-show" responsive="true">
          <svg class="c-icon c-icon-lg">
            <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-menu"></use>
          </svg>
        </button>

        <ul class="c-header-nav d-md-down-none">
          <li class="c-header-nav-item px-3"><a class="c-header-nav-link" href="page_home.php">Home</a></li>
          
          
        </ul>
        <ul class="c-header-nav ml-auto mr-4">
          
       
          
          <li class="c-header-nav-item dropdown"><a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
              <div class="c-avatar"> <svg class="c-icon mr-2">
                  <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                </svg></div>
            </a>
            <div class="dropdown-menu dropdown-menu-right pt-0">
              <div class="dropdown-header bg-light py-2"><strong>Klant</strong></div>

                
                <p  class="dropdown-item">Role: <?php echo $_SESSION['client_role']; ?></p>
               

                
                <a class="dropdown-item" href="page_change_password.php"><svg class="c-icon mr-2">
                  <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-lock-locked"></use>
                </svg> Wachtwoord Wijzigen</a>

                <a class="dropdown-item" href="index.php?mode=logout"><svg class="c-icon mr-2">
                  <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-account-logout"></use>
                </svg> Uitloggen</a>
            </div>
          </li>
        </ul>
     