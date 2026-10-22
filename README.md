# StudentHub Portal

## Overview
StudentHub is a responsive college portal that provides students with access to college information, events, registration, and dashboard features. It includes PHP-based authentication, role-based access control, and administrative tools.

## Features
- Home, About, Contact, FAQ, Feedback, and Profile pages
- Student registration and login
- PHP session management and logout
- Role-based access for students and administrators
- Student dashboard and admin dashboard
- Student management (CRUD operations)
- Event management with poster uploads
- Events API with asynchronous Fetch/AJAX functionality
- Database integration using MySQL
- Responsive frontend using HTML, CSS, and JavaScript

## Technologies
- HTML5 and CSS3
- JavaScript and Fetch API
- PHP
- MySQL
- Apache via XAMPP
- Git and GitHub

## Local Setup Using XAMPP

1. Install XAMPP with Apache, PHP, and MySQL.
2. Copy or clone this project into the XAMPP `htdocs` directory.
3. Start Apache and MySQL using the XAMPP Control Panel.
4. Open phpMyAdmin and create a database named `studenthub`.
5. Import the project's database schema or create the required tables for students, events, and registrations.
6. Configure `db.php` with your local MySQL settings.
7. Open `http://localhost/StudentHub/` in your browser.

The application requires its database tables and any necessary folders to be configured before all modules can work. Do not commit private student data, uploaded files, or production credentials.

## Security
- Prepared SQL statements for database queries
- Password verification using PHP `password_verify()`
- Session-based authentication
- Role-based authorization
- Session timeout and logout
- Input validation and CSRF protection where implemented
- Restrictions on event poster uploads

## Testing Checklist
- Verify valid and invalid login attempts
- Verify student and admin role-based access
- Verify logout and protected page access
- Verify student and event CRUD operations
- Verify event API requests and responses
- Verify responsive layouts on desktop and mobile
- Verify database connection and session timeout

## Repository
GitHub: https://github.com/Cynthia758/StudentsHub

## Author
Vaidehi Parmar
