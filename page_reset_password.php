<?php
global $cn; 
include '../inc/connection.inc.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$message_type = '';
$token_valid = false;
$client_id = '';
$client_name = '';

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $message = 'Ongeldig of verlopen resettoken!';
    $message_type = 'danger';
} else {
    $token = $_GET['token'];
    $token_dir = __DIR__ . '/temp/password_reset/';
    $token_file = $token_dir . $token . '.json';
    
    // Check if token file exists
    if (file_exists($token_file)) {
        $token_data = json_decode(file_get_contents($token_file), true);
        
        // Check if token has expired
        if ($token_data['expires'] > time()) {
            $token_valid = true;
            $client_id = $token_data['client_id'];
            $client_name = $token_data['name'];
        } else {
            $message = 'Het resettoken is verlopen. Vraag een nieuw token aan.';
            $message_type = 'danger';
            // Delete expired token
            unlink($token_file);
        }
    } else {
        $message = 'Ongeldig resettoken!';
        $message_type = 'danger';
    }
}

// Handle password reset form submission
if ($token_valid && $_POST && $_POST['mode'] == 'reset_password') {
    $new_password = mysqli_real_escape_string($cn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($cn, $_POST['confirm_password']);
    
    // Validate passwords match
    if ($new_password !== $confirm_password) {
        $message = 'Wachtwoorden komen niet overeen!';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'Wachtwoord moet minstens 6 tekens lang zijn!';
        $message_type = 'danger';
    } else {
        // Update password
        $new_password_md5 = md5($new_password);
        $update_sql = "UPDATE photobooth_stat_client 
                      SET client_password = '$new_password_md5' 
                      WHERE client_id = '$client_id'";
        
        if (mysqli_query($cn, $update_sql)) {
            $message = 'Wachtwoord succesvol opnieuw ingesteld! U kunt nu inloggen.';
            $message_type = 'success';
            $token_valid = false; // Hide form
            
            // Delete token file
            unlink($token_file);
        } else {
            $message = 'Er is een fout opgetreden bij het opnieuw instellen van het wachtwoord!';
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
    <title>Wachtwoord Opnieuw Instellen</title>
    <link rel="stylesheet" href="vendors/@coreui/coreui/css/coreui.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="c-app flex-row align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="POST" action="page_reset_password.php?token=<?php echo isset($token) ? htmlspecialchars($token) : ''; ?>" class="inlogform">
                    <input type="hidden" name="mode" value="reset_password">
                    
                    <div class="card-group">
                        <div class="card p-4">
                            <div class="card-body">
                                <div valign="center" width="500">
                                    <img src="assets/brand/magicshoot_logo_zonder_url_zwart_woord_shoit.svg" width="280" >
                                </div>
                                
                                <h1>Wachtwoord Opnieuw Instellen</h1>
                                <p class="text-muted">MagicShoot Photobooth</p>
                                
                                <?php if ($message): ?>
                                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                        <?php echo $message; ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($token_valid && isset($_POST['mode']) && $_POST['mode'] == 'reset_password' && $message_type == 'success'): ?>
                                    <div class="row">
                                        <div class="col-12">
                                            <a href="index.php" class="btn btn-primary px-4">Terug naar inloggen</a>
                                        </div>
                                    </div>
                                <?php elseif ($token_valid): ?>
                                    <p class="text-muted mb-4">Welkom <?php echo htmlspecialchars($client_name); ?>! Voer uw nieuwe wachtwoord in.</p>
                                    
                                    <div class="input-group mb-4">
                                        <div class="input-group-prepend"><span class="input-group-text">
                                            <svg class="c-icon">
                                                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-lock-locked"></use>
                                            </svg></span></div>
                                        <input class="form-control" type="password" placeholder="Nieuw Wachtwoord" name="new_password" required>
                                    </div>
                                    
                                    <div class="input-group mb-4">
                                        <div class="input-group-prepend"><span class="input-group-text">
                                            <svg class="c-icon">
                                                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-lock-locked"></use>
                                            </svg></span></div>
                                        <input class="form-control" type="password" placeholder="Wachtwoord Bevestigen" name="confirm_password" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <button type="submit" class="btn btn-primary px-4">Wachtwoord Instellen</button>
                                        </div>
                                        <div class="col-6 text-right">
                                            <a href="index.php" class="btn btn-link px-0">Annuleren</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <div class="col-12">
                                            <a href="index.php" class="btn btn-primary px-4">Terug naar inloggen</a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="vendors/@popperjs/core/umd/popper.min.js"></script>
    <script src="vendors/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
