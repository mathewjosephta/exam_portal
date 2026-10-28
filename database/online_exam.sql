CREATE DATABASE IF NOT EXISTS online_exam
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE online_exam;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS exams (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_exams_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_exams_availability (status, starts_at, ends_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS questions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    exam_id BIGINT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    marks SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_questions_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    UNIQUE KEY uq_questions_exam_id_id (exam_id, id),
    INDEX idx_questions_exam_position (exam_id, position)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS question_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    position TINYINT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_options_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_options_question_id_id (question_id, id),
    INDEX idx_options_question (question_id, position)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    exam_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    status ENUM('in_progress', 'submitted') NOT NULL DEFAULT 'in_progress',
    score INT UNSIGNED NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    CONSTRAINT fk_attempts_exam FOREIGN KEY (exam_id) REFERENCES exams(id),
    CONSTRAINT fk_attempts_student FOREIGN KEY (student_id) REFERENCES users(id),
    CONSTRAINT uq_attempt_exam_student UNIQUE (exam_id, student_id),
    UNIQUE KEY uq_attempts_exam_id_id (exam_id, id),
    INDEX idx_attempts_student_status (student_id, status),
    INDEX idx_attempts_exam_status (exam_id, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS answers (
    exam_id BIGINT UNSIGNED NOT NULL,
    attempt_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    selected_option_id BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (attempt_id, question_id),
    CONSTRAINT fk_answers_attempt_exam FOREIGN KEY (exam_id, attempt_id)
        REFERENCES attempts(exam_id, id) ON DELETE CASCADE,
    CONSTRAINT fk_answers_question_exam FOREIGN KEY (exam_id, question_id)
        REFERENCES questions(exam_id, id) ON DELETE CASCADE,
    CONSTRAINT fk_answers_question_option FOREIGN KEY (question_id, selected_option_id)
        REFERENCES question_options(question_id, id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auth_rate_limits (
    key_hash CHAR(64) NOT NULL PRIMARY KEY,
    window_started_at DATETIME NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_auth_rate_limits_window (window_started_at)
) ENGINE=InnoDB;
