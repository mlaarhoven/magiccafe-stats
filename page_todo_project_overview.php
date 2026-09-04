  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");

  $event_id = $_SESSION['event_id'];

  $mode           = mysqli_real_escape_string ( $cn, $_REQUEST['mode']);
  $project_id     = mysqli_real_escape_string ( $cn, $_REQUEST['project_id']);
  $prio_new       = mysqli_real_escape_string ( $cn, $_REQUEST['prio_new']);
  $prio_cur       = mysqli_real_escape_string ( $cn, $_REQUEST['prio_cur']);
  

  if ( $mode == "change_prio" ) {
      # switch the prios 
      $sql           = "UPDATE todo_project
                            SET project_prio = $prio_cur
                          WHERE project_prio = '" . $prio_new . "'";
      $result        = mysqli_query( $cn , $sql );

      $sql           = "UPDATE todo_project
                           SET project_prio = $prio_new
                         WHERE project_id = '" . $project_id . "'";
      $result        = mysqli_query( $cn , $sql );
  }
  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Projecten</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,400;0,500;0,700;0,900;1,400&display=swap" rel="stylesheet">
  <style>
   
  .sortable_list {
    margin: 20px;
  }

  </style>
 <link rel="stylesheet" href="styles.css">
              <div class="sortable_list">
              <!-- eerste optie: sortable list -->
                    <!-- https://dev.to/hassancoder/drag-and-drop-sortable-list-using-html-css-js-a-comprehensive-guide-10po -->
                    
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
                    <script src="sortable_list.js" defer></script>
                    <ul class="sortable-list">
                    <?php     
                        # haal projecten op        
                        $sql = "SELECT project_id,
                                       project_name,
                                       project_description,
                                       project_prio,
                                       member_photo_filename,
                                       status_name,
                                       status_color
                                  FROM todo_project
                             LEFT JOIN todo_member USING (member_id)
                             LEFT JOIN todo_status USING (status_id)
                                  ORDER BY project_prio ASC
                                
                                  ";
                        
                        $result   = mysqli_query( $cn , $sql );
                        if ( !$result ){echo "ERROR bij ophalen projecten";}
                        
                        while ( $return = $result->fetch_object() ) {
                          $project_id             = $return->project_id;
                          $project_name           = $return->project_name;
                          $project_description    = $return->project_description;
                          $project_prio           = $return->project_prio;
                          $member_photo_filename  = $return->member_photo_filename;
                          $status_name            = $return->status_name;
                          $status_color           = $return->status_color;
                          
                          $voortgangs_percentage = get_voortgangs_percentage ( $project_id );
                          $min_prio = get_min_prio ();
                          $max_prio = get_max_prio ();
                          ?>
                    
                              <li class="item" draggable="true" id="<?php echo $project_id; ?>">
                                <div class="details"> 
                                    <span>
                                            
                                            <span class="sort_project_name"><a href="page_todo_task_overview.php?project_id=<?php echo $project_id; ?>">
                                            <?php echo $project_name; ?></a></span>
                                            
                                            <span class="sort_member_photo"><img src="../<?php echo $member_photo_filename; ?>" class="member_photo"/></span>
                                            
                                            <span class="voortgang_container">
                                              <span class="voortgang_ondergrond"></span>
                                              <span class="voortgang" style="width: <?php echo $voortgangs_percentage; ?>px;"></span>
                                              <span class="percentage"><?php echo $voortgangs_percentage; ?> %</span>
                                            </span>

                                            <span class="sort_status" style="background-color:<?php echo $status_color; ?>;"><?php echo $status_name; ?></span>

                                           <span class="sort_last_updated"><?php echo get_last_update_date ( $project_id ); ?></span>
                                          </tr>
                                  </span>
                                </div>
                                <i class="uil uil-draggabledots"></i>
                              </li>
                          <?php } ?>    
                        </ul>

                
                        </div>

                <!-- optie twee -->
        <!--         <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">
       
         
          
            <div class="row">
              <div class="col-sm-9 col-md-9 col-lg-9">
                <div class="card"> 
                  <div class="card-header bg-info content-center">
                      <h2  class="project_header">Projecten</h2>
                  </div>
                   
                  <div class="card-body">
                   
                    <table class="project_table">
                     <thead>
                        <tr>
                        
                          <th scope="col">Volgorde</th>
                          <th scope="col">Projectnaam</th>
                          <th scope="col">Eigenaar</th> 
                          <th scope="col">Voortgang</th>
                          <th scope="col">Status</th>
                          <th scope="col">Laatste update</th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php     
                       /*
                        # haal projecten op        
                        $sql = "SELECT project_id,
                                       project_name,
                                       project_description,
                                       project_prio,
                                       member_photo_filename,
                                       status_name,
                                       status_color
                                  FROM todo_project
                             LEFT JOIN todo_member USING (member_id)
                             LEFT JOIN todo_status USING (status_id)
                                  ORDER BY project_prio ASC
                                
                                  ";
                        
                        $result   = mysqli_query( $cn , $sql );
                        if ( !$result ){echo "ERROR bij ophalen projecten";}
                        
                        while ( $return = $result->fetch_object() ) {
                          $project_id             = $return->project_id;
                          $project_name           = $return->project_name;
                          $project_description    = $return->project_description;
                          $project_prio           = $return->project_prio;
                          $member_photo_filename  = $return->member_photo_filename;
                          $status_name            = $return->status_name;
                          $status_color           = $return->status_color;
                          
                          $voortgangs_percentage = get_voortgangs_percentage ( $project_id );
                          $min_prio = get_min_prio ();
                          $max_prio = get_max_prio ();
                        //echo "project prio = $project_prio min = $min_prio max = $max_prio<br>";
                         
                          ?> 
                          <tr>
                           <!-- <td><?php echo $project_prio; ?></td>-->
                            <td class="project-prio">
                              <?php if ( $project_prio > $min_prio ) { 
                                $prio_new = $project_prio - 1;
                                ?>
                                <a href="page_todo_project_overview.php?mode=change_prio&project_id=<?php echo $project_id; ?>&prio_cur=<?php echo $project_prio; ?>&prio_new=<?php echo $prio_new; ?>"><img class="img_prio" src="../img/up.png"></a>
                                <?php } ?>
                                
                                <?php if ( $project_prio < $max_prio ) { 
                                  $prio_new = $project_prio + 1;
                                  ?>  
                                <a href="page_todo_project_overview.php?mode=change_prio&project_id=<?php echo $project_id; ?>&prio_cur=<?php echo $project_prio; ?>&prio_new=<?php echo $prio_new; ?>"><img class="img_prio" src="../img/down.png"></a>
                                <?php } ?>

                               
                                </td>
                            <td class="project-name"><a href="page_todo_task_overview.php?project_id=<?php echo $project_id; ?>"><?php echo $project_name; ?></a></td>
                            <td class="member_photo_td"><img src="../<?php echo $member_photo_filename; ?>" class="member_photo"/></td>
                            <td>
                                <span class="voortgang_ondergrond"></span>
                                <span class="voortgang" style="width: <?php echo $voortgangs_percentage; ?>px;"></span>
                                <span class="percentage"><?php echo $voortgangs_percentage; ?> %</span>
                            </td>
                            <td nowrap="nowrap" class="status" style="background-color:<?php echo $status_color; ?>;"><?php echo $status_name; ?></td>
                            <td class="last_updated"><?php echo get_last_update_date ( $project_id ); ?></td>
                          </tr>
                        <?php } 
                        
                        */
                        ?>
                  </tbody>
                    </table>

            
                  </div>
                </div>
              </div>
                                
            </div>          
          </div>
          </div>
           
      </main>
       </div>-->
      <footer class="c-footer">
        <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> © <?php echo date('Y'); ?> MagicShoot.</div>
        <div class="ml-auto">Powered by&nbsp;<a href="https://www.MagicShoot.nl/">MagicShoot</a></div>
        </footer>
      </div>
    </div>  

<?php
# ------- FUNCTIONS ------- #
function get_voortgangs_percentage ( $project_id ) {
  global $cn;
  # totaal aantal taken bij dit project
  $sql           = "SELECT COUNT(*) as aantal
                      FROM todo_task
                     WHERE project_id = '" . $project_id . "'";
  
  $result        = mysqli_query( $cn , $sql );
  $return        = $result->fetch_object();
  $aantal_tasks  = $return->aantal;
  //echo $aantal_tasks . "<br>";
  # taken met status_id 4 (afgerond)
  $sql           = "SELECT COUNT(*) as aantal_afgerond
                      FROM todo_task
                     WHERE project_id = '" . $project_id . "'
                     AND status_id = '4'"; 
                    
  $result        = mysqli_query( $cn , $sql );
  $return        = $result->fetch_object();
  $aantal_tasks_afgerond  = $return->aantal_afgerond;
  //echo $aantal_tasks_afgerond . "<br>";
  if ( $aantal_tasks > 0  ) {
    $voortgangs_percentage = round ( 100 * ( $aantal_tasks_afgerond / $aantal_tasks ) );
  } else {
    $voortgangs_percentage = 0;
  }
  return ( $voortgangs_percentage );
}

function get_last_update_date ( $project_id ) {
  global $cn;
  # totaal aantal taken bij dit project
  $sql           = "SELECT MAX(task_updated) as last_updated
                      FROM todo_task
                     WHERE project_id = '" . $project_id . "'";
  
  $result        = mysqli_query( $cn , $sql );
  $return        = $result->fetch_object();
  $last_updated  = $return->last_updated;
  if ( $last_updated <> "") {
    setlocale(LC_ALL, 'nl_NL');
    $last_updated  = date ( "d M Y" , strtotime ( $last_updated ) );
  } else { $last_updated = '';}  
  return ( $last_updated );
}

function get_min_prio () {
  global $cn;
  $sql           = "SELECT MIN(project_prio) as minimum
                      FROM todo_project";
  $result        = mysqli_query( $cn , $sql );
  $return        = $result->fetch_object();
  $min_prio      = $return->minimum;
  return $min_prio;
}

function get_max_prio () {
  global $cn;
  $sql           = "SELECT MAX(project_prio) as maximum
                      FROM todo_project";
  $result        = mysqli_query( $cn , $sql );
  $return        = $result->fetch_object();
  $max_prio      = $return->maximum;
  return $max_prio;

}


?>
<?php include("partial_footer.php"); ?>