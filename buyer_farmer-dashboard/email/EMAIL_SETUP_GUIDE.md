# Email OTP Setup Guide for AgriFarm

This guide will help you configure SMTP email functionality for OTP verification in your AgriFarm project.

## Files Created/Modified

### New Files:
- `email_config.php` - SMTP configuration settings
- `email_functions.php` - Email sending functions and templates
- `test_email.php` - Test your email configuration
- `EMAIL_SETUP_GUIDE.md` - This setup guide

### Modified Files:
- `Registration.php` - Updated to send OTP via email
- `otp_verify.php` - Added resend OTP functionality

## Setup Instructions

### 1. Configure SMTP Settings

Edit `email_config.php` and update the following settings:

```php
// For Gmail (Recommended)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password'); // Use App Password, not regular password
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'AgriFarm');
```

### 2. Gmail Setup (Recommended)

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate an App Password**:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate a password for "Mail"
   - Use this password in `email_config.php`

### 3. Alternative Email Providers

#### Outlook/Hotmail:
```php
define('SMTP_HOST', 'smtp-mail.outlook.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
```

#### Yahoo:
```php
define('SMTP_HOST', 'smtp.mail.yahoo.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
```

#### Custom SMTP Server:
```php
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_PORT', 587); // or 465 for SSL
define('SMTP_ENCRYPTION', 'tls'); // or 'ssl'
```

### 4. Test Your Configuration

1. Open `test_email.php` in your browser
2. Enter your email address
3. Click "Send Test Email"
4. Check your inbox for the test email

### 5. Features Included

#### Email Templates:
- **HTML Email**: Beautiful, responsive design with AgriFarm branding
- **Plain Text**: Fallback for email clients that don't support HTML
- **Professional Styling**: Green theme matching your agricultural theme

#### Security Features:
- **OTP Expiration**: 10-minute expiry time
- **Unique OTPs**: 6-digit random codes
- **Input Validation**: Email format and OTP format validation
- **SQL Injection Protection**: Prepared statements

#### User Experience:
- **Resend OTP**: Users can request new OTP if needed
- **Clear Instructions**: Step-by-step guidance for users
- **Error Handling**: Graceful fallback if email fails
- **Responsive Design**: Works on all devices

### 6. Troubleshooting

#### Common Issues:

1. **"Authentication failed"**
   - Check your email and password
   - For Gmail, make sure you're using an App Password
   - Ensure 2FA is enabled

2. **"Connection refused"**
   - Check SMTP host and port
   - Verify firewall settings
   - Try different ports (587, 465, 25)

3. **"SSL/TLS error"**
   - Try changing encryption from 'tls' to 'ssl' or vice versa
   - Check if your server supports the encryption method

4. **Emails not received**
   - Check spam/junk folder
   - Verify email address is correct
   - Test with different email providers

#### Debug Mode:
Add this to `email_functions.php` for detailed error messages:
```php
$mail->SMTPDebug = 2; // Enable verbose debug output
```

### 7. Production Considerations

1. **Security**:
   - Never commit `email_config.php` with real credentials
   - Use environment variables for sensitive data
   - Consider using a dedicated email service (SendGrid, Mailgun, etc.)

2. **Performance**:
   - Implement email queuing for high volume
   - Add rate limiting for OTP requests
   - Monitor email delivery rates

3. **Monitoring**:
   - Log email sending attempts
   - Track OTP verification success rates
   - Set up alerts for email failures

### 8. Testing the Complete Flow

1. Go to `Registration.html`
2. Fill out the registration form
3. Submit the form
4. Check your email for the OTP
5. Go to the OTP verification page
6. Enter the OTP code
7. Test the "Resend OTP" functionality

### 9. Customization

#### Email Template:
Edit the `generateOTPEmailTemplate()` function in `email_functions.php` to customize:
- Colors and styling
- Logo and branding
- Content and messaging
- Layout and structure

#### OTP Settings:
Modify these constants in `email_config.php`:
- `OTP_EXPIRY_MINUTES` - How long OTPs are valid
- `OTP_SUBJECT` - Email subject line
- `SMTP_FROM_NAME` - Sender name

## Support

If you encounter any issues:
1. Check the error messages in `test_email.php`
2. Verify your SMTP settings
3. Test with different email providers
4. Check server logs for detailed error information

The system includes fallback functionality - if email sending fails, the OTP will still be displayed to the user for testing purposes.
