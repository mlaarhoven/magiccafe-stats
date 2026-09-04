<?php 
include '../inc/connection.inc.php';
# lijst bevat de project_ids in de nieuwe volgorde

$lijst     = mysqli_real_escape_string ( $cn, $_REQUEST['lijst']);
$lijst_array = explode("," , $lijst );
$lengte = count ( $lijst_array );
for ( $t = 0; $t < $lengte ; $t ++ ){
  $sql = "UPDATE todo_project
             SET project_prio = $t
           WHERE project_id = '" . $lijst_array[ $t ]. "'";
  $result        = mysqli_query( $cn , $sql );
}
echo "project order saved lijst = " . $lijst;

?>