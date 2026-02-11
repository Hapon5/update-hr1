<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

echo "<h1>SMTP Test Diagnostic</h1>";

$mail = new PHPMailer(true);
try {
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "<code>$str</code><br>";
    };

    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'linbilcelestre31@gmail.com';
    $mail->Password   = 'oothfogbgznnfkdp';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Bypassing SSL verification
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Recipients
    $mail->setFrom('linbilcelestre31@gmail.com', 'Diagnostic Test');
    $mail->addAddress('linbilcelestre31@gmail.com'); // Sending to self for test

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test - ' . date('Y-m-d H:i:s');
    $mail->Body    = 'This is a test email to verify SMTP settings.';

    echo "<h3>Attempting to send...</h3>";
    $mail->send();
    echo "<h2 style='color:green;'>Message has been sent successfully!</h2>";
} catch (Exception $e) {
    echo "<h2 style='color:red;'>Message could not be sent.</h2>";
    echo "<b>Mailer Error:</b> " . $mail->ErrorInfo;
}
?>
