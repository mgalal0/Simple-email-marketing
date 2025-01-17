# Email Marketing Campaign Management System

A PHP-based web application for managing email marketing campaigns with features for email validation, campaign management, and SMTP configuration. Built for the Institute of Administrative Development.

## Features

### 1. User Authentication
- Secure login system
- Session management
- User role-based access control

### 2. Dashboard
- Overview of campaign statistics
- Total campaigns sent
- Total recipients reached
- SMTP configuration status
- Recent campaign history

### 3. Email Filter
- Email list validation
- Domain DNS record checking
- Real-time validation feedback
- Copy validated emails to clipboard
- Detailed error reporting for invalid emails
- Statistics display (total, valid, invalid counts)

### 4. Campaign Management
- Create and send email campaigns
- Rich text editor for email composition
- File attachment support
- Real-time sending progress
- Campaign history tracking
- Campaign deletion capability
- Support for bulk email sending

### 5. SMTP Configuration
- Configurable SMTP settings
- Support for various SMTP providers
- Secure password management
- Configurable sending delays
- From name and email customization
- TLS/SSL encryption support

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PHP Extensions:
  - PDO
  - MySQLi
  - OpenSSL
  - Fileinfo
  - GD

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
```

2. Create a MySQL database and import the schema:
```sql
CREATE DATABASE email_marketing;
USE email_marketing;
```

3. Configure the database connection in `config.php`:
```php
$conn = new mysqli('localhost', 'username', 'password', 'database_name');
```

4. Install dependencies:
```bash
composer require phpmailer/phpmailer
```

5. Set up the required database tables:
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE smtp_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    host VARCHAR(255) NOT NULL,
    port INT NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    encryption ENUM('tls', 'ssl') NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    delay_ms INT DEFAULT 1000
);

CREATE TABLE email_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject VARCHAR(255) NOT NULL,
    recipient_count INT NOT NULL,
    status VARCHAR(255) NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Configuration

### SMTP Settings
1. Navigate to the SMTP Settings page
2. Configure your SMTP server details:
   - Host (e.g., smtp.gmail.com)
   - Port (e.g., 587 for TLS)
   - Username
   - Password
   - Encryption type (TLS/SSL)
   - From email and name
   - Sending delay (ms)

### Security Considerations
- Keep your config.php file secure
- Use strong passwords
- Regularly update dependencies
- Set appropriate file permissions
- Enable HTTPS for secure data transmission

## Usage

### Email Filtering
1. Navigate to Email Filter page
2. Paste your email list (one email per line)
3. Click "Filter Emails"
4. Review validation results
5. Copy valid emails for campaign use

### Sending Campaigns
1. Go to Send Email page
2. Enter campaign subject
3. Compose message using rich text editor
4. Add attachments (optional)
5. Paste recipient list
6. Monitor sending progress in real-time

### Managing Campaigns
- View campaign history on dashboard
- Track success/failure rates
- Delete old campaigns as needed
- Monitor SMTP configuration status

## File Structure
```
├── components/
│   └── sidebar.php
├── vendor/
├── config.php
├── dashboard.php
├── email_filter.php
├── login.php
├── logout.php
├── send_email.php
├── smtp_config.php
└── README.md
```

## Libraries Used

- Bootstrap 5.1.3
- Font Awesome 5.15.4
- jQuery 3.5.1
- Summernote Editor
- PHPMailer

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

Mahmoud Galal 

## Support

For support, please contact me or Open issue ticket
