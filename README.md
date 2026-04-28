# AcadFlow - Academic Management System

A complete web application for managing academic institutions with role-based access control, built using PHP, MySQL, HTML5, CSS3, and vanilla JavaScript.

## Features

### 🔐 Authentication & Security
- Secure login/registration system
- Password hashing using PHP's built-in functions
- Session-based authentication
- Role-based access control (RBAC)

### 👥 Role-Based Access Control
- **Admin**: Full system access (manage users, courses, view reports)
- **Teacher**: Manage students, mark attendance, assign grades
- **Student**: View personal details, grades, and attendance

### 📊 Core Features
- Dynamic dashboard with role-specific content
- CRUD operations for users, students, and courses
- Attendance management system
- Grade management and tracking
- Responsive design for mobile and desktop
- Form validation (client-side and server-side)

### 🎨 User Interface
- Modern, clean design
- Responsive layout using Flexbox and CSS Grid
- Mobile-friendly navigation
- Interactive elements with JavaScript

## Technology Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Backend**: PHP (Core PHP, no frameworks)
- **Database**: MySQL
- **Styling**: Custom CSS with Flexbox/Grid
- **Security**: PHP sessions, password hashing

## Installation & Setup

### Prerequisites
- XAMPP, WAMP, or similar local server environment
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Step 1: Clone/Download the Project
```bash
# If using git
git clone <repository-url>
cd AcadFlow

# Or download and extract the ZIP file
```

### Step 2: Database Setup
1. Start your local server (XAMPP/WAMP)
2. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
3. Create a new database named `acadflow`
4. Import the database structure by running the SQL file:
   - Go to the `setup` folder
   - Open `database.sql` in phpMyAdmin
   - Execute the SQL commands

### Step 3: Configure Database Connection
1. Open `config/database.php`
2. Update the database credentials if needed:
   ```php
   $host = 'localhost';
   $dbname = 'acadflow';
   $username = 'root';  // Your MySQL username
   $password = '';      // Your MySQL password
   ```

### Step 4: Access the Application
1. Place the project in your web server directory (e.g., `htdocs` for XAMPP)
2. Open your browser and navigate to: `http://localhost/AcadFlow`

## Project Structure

```
AcadFlow/
├── index.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # Main dashboard
├── profile.php            # User profile management
├── logout.php             # Logout functionality
├── config/
│   └── database.php       # Database configuration
├── setup/
│   └── database.sql       # Database structure and sample data
├── admin/                 # Admin-specific pages
│   ├── users.php          # User management
│   ├── courses.php        # Course management
│   └── reports.php        # System reports
├── teacher/               # Teacher-specific pages
│   ├── students.php       # Student management
│   ├── attendance.php     # Attendance marking
│   └── grades.php         # Grade management
├── student/               # Student-specific pages
│   ├── courses.php        # Course enrollment
│   ├── attendance.php     # Attendance view
│   └── grades.php         # Grade view
└── assets/
    ├── css/
    │   └── style.css      # Main stylesheet
    └── js/
        └── validation.js  # Form validation
```

## Features by Role

### Admin Features
- **User Management**: Create, edit, delete users
- **Course Management**: Create and manage courses
- **System Reports**: View system statistics and reports
- **Full Access**: Access to all system features

### Teacher Features
- **Student Management**: View enrolled students
- **Attendance Marking**: Mark daily attendance for students
- **Grade Management**: Assign and manage student grades
- **Course Overview**: View assigned courses

### Student Features
- **Course View**: View enrolled courses
- **Attendance Tracking**: View personal attendance records
- **Grade Tracking**: View grades and performance
- **Profile Management**: Update personal information

## Security Features

- **Password Hashing**: All passwords are hashed using PHP's `password_hash()`
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Prevention**: HTML escaping for all user inputs
- **Session Security**: Secure session handling
- **Input Validation**: Both client-side and server-side validation

## Responsive Design

The application is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- All modern browsers

## Customization

### Adding New Features
1. Create new PHP files in appropriate role directories
2. Add navigation links in the sidebar
3. Update database schema if needed
4. Add corresponding CSS styles

### Styling Changes
- Main styles are in `assets/css/style.css`
- Use CSS variables for consistent theming
- Follow the existing design patterns

### Database Modifications
- Backup your database before making changes
- Update the `setup/database.sql` file for new installations
- Test thoroughly after schema changes

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database name exists

2. **Page Not Found (404)**
   - Check file permissions
   - Verify file paths are correct
   - Ensure web server is running

3. **Login Issues**
   - Verify default credentials
   - Check if database was imported correctly
   - Clear browser cache and cookies

4. **Styling Issues**
   - Check if CSS file is loading
   - Verify file paths in HTML
   - Clear browser cache

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Internet Explorer 11+

## License

This project is open source and available under the MIT License.

## Support

For support or questions:
1. Check the troubleshooting section above
2. Review the code comments for guidance
3. Ensure all prerequisites are met
4. Verify database setup is correct

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

**Note**: This is a demonstration project. For production use, consider additional security measures, error logging, and backup systems. 