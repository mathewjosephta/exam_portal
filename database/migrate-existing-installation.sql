-- Run once when upgrading the earlier question_options-based online_exam schema.
-- This migration only adds compatible columns and retains legacy data.

ALTER TABLE users
    ADD COLUMN status ENUM('active', 'disabled') NOT NULL DEFAULT 'active';

ALTER TABLE exams
    MODIFY COLUMN status ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'draft',
    ADD COLUMN total_marks INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN start_time DATETIME NULL,
    ADD COLUMN end_time DATETIME NULL;

UPDATE exams
SET start_time = starts_at,
    end_time = ends_at;

ALTER TABLE questions
    ADD COLUMN option_a VARCHAR(500) NOT NULL DEFAULT '',
    ADD COLUMN option_b VARCHAR(500) NOT NULL DEFAULT '',
    ADD COLUMN option_c VARCHAR(500) NOT NULL DEFAULT '',
    ADD COLUMN option_d VARCHAR(500) NOT NULL DEFAULT '',
    ADD COLUMN correct_option ENUM('A', 'B', 'C', 'D') NOT NULL DEFAULT 'A';

UPDATE questions q
JOIN (
    SELECT
        question_id,
        MAX(CASE WHEN position = 1 THEN option_text END) AS option_a,
        MAX(CASE WHEN position = 2 THEN option_text END) AS option_b,
        MAX(CASE WHEN position = 3 THEN option_text END) AS option_c,
        MAX(CASE WHEN position = 4 THEN option_text END) AS option_d,
        MAX(CASE
            WHEN is_correct = 1 AND position = 1 THEN 'A'
            WHEN is_correct = 1 AND position = 2 THEN 'B'
            WHEN is_correct = 1 AND position = 3 THEN 'C'
            WHEN is_correct = 1 AND position = 4 THEN 'D'
        END) AS correct_option
    FROM question_options
    GROUP BY question_id
) options ON options.question_id = q.id
SET q.option_a = COALESCE(options.option_a, q.option_a),
    q.option_b = COALESCE(options.option_b, q.option_b),
    q.option_c = COALESCE(options.option_c, q.option_c),
    q.option_d = COALESCE(options.option_d, q.option_d),
    q.correct_option = COALESCE(options.correct_option, q.correct_option);

UPDATE exams e
SET total_marks = (
    SELECT COALESCE(SUM(q.marks), 0)
    FROM questions q
    WHERE q.exam_id = e.id
);

ALTER TABLE answers
    ADD COLUMN selected_option ENUM('A', 'B', 'C', 'D') NULL,
    ADD COLUMN is_correct TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN marks_obtained INT UNSIGNED NOT NULL DEFAULT 0;

UPDATE answers a
JOIN question_options selected ON selected.id = a.selected_option_id
JOIN questions q ON q.id = a.question_id
JOIN question_options correct
    ON correct.question_id = q.id AND correct.is_correct = 1
SET a.selected_option = CASE selected.position
        WHEN 1 THEN 'A'
        WHEN 2 THEN 'B'
        WHEN 3 THEN 'C'
        WHEN 4 THEN 'D'
        ELSE NULL
    END,
    a.is_correct = (selected.id = correct.id),
    a.marks_obtained = CASE WHEN selected.id = correct.id THEN q.marks ELSE 0 END;
