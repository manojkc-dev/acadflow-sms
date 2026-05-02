-- Create database
CREATE DATABASE IF NOT EXISTS acadflow;
USE acadflow;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    description TEXT,
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Student enrollments
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
);

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,
    marked_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, course_id, date)
);

-- Grades table
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    assignment_name VARCHAR(100) NOT NULL,
    grade DECIMAL(5,2) NOT NULL,
    max_grade DECIMAL(5,2) NOT NULL DEFAULT 100,
    comments TEXT,
    assigned_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (first_name, last_name, email, password, role) VALUES 
('Admin', 'User', 'admin@acadflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample teachers
INSERT INTO users (first_name, last_name, email, password, role) VALUES 
('Krishna', 'Professor', 'krishna.professor@acadflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher'),
('Radha', 'Instructor', 'radha.instructor@acadflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher');

-- Insert sample students
INSERT INTO users (first_name, last_name, email, password, role) VALUES 
('Ram', 'Student', 'ram.student@acadflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Laxman', 'Wilson', 'laxman.student@acadflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Sita', 'Student', 'sita.student@acadflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Insert sample courses
INSERT INTO courses (course_code, course_name, description, teacher_id) VALUES 
('BIT101', 'Python Programming', 'Basic Python programming concepts and problem solving', 2),
('BIT102', 'React.js Fundamentals', 'Introduction to React.js and component-based development', 3),
('BIT103', 'PostgreSQL', 'Introduction to PostgreSQL and database management', 2),
('BIT104', 'PHP', 'Introduction to PHP and web development', 3),
('BIT105', 'HTML & CSS', 'Introduction to HTML and CSS for web design', 2),
('BIT106', 'MySQL', 'Introduction to MySQL and database management', 3),
('BIT107', 'JavaScript', 'Introduction to JavaScript programming and web development', 2),
('BIT108', 'Data Structures', 'Introduction to data structures and algorithms', 3);

-- Enroll students in courses
INSERT INTO enrollments (student_id, course_id) VALUES 
(4, 1), (4, 2), (5, 1), (5, 3), (6, 2), (6, 3);

-- Insert sample attendance records
INSERT INTO attendance (student_id, course_id, date, status, marked_by) VALUES 
(4, 1, CURDATE(), 'present', 2),
(5, 1, CURDATE(), 'present', 2),
(4, 2, CURDATE(), 'late', 3),
(6, 2, CURDATE(), 'absent', 3);

-- Insert sample grades
INSERT INTO grades (student_id, course_id, assignment_name, grade, max_grade, assigned_by) VALUES 
(4, 1, 'Midterm Exam', 85.5, 100, 2),
(5, 1, 'Midterm Exam', 92.0, 100, 2),
(4, 2, 'Essay Assignment', 88.0, 100, 3),
(6, 2, 'Essay Assignment', 95.5, 100, 3); 