<?php
function is_gmail_filled_in( $user_id )
{
	global $cn_beheer;
	$sql = "SELECT user_name,
	   			   user_gmailaccount_name,
	   			   user_gmailaccount_pwd
              FROM user 
	         WHERE user_id   = '" . $user_id . "'";
    $result   = mysqli_query( $cn_beheer , $sql );
    if ( !$result )
      {
      ?><div class="alert alert-warning" role="alert">ERROR  bij ophalen users bij deze client <?php $sql . mysqli_error( $cn_beheer );?></div><?php  
      }
    $return = $result->fetch_object();
    if ( $return->user_name <> "" && $return->user_gmailaccount_name <> "" && $return->user_gmailaccount_pwd <> "")
  		{ 
  		return true;
    	}
    else 
  		{
    	return false;
    	}  
}

function is_background_photo_filled_in( $user_id )
{
	global $cn_beheer;
	$sql = "SELECT user_background_photo
              FROM user 
	         WHERE user_id   = '" . $user_id . "'";
	$result   = mysqli_query( $cn_beheer , $sql );
	if ( !$result )
    {
    ?><div class="alert alert-warning" role="alert">ERROR  bij ophalen users bij deze client <?php $sql . mysqli_error( $cn_beheer );?></div><?php  
    }

	$return = $result->fetch_object();
	if ( $return->user_background_photo <> "" )
		{ 
	  	return true;
	    }
	else {
	    return false;
	    }  
}

function is_user_contact_filled_in( $user_id )
{
	global $cn_beheer;
	$sql = "SELECT *
              FROM user 
	         WHERE user_id   = '" . $user_id . "'";
    $result   = mysqli_query( $cn_beheer , $sql );
    if ( !$result )
        {
         
          ?><div class="alert alert-warning" role="alert">ERROR  bij ophalen users bij deze client <?php $sql . mysqli_error( $cn_beheer );?></div><?php  
        }
    $return = $result->fetch_object();
    $user_contact_name_1 = $return->user_contact_name_1;
    $user_contact_name_2 = $return->user_contact_name_2;
    $user_contact_name_3 = $return->user_contact_name_3;
    $user_contact_name_4 = $return->user_contact_name_4;
    $user_contact_name_5 = $return->user_contact_name_5;
    $user_contact_name_6 = $return->user_contact_name_6;

    $user_contact_phone_1 = $return->user_contact_phone_1;
    $user_contact_phone_2 = $return->user_contact_phone_2;
    $user_contact_phone_3 = $return->user_contact_phone_3;
    $user_contact_phone_4 = $return->user_contact_phone_4;
    $user_contact_phone_5 = $return->user_contact_phone_5;
    $user_contact_phone_6 = $return->user_contact_phone_6;

    if ( $user_contact_name_1 <> "" && $user_contact_phone_1 <> "" ||
     	 $user_contact_name_2 <> "" && $user_contact_phone_2 <> "" ||
         $user_contact_name_3 <> "" && $user_contact_phone_3 <> "" ||
         $user_contact_name_4 <> "" && $user_contact_phone_4 <> "" ||
         $user_contact_name_5 <> "" && $user_contact_phone_5 <> "" ||
         $user_contact_name_6 <> "" && $user_contact_phone_6 <> "" ) 
    	 { 
         return true;
         }
    else {
         return false;
         }  
}


function is_approved( $user_id )
{
	global $cn_beheer;
	$sql = "SELECT user_approved_date_time
              FROM user 
	         WHERE user_id   = '" . $user_id . "'";
  $result   = mysqli_query( $cn_beheer , $sql );
  if ( !$result )
    {
     
      ?><div class="alert alert-warning" role="alert">ERROR  bij ophalen users bij deze client <?php $sql . mysqli_error( $cn_beheer );?></div><?php  
    }
  $return = $result->fetch_object();
  $user_approved_date_time = $return->user_approved_date_time;
     
  if ( $user_approved_date_time <> "0000-00-00 00:00:00") 
   	{ 
   	return true;
    }
  else 
    {
    return false;
    }  
}

function save_gmail( $user_id, $user_name, $user_gmailaccount_name, $user_gmailaccount_pwd )
{
	global $cn_beheer;
	$user_id 				        = mysqli_real_escape_string ( $cn_beheer, $user_id);
	$user_name 				      = mysqli_real_escape_string ( $cn_beheer, $user_name);
	$user_gmailaccount_name = mysqli_real_escape_string ( $cn_beheer, $user_gmailaccount_name);
	$user_gmailaccount_pwd 	= mysqli_real_escape_string ( $cn_beheer, $user_gmailaccount_pwd);
	$sql = "UPDATE user 
			   SET user_name 				        = '" . $user_name 				      . "',
			   	   user_gmailaccount_name 	= '" . $user_gmailaccount_name 	. "',
			   	   user_gmailaccount_pwd 	  = '" . $user_gmailaccount_pwd 	. "'
	         WHERE user_id   				    = '" . $user_id 				        . "'";
  $result   = mysqli_query( $cn_beheer , $sql );
  if ( !$result )
   	{ 
   	?><div class="alert alert-warning" role="alert">ERROR bij updaten user met gmail adres en password <?php $sql . mysqli_error( $cn_beheer );?></div><?php  
     return false;
   	}
  return true;  
}

function save_knop( $user_id, $knopnummer, $contact_name, $contact_phone )
{
	global $cn_beheer;
	$user_id 			= mysqli_real_escape_string ( $cn_beheer, $user_id);
	$knopnummer 		= mysqli_real_escape_string ( $cn_beheer, $knopnummer);
	$contact_name 		= mysqli_real_escape_string ( $cn_beheer, $contact_name);
	$contact_phone 		= mysqli_real_escape_string ( $cn_beheer, $contact_phone);
	$sql = "UPDATE user 
			       SET user_contact_name_"  . $knopnummer . "= '" . $contact_name            . "',
			   	   	   user_contact_phone_" . $knopnummer . "= '" . $contact_phone           . "'
	         WHERE user_id   				                     = '" . $user_id 				         . "'";
    $result   = mysqli_query( $cn_beheer , $sql );
    if ( !$result )
    	{ 
      ?><div class="alert alert-warning" role="alert">ERROR bij updaten user met contact_name en contact_phone <?php $sql . mysqli_error( $cn_beheer );?></div><?php    
      return false;
    	}
  return true;      
}

function save_approved( $user_id )
{
	global $cn_beheer;
	$date_time = date("Y-m-d H:i:s");
	$sql = "UPDATE user 
			       SET user_approved_date_time = '" . $date_time             . "'
	         WHERE user_id                 = '" . $user_id               . "'";
    $result   = mysqli_query( $cn_beheer , $sql );
    
    if ( !$result )
    	{
       ?><div class="alert alert-warning" role="alert">ERROR bij updaten user met approved_date_time <?php $sql . mysqli_error( $cn_beheer );?></div><?php
      return false;
    	}
    mail ("info@sitan.nl", "OMAFOON || User akkoord" , "User heeft akkoord gegeven voor productie, user_id=" . $user_id );
    return true;   
}

?>