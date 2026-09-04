  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");

  $event_id = $_SESSION['event_id'];
  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Project en Taken </li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <style>
    .project_header {
      font-size: 20px; 
      font-weight: 700;
      padding-top: 15px; 
      padding-bottom: 10px;
      font-family: 'Roboto', sans-serif;
      text-transform: uppercase;
      color: #fff;

    }
    .project-name, td.project-name a {
      font-size: 16px; 
      font-weight: 500;
      padding: 8px;
      padding-left: 16px;
      padding-right: 16px;
      font-family: 'Roboto', sans-serif;
      color: #505051;
    }
    .member_photo {
      
      border:0;
      border-radius: 30px;
      height: 30px;
      width: 30px;

    }
   
      
    td.status {
          font-family: 'Roboto', sans-serif;
          color: #fff;
          font-weight: 500;
          font-size: 15px;
          text-align: center;
          
        }
      
        td,
    th {
      border: 1px solid rgb(190, 190, 190);
      padding: 10px;
    }

    td {
      text-align: left;
      
    }

    tr:nth-child(even) {
      background-color: #eee;
    }

    th[scope='col'] {
      background-color: #696969;
      color: #fff;
      font-size: 16px; 
      font-weight: 500;
    }

    th[scope='row'] {
      background-color: #d7d9f2;
    }

    caption {
      padding: 10px;
      caption-side: bottom;
    }
    td.member_photo_td {
      text-align: center;
      width: 100px;
      display: block;
      border-bottom: 0;
      border-left: 0;
      border-right: 0;

    }
    table {
      border-collapse: collapse;
      border: 2px solid rgb(200, 200, 200);
      font-family: 'Roboto', sans-serif;
      font-size: 0.8rem;
      
    }
   .task_description {
     font-size: 0.65rem;
   }

    .last_updated {
      text-align: center;
      font-family: 'Roboto', sans-serif;
      font-weight: bold;
    }
   .project_description {
    display: block;
    padding-left: 10px;
    font-size: 1.1rem;
    font-weight: bold;
   } 
  </style>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">
           <?php 
                  $project_id = mysqli_real_escape_string ( $cn, $_REQUEST['project_id']);
                   # haal project omschrijving op        
                   $sql = "SELECT project_description, project_name 
                             FROM todo_project
                            WHERE project_id = '" . $project_id . "'";
                   $result   = mysqli_query( $cn , $sql );
                   $return = $result->fetch_object();
                   $project_description  = $return->project_description;         
                   $project_name  = $return->project_name;         
                  ?> 
         
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-6 col-md-6 col-lg-6">
            
                <div class="card"> 
                  <div class="card-header bg-info content-center">
                      <h2  class="project_header">Project: <?php echo $project_name; ?></h2>
                  </div>
                 
                  <div class="card-body">
                    
                    <p class="project_description"><?php echo $project_description; ?></p>
                    <table class="project_table">
                     <thead>
                        <tr>
                          <th scope="col">Taaknaam</th>
                          <th scope="col">Eigenaar</th> 
                          
                          <th scope="col">Status</th>
                          <th scope="col">Laatste update</th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php     

                      
                        # haal taken op        
                        $sql = "SELECT task_id,
                                       task_name,
                                       task_description,
                                       task_prio,
                                       member_photo_filename,
                                       status_name,
                                       status_color
                                  FROM todo_task
                             LEFT JOIN todo_member USING (member_id)
                             LEFT JOIN todo_status USING (status_id)
                                  WHERE project_id = '" . $project_id . "'";
                        
                        $result   = mysqli_query( $cn , $sql );
                        if ( !$result ){echo "ERROR bij ophalen taken";}
                        
                        while ( $return = $result->fetch_object() ) {
                          $task_id                = $return->task_id;
                          $task_name              = $return->task_name;
                          $task_description       = $return->task_description;
                          $task_prio              = $return->task_prio;
                          $member_photo_filename  = $return->member_photo_filename;
                          $status_name            = $return->status_name;
                          $status_color           = $return->status_color;
                          
                          ?> 
                          <tr>
                            <td class="project-name"><?php echo $task_name; ?><br><span class="task_description"><?php echo $task_description; ?></span></td>
                           
                            <td class="member_photo_td"><img src="../<?php echo $member_photo_filename; ?>" class="member_photo"/></td>
                           
                            <td nowrap="nowrap" class="status" style="background-color:<?php echo $status_color; ?>;"><?php echo $status_name; ?></td>
                            <td class="last_updated"><?php echo get_last_update_date ( $task_id ); ?></td>
                          </tr>
                        <?php }  ?>
                      </tbody>
                    </table>
                  </div>
                </div>
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


    <?php
# ------- FUNCTIONS ------- #


function get_last_update_date ( $task_id ) {
  global $cn;
  # totaal aantal taken bij dit project
  $sql           = "SELECT MAX(task_updated) as last_updated
                      FROM todo_task
                     WHERE task_id = '" . $task_id . "'";
  
  $result        = mysqli_query( $cn , $sql );
  $return        = $result->fetch_object();
  $last_updated  = $return->last_updated;
  if ( $last_updated <> "") {
    setlocale(LC_ALL, 'nl_NL');
    $last_updated  = date ( "d M Y" , strtotime ( $last_updated ) );
  } else { $last_updated = '';}  
  return ( $last_updated );
}
?>
<?php include("partial_footer.php"); ?>