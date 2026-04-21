-- WARNING: The following statement drops and recreates the entire database.
-- This is intended ONLY for initial local development setup.
-- Never run this file against a production or shared database.
DROP DATABASE IF EXISTS uniconnect;

-- Create Database
CREATE DATABASE IF NOT EXISTS uniconnect;
USE uniconnect;

-- 1. users table 
CREATE TABLE IF NOT EXISTS users (
    student_id VARCHAR(50) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(50) NOT NULL,
    coin_balance INT DEFAULT 5,
    reputation_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    trimester VARCHAR(20) DEFAULT NULL,
    profile_picture VARCHAR(255),
    bio TEXT DEFAULT NULL,
    profile_completion TINYINT DEFAULT 0,
    reward_claimed BOOLEAN DEFAULT FALSE,
    status VARCHAR(255) DEFAULT 'Offline now'
);

-- 2. skills table (for user's declared skills)
CREATE TABLE IF NOT EXISTS skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    skill_name VARCHAR(200) NOT NULL,
    CONSTRAINT fk_skills_user_id FOREIGN KEY (user_id) REFERENCES users (student_id) ON DELETE CASCADE,
    UNIQUE (user_id, skill_name) -- Prevents duplicate skills for a user
);

-- 3. user_courses_profile table (for courses a user adds to their profile/routine)
CREATE TABLE IF NOT EXISTS user_courses_profile (
    user_course_profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    section VARCHAR(10) NOT NULL,
    CONSTRAINT fk_user_courses_profile_user_id FOREIGN KEY (user_id) REFERENCES users (student_id) ON DELETE CASCADE,
    UNIQUE (user_id, course_name, section) -- Prevents duplicate course-sections for a user
);

-- 4. available_courses table (master list of courses for selection in profile)
CREATE TABLE IF NOT EXISTS available_courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(200) NOT NULL UNIQUE,
    course_code VARCHAR(50) UNIQUE
);
-- Populate with initial data (INSERT IGNORE prevents duplicates on re-run)
INSERT IGNORE INTO available_courses (course_name, course_code) VALUES
('Intensive English I', 'ENGI101'),
('Intensive English II', 'ENGI102'),
('Society, Environment and Engineering Ethics', 'SEEE101'),
('Project Management', 'PM101'),
('Physics', 'PHY101'),
('Physics Laboratory', 'PHY101L'),
('Biology for Engineers', 'BEE101'),
('Fundamental Calculus', 'FCA101'),
('Calculus and Linear Algebra', 'CLA101'),
('Coordinate Geometry and Vector Analysis', 'CGVA101'),
('Probability and Statistics', 'PS101'),
('Electrical Circuits', 'EC101'),
('Electronics', 'ELT101'),
('Electronics Laboratory', 'ELT101L'),
('Green Computing', 'GC101'),
('Introduction to Computer Systems', 'ICS101'),
('Structured Programming Language', 'SPL101'),
('Structured Programming Language Laboratory', 'SPL101L'),
('Object Oriented Programming', 'OOP101'),
('Object Oriented Programming Laboratory', 'OOP101L'),
('Advanced Object Oriented Programming Lab', 'AOOP101L'),
('Web Programming', 'WEB101'),
('Mobile Application Development', 'MAD101'),
('Digital Logic Design', 'DLD101'),
('Digital Logic Design Laboratory', 'DLD101L'),
('Computer Architecture', 'CA101'),
('Microprocessors and Microcontrollers', 'MMC101'),
('Microprocessors and Microcontrollers Laboratory', 'MMC101L'),
('Discrete Mathematics', 'DM101'),
('Data Structure and Algorithms I', 'DSAI101'),
('Data Structure and Algorithms I Laboratory', 'DSAI101L'),
('Artificial Intelligence', 'AI101'),
('Artificial Intelligence Laboratory', 'AI101L'),
('Database Management Systems', 'DBMS101'),
('Database Management Systems Laboratory', 'DBMS101L');


-- 5. course table (master list for course definitions, referenced by routine table)
CREATE TABLE IF NOT EXISTS course (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(50) UNIQUE,
    course_name VARCHAR(200) NOT NULL,
    credit INT NOT NULL
);
-- Populate the 'course' table with the updated list of courses
INSERT IGNORE INTO course (course_name, course_code, credit) VALUES
('Intensive English I', 'ENGI101', 3),
('Intensive English II', 'ENGI102', 3),
('Society, Environment and Engineering Ethics', 'SEEE101', 3),
('Project Management', 'PM101', 3),
('Physics', 'PHY101', 3),
('Physics Laboratory', 'PHY101L', 1),
('Biology for Engineers', 'BEE101', 3),
('Fundamental Calculus', 'FCA101', 3),
('Calculus and Linear Algebra', 'CLA101', 3),
('Coordinate Geometry and Vector Analysis', 'CGVA101', 3),
('Probability and Statistics', 'PS101', 3),
('Electrical Circuits', 'EC101', 3),
('Electronics', 'ELT101', 3),
('Electronics Laboratory', 'ELT101L', 1),
('Green Computing', 'GC101', 3),
('Introduction to Computer Systems', 'ICS101', 3),
('Structured Programming Language', 'SPL101', 3),
('Structured Programming Language Laboratory', 'SPL101L', 1),
('Object Oriented Programming', 'OOP101', 3),
('Object Oriented Programming Laboratory', 'OOP101L', 1),
('Advanced Object Oriented Programming Lab', 'AOOP101L', 1),
('Web Programming', 'WEB101', 3),
('Mobile Application Development', 'MAD101', 3),
('Digital Logic Design', 'DLD101', 3),
('Digital Logic Design Laboratory', 'DLD101L', 1),
('Computer Architecture', 'CA101', 3),
('Microprocessors and Microcontrollers', 'MMC101', 3),
('Microprocessors and Microcontrollers Laboratory', 'MMC101L', 1),
('Discrete Mathematics', 'DM101', 3),
('Data Structure and Algorithms I', 'DSAI101', 3),
('Data Structure and Algorithms I Laboratory', 'DSAI101L', 1),
('Artificial Intelligence', 'AI101', 3),
('Artificial Intelligence Laboratory', 'AI101L', 1),
('Database Management Systems', 'DBMS101', 3),
('Database Management Systems Laboratory', 'DBMS101L', 1);


-- 6. routine table (REVISED: no user_id column, room_number is VARCHAR)
CREATE TABLE IF NOT EXISTS routine (
    routine_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,       -- References course(course_id)
    section VARCHAR(10) NOT NULL,
    day_of_week ENUM('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    is_exam BOOLEAN DEFAULT FALSE,
    CONSTRAINT fk_routine_course_id FOREIGN KEY (course_id) REFERENCES course (course_id) ON DELETE CASCADE
);


-- 7. posts table (REVISED: Now includes 'lost-found' category)
CREATE TABLE IF NOT EXISTS posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL, -- References users(student_id)
    post_text TEXT NOT NULL,
    post_image_url VARCHAR(255) NULL, -- Optional image
    category ENUM('general', 'academic', 'buy-sell', 'lost-found') NOT NULL DEFAULT 'general', -- ADDED 'lost-found'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_posts_user_id FOREIGN KEY (user_id) REFERENCES users (student_id) ON DELETE CASCADE
);



-- Add a new table for post likes
CREATE TABLE IF NOT EXISTS post_likes (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,               -- Foreign Key to posts.post_id
    user_id VARCHAR(50) NOT NULL,       -- Foreign Key to users.student_id (who liked it)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_post_likes_post_id FOREIGN KEY (post_id) REFERENCES posts (post_id) ON DELETE CASCADE,
    CONSTRAINT fk_post_likes_user_id FOREIGN KEY (user_id) REFERENCES users (student_id) ON DELETE CASCADE,
    UNIQUE (post_id, user_id) -- Ensures a user can only like a specific post once
);

-- 8. comments table (Now references 'posts' table directly)
CREATE TABLE IF NOT EXISTS comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL, -- Now references posts(post_id)
    user_id VARCHAR(50) NOT NULL, -- References users(student_id)
    comment_text TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_post_id FOREIGN KEY (post_id) REFERENCES posts (post_id) ON DELETE CASCADE, -- NEW FK
    CONSTRAINT fk_comments_user_id FOREIGN KEY (user_id) REFERENCES users (student_id) ON DELETE CASCADE
);

-- 14. Messages table (for chat system)
CREATE TABLE IF NOT EXISTS messages (
  msg_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  incoming_msg_id VARCHAR(50) NOT NULL, -- References users(student_id)
  outgoing_msg_id VARCHAR(50) NOT NULL, -- References users(student_id)
  msg VARCHAR(1000) NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP -- Added timestamp for chat
);

ALTER TABLE messages
ADD CONSTRAINT fk_messages_incoming_user FOREIGN KEY (incoming_msg_id) REFERENCES users(student_id) ON DELETE CASCADE,
ADD CONSTRAINT fk_messages_outgoing_user FOREIGN KEY (outgoing_msg_id) REFERENCES users(student_id) ON DELETE CASCADE;


-- 16. New: notes table (for user-posted notes)
CREATE TABLE IF NOT EXISTS notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL, -- The student who posted the note
    title VARCHAR(255) NOT NULL,
    description TEXT,
    subject VARCHAR(100) NOT NULL, -- e.g., "DBMS", "AI"
    exam_type ENUM('midterm', 'final', 'quiz', 'assignment', 'other') NOT NULL, -- e.g., "mid", "final", "quiz", "assignment", "other"
    file_path VARCHAR(255) NOT NULL, -- Path to the uploaded PDF/Doc file
    thumbnail_path VARCHAR(255) NULL, -- Optional thumbnail for preview
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notes_user_id FOREIGN KEY (user_id) REFERENCES users (student_id) ON DELETE CASCADE
);

-- 17. New: note_reviews table (for user reviews on notes)
CREATE TABLE IF NOT EXISTS note_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    note_id INT NOT NULL,               -- References notes(note_id)
    reviewer_id VARCHAR(50) NOT NULL,   -- The student who reviewed the note
    rating_category ENUM('very_helpful', 'average', 'not_helpful') NOT NULL,
    review_text TEXT NULL,              -- Optional text review
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_note_reviews_note_id FOREIGN KEY (note_id) REFERENCES notes (note_id) ON DELETE CASCADE,
    CONSTRAINT fk_note_reviews_reviewer_id FOREIGN KEY (reviewer_id) REFERENCES users (student_id) ON DELETE CASCADE,
    UNIQUE (note_id, reviewer_id) -- A user can review a specific note only once
);


-- 18. New: notifications table (for real-time notifications)
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL, -- The user who receives the notification
    sender_id VARCHAR(50) NULL,   -- The user who triggered the notification (e.g., liked, commented), null if system/anonymous
    type ENUM(
        'like_post',
        'comment_post',
        'reply_comment', -- Although replies are also comments, differentiating type can help with message/link
        'new_pm',        -- New private message
        'new_note_upload',
        'note_reviewed',
        'coins_gained',
        'coins_lost',
        'reputation_gained',
        'class_reminder',
        'system_announcement'
    ) NOT NULL,
    message TEXT NOT NULL,         -- The display message for the notification
    link VARCHAR(255) NULL,        -- URL to navigate to when notification is clicked
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user_id FOREIGN KEY (user_id) REFERENCES users (student_id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_sender_id FOREIGN KEY (sender_id) REFERENCES users (student_id) ON DELETE SET NULL -- SET NULL if sender account is deleted
);