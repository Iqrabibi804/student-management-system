-- ============================================================
-- Smart Student Management System - Database Setup
-- Compatible with: MySQL 5.7+ / MariaDB 10.3+
-- Usage: Import via phpMyAdmin or run: mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS student_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE student_management;

-- --------------------------------------------------------
-- Table: students
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id          INT(11)      NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    course      VARCHAR(100) NOT NULL,
    status      ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email   (email),
    INDEX idx_course  (course),
    INDEX idx_status  (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: admin_users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id           INT(11)      NOT NULL AUTO_INCREMENT,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    full_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL,
    last_login   TIMESTAMP    NULL DEFAULT NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_log
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
    id           INT(11)      NOT NULL AUTO_INCREMENT,
    admin_id     INT(11)      NULL,
    action       VARCHAR(50)  NOT NULL,
    description  TEXT         NOT NULL,
    student_id   INT(11)      NULL,
    ip_address   VARCHAR(45)  NOT NULL DEFAULT '',
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_admin    (admin_id),
    INDEX idx_student  (student_id),
    INDEX idx_created  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Default admin user
-- Password: admin123  (bcrypt hashed)
-- CHANGE THIS PASSWORD IMMEDIATELY IN PRODUCTION!
-- --------------------------------------------------------
INSERT INTO admin_users (username, password, full_name, email) VALUES (
    'admin',
    '$2y$12$dGQeWWD13oqfKZfC5.jLbudEMxS.kXuWciVSS8lIvkJNV41w7/YSS',
    'System Administrator',
    'admin@university.edu'
);

-- --------------------------------------------------------
-- Sample student data
-- --------------------------------------------------------
INSERT INTO students (name, email, course, status, created_at) VALUES
('Alice Johnson',   'alice.johnson@uni.edu',    'Computer Science',       'Active',   NOW() - INTERVAL 45 DAY),
('Bob Martinez',    'bob.martinez@uni.edu',     'Software Engineering',   'Active',   NOW() - INTERVAL 40 DAY),
('Carol White',     'carol.white@uni.edu',      'Data Science',           'Active',   NOW() - INTERVAL 35 DAY),
('David Lee',       'david.lee@uni.edu',        'Computer Science',       'Inactive', NOW() - INTERVAL 30 DAY),
('Emma Davis',      'emma.davis@uni.edu',       'Cybersecurity',          'Active',   NOW() - INTERVAL 25 DAY),
('Frank Wilson',    'frank.wilson@uni.edu',     'Software Engineering',   'Active',   NOW() - INTERVAL 20 DAY),
('Grace Kim',       'grace.kim@uni.edu',        'Data Science',           'Active',   NOW() - INTERVAL 15 DAY),
('Henry Brown',     'henry.brown@uni.edu',      'Computer Science',       'Active',   NOW() - INTERVAL 12 DAY),
('Isabella Clark',  'isabella.clark@uni.edu',   'Cybersecurity',          'Inactive', NOW() - INTERVAL 10 DAY),
('James Taylor',    'james.taylor@uni.edu',     'Software Engineering',   'Active',   NOW() - INTERVAL 8 DAY),
('Karen Moore',     'karen.moore@uni.edu',      'Computer Science',       'Active',   NOW() - INTERVAL 6 DAY),
('Liam Anderson',   'liam.anderson@uni.edu',    'Data Science',           'Active',   NOW() - INTERVAL 4 DAY),
('Mia Thomas',      'mia.thomas@uni.edu',       'Cybersecurity',          'Active',   NOW() - INTERVAL 3 DAY),
('Noah Jackson',    'noah.jackson@uni.edu',     'Software Engineering',   'Active',   NOW() - INTERVAL 2 DAY),
('Olivia Harris',   'olivia.harris@uni.edu',    'Computer Science',       'Active',   NOW() - INTERVAL 1 DAY);
