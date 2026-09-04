<?php
global $cn; 
include '../inc/connection.inc.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$message_type = '';

// Handle forgot password form submission
if ($_POST && $_POST['mode'] == 'forgot_password') {
    $email = mysqli_real_escape_string($cn, $_POST['email']);
    
    // Check if email exists in database
    $sql = "SELECT client_id, client_name, client_email FROM photobooth_stat_client 
            WHERE client_email = '$email' AND client_active = '1'";
    $result = mysqli_query($cn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $client = mysqli_fetch_object($result);
        
        // Generate a unique token
        $token = bin2hex(random_bytes(32));
        $expires = time() + 3600; // Token expires in 1 hour
        
        // Store token in directory (create if doesn't exist)
        $token_dir = __DIR__ . '/temp/password_reset/';
        if (!is_dir($token_dir)) {
            mkdir($token_dir, 0755, true);
        }
        
        // Store token with expiration
        $token_data = array(
            'client_id' => $client->client_id,
            'email' => $client->client_email,
            'name' => $client->client_name,
            'expires' => $expires
        );
        
        file_put_contents($token_dir . $token . '.json', json_encode($token_data));
        
        // Create reset link
        $reset_link = "https://stats.magicshoot.nl/page_reset_password.php?token=" . $token;
        
        // Send email with reset link
        $subject = "MagicShoot - Wachtwoord Resetten";
        $message_body = "Hallo " . $client->client_name . ",\n\n";
        $message_body .= "U heeft een verzoek ingediend om uw wachtwoord opnieuw in te stellen.\n\n";
        $message_body .= "Klik op de volgende link om uw wachtwoord opnieuw in te stellen:\n";
        $message_body .= $reset_link . "\n\n";
        $message_body .= "Deze link verloopt over 1 uur.\n\n";
        $message_body .= "Als u dit verzoek niet hebt ingediend, negeer deze e-mail.\n\n";
        $message_body .= "Met vriendelijke groeten,\nMagicShoot Team";
        
        $headers = "From: noreply@magicshoot.nl\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // Send email
        mail($email, $subject, $message_body, $headers);
        
        $message = 'Een e-mail met instructies voor het opnieuw instellen van het wachtwoord is verzonden naar ' . htmlspecialchars($email);
        $message_type = 'success';
    } else {
        // Don't reveal if email exists or not (security)
        $message = 'Een e-mail met instructies voor het opnieuw instellen van het wachtwoord is verzonden naar ' . htmlspecialchars($email);
        $message_type = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord Vergeten</title>
    <link rel="stylesheet" href="vendors/@coreui/coreui/css/coreui.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="c-app flex-row align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="POST" action="page_forgot_password.php" class="inlogform">
                    <input type="hidden" name="mode" value="forgot_password">
                    
                    <div class="card-group">
                        <div class="card p-4">
                            <div class="card-body">
                                <div valign="center" width="500">
                                    <img src="assets/brand/magicshoot_logo_zonder_url_zwart_woord_shoit.svg" width="280" >
                                </div>
                                
                                <h1>Wachtwoord Vergeten</h1>
                                <p class="text-muted">MagicShoot Photobooth</p>
                                
                                <?php if ($message): ?>
                                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                        <?php echo $message; ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <?php if ($message_type == 'success'): ?>
                                        <div class="row">
                                            <div class="col-12">
                                                <a href="index.php" class="btn btn-primary px-4">Terug naar inloggen</a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted mb-4">Voer uw e-mailadres in en wij sturen u een link om uw wachtwoord opnieuw in te stellen.</p>
                                    
                                    <div class="input-group sm-6 mb-5">
                                        <div class="input-group-prepend"><span class="input-group-text">
                                            <svg class="c-icon">
                                                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-envelope-closed"></use>
                                            </svg></span></div>
                                        <input class="form-control" type="email" placeholder="E-mailadres" name="email" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <button type="submit" class="btn btn-primary px-4">Verzenden</button>
                                        </div>
                                        <div class="col-6 text-right">
                                            <a href="index.php" class="btn btn-link px-0">Terug naar inloggen</a>
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
