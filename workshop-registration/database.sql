-- Workshop Registration System
-- Run this file once to create the database and tables.

CREATE DATABASE IF NOT EXISTS workshop_db;

USE workshop_db;

DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS workshops;

CREATE TABLE workshops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    instructor VARCHAR(100) NOT NULL,
    schedule VARCHAR(100) NOT NULL,
    seats INT NOT NULL
);

CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    department VARCHAR(20) NOT NULL,
    workshop_id INT NOT NULL,
    registered_at DATETIME NOT NULL
);

INSERT INTO workshops (title, instructor, schedule, seats) VALUES
('Web Development with PHP', 'Supta Richard Philip', 'Sunday, 10:00 AM', 30),
('Database Design and MySQL', 'Dr. Anisur Rahman', 'Monday, 02:00 PM', 25),
('JavaScript and AJAX', 'Nusrat Jahan', 'Tuesday, 11:00 AM', 20),
('Cyber Security Basics', 'Tanvir Hasan', 'Wednesday, 09:00 AM', 15);
