-- Pharmacy Internship Management System
-- Create the database and tables for the prototype.

CREATE DATABASE IF NOT EXISTS pharmacy_internship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_internship;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) DEFAULT NULL,
    role ENUM('Intern','HR Personnel','Pharmacist') NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX(role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS internship_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(150) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS intern_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    requirement_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    remarks TEXT DEFAULT NULL,
    uploaded_at DATETIME NOT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (requirement_id) REFERENCES internship_requirements(id) ON DELETE CASCADE,
    UNIQUE KEY user_requirement_unique (user_id, requirement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO internship_requirements (title, description, created_at) VALUES
('Proof of Enrollment', 'Upload a valid school enrollment letter or student ID.', NOW()),
('Birth Certificate', 'Upload a copy of your birth certificate.', NOW()),
('Pre-Internship Requirements Clearance', 'Upload the pre-internship requirements clearance form.', NOW()),
('Medical Certificate', 'Upload a valid medical certificate confirming fitness for internship.', NOW()),
('Notarized Parental/Guardian Consent Form', 'Upload the notarized parental or guardian consent form.', NOW());

INSERT IGNORE INTO policies (category, title, content, created_at) VALUES
('General Pharmacy Operations', 'Workplace Conduct', 'All interns must follow pharmacy policies, maintain professionalism, and ask questions when needed.', NOW()),
('Patient Safety and Medication Use', 'Medication Safety', 'Follow proper handling, labeling, and storage protocols for medications and inventory.', NOW()),
('Pharmacist and Staff Responsibilities', 'Attendance & Punctuality', 'Arrive on time, notify HR for absences, and complete assigned daily tasks.', NOW());
