# AgriFarm - Agricultural Marketplace Platform

A complete web-based marketplace platform that connects farmers and buyers for agricultural products. Built with PHP and MySQL, featuring admin dashboard, seller management, shopping cart, bidding system, and secure payments.

---

## 🌾 What is AgriFarm?

AgriFarm is a three-tier marketplace platform where:
- **Farmers** can list and sell agricultural products
- **Buyers** can browse, bid on products, and make purchases
- **Admins** manage the entire platform (products, orders, users, transactions)

---

## ✨ Key Features

### For Farmers
- Product management (add, update, delete products)
- Real-time bidding on orders
- View buyer bids and accept/reject offers
- Weather integration for crop planning
- Feedback and rating system
- Return product handling
- Mobile number updates

### For Buyers
- Browse and search agricultural products
- Add products to cart
- Place bids on farmer products
- Secure payment processing
- Product rating and reviews
- Website rating system
- Return products if needed
- Real-time cart updates

### For Admin
- Complete dashboard with analytics
- Product approval workflow
- Order management
- Transaction tracking
- Return product approval
- User management

---

## 🛠️ Technology Stack

| Component | Technology |
|-----------|-----------|
| **Backend** | PHP 7.4+ / PHP 8.x |
| **Database** | MySQL / MariaDB |
| **Server** | Apache (XAMPP recommended) |
| **Frontend** | HTML5, CSS3, JavaScript |
| **Email** | PHPMailer (SMTP) |
| **Dependencies** | Composer |

---

## 📋 Prerequisites

Before you start, make sure you have:
- PHP 7.4 or higher
- MySQL or MariaDB database
- Apache web server (XAMPP for Windows)
- Composer (for dependency management)
- SMTP credentials for email notifications (Gmail, SendGrid, etc.)

---

## ⚡ Quick Start Guide

### Step 1: Setup Project Directory
```bash
# Place the project in your web server root
# For XAMPP: C:\xampp\htdocs\agrifarm
# For Linux: /var/www/html/agrifarm
```

### Step 2: Create Database
```bash
# Option A: Using phpMyAdmin
1. Open http://localhost/phpmyadmin
2. Click "New" → Create database "agri_farm"
3. Select database → Import tab
4. Choose Database.sql file → Import

# Option B: Using Command Line
mysql -u root -p < Database.sql
```

### Step 3: Configure Database Connection
Edit db.php and update credentials:
```php
$host = "localhost";      // Your database host
$user = "root";           // Your MySQL username
$pass = "";               // Your MySQL password
$dbname = "agri_farm";    // Your database name
```

### Step 4: Setup Email (SMTP)
Edit buyer_farmer-dashboard/email/email_config.php:
```php
$mail_host = "smtp.gmail.com";    // SMTP server
$mail_username = "your@email.com"; // Your email
$mail_password = "your-app-password"; // App password
$mail_port = 587;                 // SMTP port
```

### Step 5: Configure File Uploads
Ensure these directories are writable:
```bash
chmod 755 uploads/
chmod 755 buyer_farmer-dashboard/farmer/uploads/
```

### Step 6: Install Dependencies
```bash
composer install
```

### Step 7: Test Your Setup
```bash
# Test database connection
http://localhost/agrifarm/admin_dashboard/test_admin_connection.php

# Test email configuration
http://localhost/agrifarm/admin_dashboard/test_email.php
```

---

## 🌐 Access URLs

Once setup is complete, access these URLs:

| User Type | URL | Default Credentials |
|-----------|-----|-------------------|
| **Buyer** | http://localhost/agrifarm/home_pages/index.html | Sign up required |
| **Farmer** | http://localhost/agrifarm/home_pages/index.html | Sign up required |
| **Admin** | http://localhost/agrifarm/admin_dashboard/admin_login.php | Ask admin |
| **Main Page** | http://localhost/agrifarm/ | - |

---

## 📁 Project Structure

```
agrifarm/
├── home_pages/                 # Public homepage and info pages
│   ├── index.html             # Main landing page
│   ├── features.html          # Platform features
│   ├── about.html             # About us
│   └── contact.html           # Contact page
│
├── admin_dashboard/           # Admin panel
│   ├── admin_login.php        # Admin login page
│   ├── admin_dashboard.php    # Main admin dashboard
│   ├── admin_orders.php       # Order management
│   ├── admin_transactions.php # Payment tracking
│   ├── admin_return_products.php # Handle returns
│   └── approve_product.php    # Product approval
│
├── buyer_farmer-dashboard/    # User dashboards
│   ├── buyer/                 # Buyer interface
│   │   ├── buyer_home.php        # Buyer dashboard
│   │   ├── buyer_bidding.php     # Bidding interface
│   │   ├── cart.php              # Shopping cart
│   │   ├── payment.php           # Payment page
│   │   ├── product_ratings_api.php # Rate products
│   │   └── buyer_return_product.php # Return items
│   │
│   ├── farmer/                # Farmer interface
│   │   ├── farmer_home.php       # Farmer dashboard
│   │   ├── farmer_bidding.php    # View buyer bids
│   │   ├── add_product.php       # Add new product
│   │   ├── update_product.php    # Edit product
│   │   ├── delete_product.php    # Remove product
│   │   ├── farmer_weather.php    # Weather info
│   │   └── uploads/              # Product images folder
│   │
│   └── email/                 # Email system
│       ├── email_config.php   # SMTP configuration
│       ├── email_functions.php # Email helper functions
│       └── otp_verify.php     # OTP verification
│
├── login/                     # Authentication
│   ├── login.php             # User login
│   ├── Registration.php      # User registration
│   ├── forgot_password.php   # Password reset
│   └── reset_password.php    # Reset link handler
│
├── Database.sql              # Database schema
├── init_database.php         # Database initialization script
├── alter_orders_payment.sql  # Payment table updates
├── db.php                    # Database connection file
├── composer.json             # PHP dependencies
└── vendor/                   # Installed packages (PHPMailer)
```

---

## 🔐 Security Features

- Session management to prevent unauthorized access
- Password encryption for user accounts
- HTML special character escaping to prevent XSS attacks
- Database connection pooling
- CSRF token protection (to be implemented)
- Input validation and sanitization

---

## 📧 Email Setup Guide

### Using Gmail (Recommended for Development)

1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable "Less secure app access" or use App Passwords
3. In buyer_farmer-dashboard/email/email_config.php:
   ```php
   $mail_host = "smtp.gmail.com";
   $mail_username = "your-email@gmail.com";
   $mail_password = "your-16-digit-app-password";
   $mail_port = 587;
   ```

### Using Other SMTP Services
- SendGrid, Mailgun, AWS SES, Office 365 - use their SMTP credentials

### Test Email Setup
Visit: http://localhost/agrifarm/admin_dashboard/test_email.php

---

## 🌤️ Weather Integration

The farmer dashboard includes weather forecasting. To enable:

1. Read WEATHER_SETUP_GUIDE.md
2. Get API key from [OpenWeatherMap](https://openweathermap.org/api) or similar
3. Configure in buyer_farmer-dashboard/farmer/weather_config.php

---

## 💳 Payment System

The platform integrates with payment gateways. Payment flow:

1. Buyer adds products to cart → Checkout
2. Enters payment details in buyer_farmer-dashboard/buyer/payment.php
3. Payment processed via buyer_farmer-dashboard/buyer/process_payment.php
4. Success confirmation at buyer_farmer-dashboard/buyer/payment_success.php
5. Order details stored in database for admin tracking

---

## 🐛 Troubleshooting

### Database Connection Error
**Problem**: "Connection failed"
**Solution**:
- Verify MySQL is running
- Check credentials in db.php
- Ensure database "agri_farm" exists
- Test: http://localhost/agrifarm/admin_dashboard/test_admin_connection.php

### File Upload Not Working
**Problem**: "Unable to upload file"
**Solution**:
```bash
# Linux/Mac
chmod 755 uploads/
chmod 755 buyer_farmer-dashboard/farmer/uploads/

# Windows - Right-click → Properties → Security → Edit permissions
```

### Email Not Sending
**Problem**: "SMTP connection failed"
**Solution**:
- Test SMTP: http://localhost/agrifarm/admin_dashboard/test_email.php
- Check firewall isn't blocking SMTP port (587)
- Verify email credentials in email_config.php
- Enable "Less secure apps" for Gmail

### Blank White Page
**Problem**: Shows nothing
**Solution**:
- Enable PHP error display in php.ini:
  ```
  display_errors = On
  error_reporting = E_ALL
  ```
- Check Apache error logs
- Verify all includes/requires point to correct files

### API Not Working
**Problem**: "Cannot GET /api"
**Solution**:
- Ensure .htaccess is configured correctly
- Test endpoints directly: products_api.php?action=list
- Check database connection

---

## 🚀 Deployment

### For Production:

1. **Security**
   - Change default admin credentials
   - Update database credentials
   - Use environment variables for sensitive data
   - Enable HTTPS/SSL

2. **Database**
   - Use strong MySQL passwords
   - Backup database regularly
   - Consider using cloud database services

3. **Hosting**
   - Use a reliable web hosting provider
   - Configure proper file permissions (644 files, 755 dirs)
   - Set up automated backups

4. **Performance**
   - Enable Apache compression
   - Optimize images
   - Use caching headers
   - Consider CDN for static files

---

## 📚 Useful Files Reference

| File | Purpose |
|------|---------|
| db.php | Database connection configuration |
| Database.sql | Complete database schema |
| composer.json | PHP dependencies list |
| WEATHER_SETUP_GUIDE.md | Weather integration guide |
| admin_dashboard/ADMIN_SETUP_GUIDE.md | Admin panel setup |
| buyer_farmer-dashboard/email/EMAIL_SETUP_GUIDE.md | Email configuration |

---

## 🤝 Contributing

Want to contribute? Great! Here's how:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/AmazingFeature`
3. Commit changes: `git commit -m 'Add AmazingFeature'`
4. Push to branch: `git push origin feature/AmazingFeature`
5. Open a Pull Request

---

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 💡 Tips for Development

- **Local Testing**: Use XAMPP for quick local development
- **Database Backup**: Always backup before testing major changes
- **Error Logging**: Keep PHP error logs enabled during development
- **Version Control**: Use Git to track changes
- **Code Comments**: Document complex logic for future maintenance
- **Testing**: Test on multiple browsers (Chrome, Firefox, Safari, Edge)
- **Mobile**: Test responsive design on mobile devices

---

## 📞 Support

For issues and questions:
- Check the troubleshooting section above
- Review the setup guides in subdirectories
- Check Apache/MySQL error logs
- Test database and email connections

---

**Happy Farming! 🌾**

Last updated: January 2026
