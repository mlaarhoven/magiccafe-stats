  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  
  $event_id = $_SESSION['event_id'];
    # check role
    if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
      header('Location: https://stats.magicshoot.nl/admin/index.php');
      exit;
    }
  
  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Transactie overzicht</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">
       
            <?php             
            $sql = "SELECT * 
                      FROM log_session
                      WHERE event_id = '" . $event_id . "' AND
                      disposition <> 'CARD ERR'
                      ORDER BY  log_session_id DESC";
            $result   = mysqli_query( $cn , $sql );
            if ( !$result ){echo "ERROR bij ophalen sessies";}
            $counter = 0;
        
            ?> 
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Transactie overzicht</h2>
                    </div>
                  <div class="card-body">
                  <a href="page_invoice_overview.php" class="btn btn-success">Maandafrekening</a>
                  <a href="page_monthly_overview.php" class="btn btn-info">Omzet per maand</a>
                  <a href="page_revenue_per_evening.php" class="btn btn-info">Omzet per werkdag/avond</a>
                  <a href="page_revenue_per_workweek.php" class="btn btn-info">Omzet per werkweek</a>
                  <a href="page_session_overview.php?aantal=40" class="btn btn-warning">Sessie overzicht</a>
                  <a href="page_transaction_overview.php" class="btn btn-warning">Transactie overzicht</a><br><br>
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                        <thead>
                          <tr>
                                                   
                            <th>Sessie ID</th>
                            <th>Created</th>
                            <th>Amount</th>
                            <th>State</th>
                            <th>Disposition</th>
                          </tr>
                        </thead>
                        <tbody>
                        <?php
                        setlocale(LC_MONETARY, "nl_NL");              
                        while ( $return = $result->fetch_object() )
                          {

                            $log_session_id = $return->log_session_id;
                            $sessie_start    = $return->sessie_start;
                            $amount         = $return->prijs;
                            $disposition    = $return->disposition;
                            $state          = $return->state;
                            $serialNumber   = $return->txnsn;
                            $transactionId  = $return->txnid;

                           
                            $message = '';
                            if ( trim( $disposition ) == 'DECLINED' )    {
                              // TODO gewoon erbij joinen?
                              $sql3   = "SELECT message
                                          FROM payter
                                        WHERE transactionId  = '" . $transactionId  . "' 
                                          AND serialNumber   = '" . $serialNumber   . "' ";
                              $result3 = mysqli_query( $cn , $sql3 );
                              $return3 = $result3->fetch_object();
                             if ( isset ( $return3 )) { $message = $return3->message; }
                           
                            }
                          ?>
                          <tr>
                            <td><a href="page_session_detail.php?log_session_id=<?php echo $return->log_session_id; ?>"><?php echo $return->log_session_id; ?></a></td>
                            <td><?php echo $sessie_start; ?></td>
                            <td><?php echo $amount; ?></td>
                            <td><?php echo $state; ?></td>
                            <td><?php echo $disposition . " " . $message; ?></td>
                          
                          </tr>
                        <?php } ?>
                        </tbody>
                      </table>
                   
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