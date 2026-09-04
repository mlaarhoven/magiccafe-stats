<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
#-------------------------------------LOGIN FUNCTION-------------------------------------------#
function inloggen ()
	{
	global $cn;
	
	$username 		= mysqli_real_escape_string ( $cn, $_REQUEST['username'] );
	$password 		= mysqli_real_escape_string ( $cn, $_REQUEST['password'] );
	$password_md5 	= md5( $password ); 


	$sql= "SELECT client_id, 
				  client_role,
				  client_name
			 FROM photobooth_stat_client
		    WHERE client_email 				= '$username'     AND 
				  client_password 		    = '$password_md5' AND
				  client_active				= '1'";
	$result 	= mysqli_query( $cn , $sql );
	
	if ( $result )
		{
		$num 		= mysqli_num_rows ( $result );
		if ( $num > 0 )
			{
			$return 	= mysqli_fetch_object ( $result );
			$_SESSION['client_id']   = $return->client_id;
			$_SESSION['client_role'] = $return->client_role;
			$_SESSION['client_name'] = $return->client_name;
			// haal nu een lijstje event_id's op waarmee deze client iets mag doen en de rechten per event_id
			$sql = "SELECT event_id
			FROM photobooth_event_ids 
			 LEFT JOIN photobooth_stat_client_event USING(event_id) 
             WHERE photobooth_stat_client_event.client_id ='". $_SESSION['client_id'] .  "' 
			 ORDER BY 	dashboard_order_nr	 DESC ";
			$result             = mysqli_query( $cn , $sql );
			while ( $return     = mysqli_fetch_object ( $result ) ) {
				$event_id   = $return->event_id;
				
				$_SESSION['event_id']    = $event_id; //set current event_id = eerste in de dashboard volgorde
			}
			
			return true;
			}
		else
			{
			return false;
			}	
		}
	else
		{
		return false;
		}	
	}	
	
function login ()
	{
	global $login_flag;
	$login_flag = false;
	?>
	<form name="login_form" action="index.php"  method="POST"  class="inlogform">
    <input type="hidden" name="mode" value="login">
   
     <div class="card-group">
            <div class="card p-4">
              <div class="card-body">
              	<div valign="center" width="500">
              	<img src="assets/brand/magicshoot_logo_zonder_url_zwart_woord_shoit.svg" width="280" ></div>
                      
                <h1>Inloggen</h1>
                <p class="text-muted">MagicShoot Photobooth</p>
                <div class="input-group sm-6 mb-5">
                 
                  <div class="input-group-prepend"><span class="input-group-text">
                      <svg class="c-icon">
                        <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                      </svg></span></div>
                  <input class="form-control" type="text" placeholder="E-mailadres" name="username">
                </div>
                
                <div class="input-group mb-4">
                  <div class="input-group-prepend"><span class="input-group-text">
                      <svg class="c-icon">
                        <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-lock-locked"></use>
                      </svg></span></div>
                  <input class="form-control" type="password" placeholder="Wachtwoord" name="password">
                </div>
                
                <div class="row">
                  <div class="col-6">
                    <button class="btn btn-primary px-4" type="submir">Inloggen</button>
                  </div>
                  <div class="col-6 text-right">
                    <a href="page_forgot_password.php" class="btn btn-link px-0">Wachtwoord vergeten?</a>
                  </div>
                </div>
              </div>
            </div>
         
          </div>


          

	
</form> 
		
		
	<?php
	$login_flag = true;
	}

function logout()
	{
	global $login_flag;
	unset ( $_SESSION['client_id'] );
	$login_flag = false;
	header('Location: https://stats.magicshoot.nl/index.php');
	}


function get_event_name( $event_id )
	{
	global $cn;	

	$sql= "SELECT event_name 
			 FROM event
		    WHERE event_id = '" . $event_id . "'";
			
	$result = mysqli_query( $cn , $sql );
	$return	= mysqli_fetch_object ( $result );
	$event_name = $return->event_name;
	return $event_name;		
	}

	function get_shelly_switch_only( $event_id )
	{
	global $cn;	

	$sql= "SELECT shelly_switch_only
			 FROM event
		    WHERE event_id = '" . $event_id . "'";
			
	$result = mysqli_query( $cn , $sql );
	$return	= mysqli_fetch_object ( $result );
	$shelly_switch_only = $return->shelly_switch_only;
	return $shelly_switch_only;		
	}


/*
function show_choose_page()
	{
	global $cn;	

	$sql= "SELECT event_id 
			 FROM photobooth_stat_client_event
		    WHERE client_id = '" . $_SESSION['client_id']. "'";
	//echo $sql;	
	$result = mysqli_query( $cn , $sql );
	$num 	= mysqli_num_rows ( $result );
	if ( $num > 1 ) {
		return 1;
		}
	else {
		return 0;
		}
					
	}	
	*/
?>