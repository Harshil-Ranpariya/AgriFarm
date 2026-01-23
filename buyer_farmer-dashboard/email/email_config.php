<?php

define('SMTP_HOST', 'mail.aerisgo.in'); 
define('SMTP_PORT', 587); 
define('SMTP_USERNAME', 'no-reply@aerisgo.in'); 
define('SMTP_PASSWORD', 'AerisGo@2025*'); 
define('SMTP_ENCRYPTION', 'TLS');
define('SMTP_FROM_EMAIL', 'no-reply@aerisgo.in'); 
define('SMTP_FROM_NAME', 'AgriFarm'); 

define('OTP_SUBJECT', 'AgriFarm - Email Verification Code');
define('OTP_EXPIRY_MINUTES', 10);


function getEmailConfig() {
    return [
        'host' => SMTP_HOST,
        'port' => SMTP_PORT,
        'username' => SMTP_USERNAME,
        'password' => SMTP_PASSWORD,
        'encryption' => SMTP_ENCRYPTION,
        'from_email' => SMTP_FROM_EMAIL,
        'from_name' => SMTP_FROM_NAME
    ];
}
?>