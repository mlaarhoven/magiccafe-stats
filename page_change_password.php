 
  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/shelly_functions.php");

// Check if user is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$message_type = '';

// Handle password change form submission
if ($_POST && $_POST['mode'] == 'change_password') {
    $current_password = mysqli_real_escape_string($cn, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($cn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($cn, $_POST['confirm_password']);
    
    $client_id = $_SESSION['client_id'];
    
    // Validate passwords match
    if ($new_password !== $confirm_password) {
        $message = 'Nieuwe wachtwoorden komen niet overeen!';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'Wachtwoord moet minstens 6 tekens lang zijn!';
        $message_type = 'danger';
    } else {
        // Verify current password
        $current_password_md5 = md5($current_password);
        $sql = "SELECT client_id FROM photobooth_stat_client 
                WHERE client_id = '$client_id' AND client_password = '$current_password_md5'";
        $result = mysqli_query($cn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            // Current password is correct, update to new password
            $new_password_md5 = md5($new_password);
            $update_sql = "UPDATE photobooth_stat_client 
                          SET client_password = '$new_password_md5' 
                          WHERE client_id = '$client_id'";
            
            if (mysqli_query($cn, $update_sql)) {
                $message = 'Wachtwoord succesvol gewijzigd!';
                $message_type = 'success';
                // Clear form
                $_POST = array();
            } else {
                $message = 'Er is een fout opgetreden bij het wijzigen van het wachtwoord!';
                $message_type = 'danger';
            }
        } else {
            $message = 'Huidige wachtwoord is incorrect!';
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord Wijzigen</title>
    <link rel="stylesheet" href="vendors/@coreui/coreui/css/coreui.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="c-app">
    <?php include 'inc/partial_header.php'; ?>
    <?php include 'inc/partial_nav.php'; ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
       <li class="breadcrumb-item">Wachtwoord wijzigen</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in"></div> 
   
                <div class="container-lg">
                    <div class="row">
                        <div class="col-md-6 offset-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <?php if ($message): ?>
                                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                            <?php echo $message; ?>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="page_change_password.php">
                                        <input type="hidden" name="mode" value="change_password">
                                        
                                        <div class="form-group">
                                            <label for="current_password">Huidge Wachtwoord</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="new_password">Nieuw Wachtwoord</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="confirm_password">Wachtwoord Bevestigen</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">Wachtwoord Wijzigen</button>
                                            <a href="page_home.php" class="btn btn-secondary">Annuleren</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'inc/partial_footer.php'; ?>
    
    <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="vendors/@popperjs/core/umd/popper.min.js"></script>
    <script src="vendors/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
