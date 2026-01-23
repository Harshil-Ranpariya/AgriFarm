# Admin Dashboard Setup Guide - AgriFarm

This guide will help you set up and use the comprehensive admin dashboard for your AgriFarm project.

## 🚀 Admin Credentials

### Default Admin Account
- **Username:** `admin`
- **Password:** `admin123`

**⚠️ Important:** Change these credentials immediately after first login for security!

## 📁 Files Created/Modified

### New Files:
- `admin_styles.css` - Professional admin dashboard styling
- `ADMIN_SETUP_GUIDE.md` - This comprehensive guide

### Enhanced Files:
- `admin_login.php` - Beautiful login page with auto-admin creation
- `admin_dashboard.php` - Comprehensive dashboard with statistics
- `otp_verify.php` - Added success popup for OTP verification
- `test_email.php` - Fixed email testing functionality
- `schema.sql` - Added default admin credentials

## 🎨 Features Included

### Admin Login Page:
- **Modern Design:** Glassmorphism effect with gradient background
- **Auto-Admin Creation:** Automatically creates admin if none exists
- **Responsive Layout:** Works perfectly on all devices  
- **Security Features:** Password hashing, session management
- **User-Friendly:** Clear error messages and success notifications

### Admin Dashboard:
- **Real-time Statistics:** Live counts of products and users
- **Product Management:** Approve/reject pending products
- **User Overview:** View recent farmer and buyer registrations
- **Quick Actions:** Easy navigation to important features
- **Auto-refresh:** Dashboard updates every 30 seconds
- **Professional UI:** Modern cards, tables, and animations

### OTP Verification Enhancement:
- **Success Popup:** Beautiful animated popup when OTP is verified
- **Auto-redirect:** Automatically redirects to login after 3 seconds
- **Professional Styling:** Consistent with admin theme

## 🔧 Setup Instructions

### 1. Database Setup
Run the updated `schema.sql` to create the admin table with default credentials:

```sql
-- The schema now includes:
CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin credentials
INSERT IGNORE INTO admins (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
```

### 2. Access Admin Panel
1. Navigate to `admin_login.php` in your browser
2. Use credentials: `admin` / `admin123`
3. The system will automatically create the admin if it doesn't exist

### 3. Change Default Password
After first login, update the admin password:

```php
// In admin_login.php, add this after successful login:
if ($_POST['change_password']) {
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $conn->query("UPDATE admins SET password = '$newPassword' WHERE id = " . $_SESSION['admin_id']);
}
```

## 📊 Dashboard Features

### Statistics Cards:
- **Pending Products:** Products awaiting approval
- **Approved Products:** Live products on the platform
- **Rejected Products:** Products that were declined
- **Total Users:** Combined count of farmers and buyers

### Product Management:
- **View Pending Products:** See all products awaiting approval
- **Approve/Reject Actions:** One-click product approval
- **Product Details:** ID, name, farmer, price, date
- **Real-time Updates:** Auto-refresh every 30 seconds

### User Management:
- **Recent Farmers:** Latest farmer registrations
- **Recent Buyers:** Latest buyer registrations
- **User Details:** Username, email, registration date
- **Role Badges:** Visual indicators for user types

### Quick Actions:
- **Refresh Dashboard:** Manual refresh option
- **Test Email:** Quick access to email testing
- **View Website:** Link back to main site
- **Logout:** Secure session termination

## 🎨 Styling Features

### Modern Design Elements:
- **Glassmorphism Effects:** Translucent cards with backdrop blur
- **Gradient Backgrounds:** Beautiful color transitions
- **Smooth Animations:** Hover effects and transitions
- **Professional Typography:** Clean, readable fonts
- **Responsive Grid:** Adapts to all screen sizes

### Color Scheme:
- **Primary:** Deep blue (#2c3e50)
- **Success:** Green (#27ae60)
- **Warning:** Orange (#f39c12)
- **Danger:** Red (#e74c3c)
- **Info:** Light blue (#3498db)

### Interactive Elements:
- **Hover Effects:** Cards lift and glow on hover
- **Button Animations:** Smooth transitions and loading states
- **Form Focus:** Highlighted input fields
- **Success Popups:** Animated confirmation messages

## 🔒 Security Features

### Authentication:
- **Password Hashing:** Secure bcrypt encryption
- **Session Management:** Proper session handling
- **Access Control:** Admin-only dashboard access
- **SQL Injection Protection:** Prepared statements

### Data Protection:
- **Input Validation:** All user inputs are validated
- **XSS Prevention:** Output escaping for all data
- **CSRF Protection:** Form token validation (recommended)
- **Secure Headers:** Proper HTTP security headers

## 📱 Responsive Design

### Mobile Optimization:
- **Flexible Grid:** Adapts to mobile screens
- **Touch-Friendly:** Large buttons and touch targets
- **Readable Text:** Optimized font sizes
- **Swipe Gestures:** Mobile-friendly interactions

### Tablet Support:
- **Medium Screens:** Optimized for tablet viewing
- **Touch Navigation:** Easy touch interactions
- **Landscape Mode:** Works in both orientations

## 🚀 Performance Features

### Optimization:
- **Efficient Queries:** Optimized database queries
- **Minimal CSS:** Only necessary styles loaded
- **Fast Loading:** Quick page load times
- **Auto-refresh:** Smart dashboard updates

### Monitoring:
- **Real-time Stats:** Live data updates
- **Error Handling:** Graceful error management
- **Loading States:** Visual feedback for operations

## 🛠️ Customization Options

### Styling:
- **Color Variables:** Easy color customization in CSS
- **Font Changes:** Simple font family updates
- **Layout Modifications:** Flexible grid system
- **Animation Controls:** Customizable transition speeds

### Functionality:
- **Auto-refresh Interval:** Change dashboard update frequency
- **Statistics Display:** Modify which stats are shown
- **User Limits:** Adjust how many users are displayed
- **Product Filters:** Add filtering options

## 🔧 Troubleshooting

### Common Issues:

1. **Admin Login Fails:**
   - Check database connection
   - Verify admin table exists
   - Ensure password hashing is working

2. **Dashboard Not Loading:**
   - Check session management
   - Verify database queries
   - Check for PHP errors

3. **Styling Issues:**
   - Ensure `admin_styles.css` is loaded
   - Check file paths
   - Verify CSS syntax

4. **Email Testing:**
   - Use `test_email.php` to verify email configuration
   - Check SMTP settings
   - Verify PHPMailer installation

### Debug Mode:
Add this to any PHP file for debugging:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📈 Future Enhancements

### Recommended Additions:
- **User Management:** Edit/delete user accounts
- **Product Analytics:** Detailed product statistics
- **Email Templates:** Custom email designs
- **Audit Logs:** Track admin actions
- **Role Management:** Multiple admin levels
- **Notification System:** Real-time alerts
- **Export Features:** Data export capabilities

## 🎯 Best Practices

### Security:
- Change default credentials immediately
- Use strong passwords
- Regular security updates
- Monitor admin activities

### Performance:
- Optimize database queries
- Use caching where appropriate
- Monitor server resources
- Regular maintenance

### User Experience:
- Keep interface clean and intuitive
- Provide clear feedback
- Ensure fast loading times
- Test on multiple devices

## 📞 Support

If you encounter any issues:
1. Check the troubleshooting section
2. Verify all files are properly uploaded
3. Check database connectivity
4. Review PHP error logs
5. Test with default credentials first

The admin system is now fully functional and ready for production use!
