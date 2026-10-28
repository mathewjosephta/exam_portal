-- Apply to an existing installation after taking a verified backup.
-- Before applying, check for mismatched legacy answers. The new constraints intentionally
-- reject any answer whose question or selected option does not belong to its exam/question.
-- Diagnostic:
-- SELECT a.attempt_id, a.question_id, a.selected_option_id
-- FROM answers a
-- JOIN attempts t ON t.id = a.attempt_id
-- JOIN questions q ON q.id = a.question_id
-- LEFT JOIN question_options o ON o.id = a.selected_option_id
-- WHERE t.exam_id <> q.exam_id OR (a.selected_option_id IS NOT NULL AND o.question_id <> q.id);

ALTER TABLE questions
    ADD UNIQUE KEY uq_questions_exam_id_id (exam_id, id);

ALTER TABLE attempts
    ADD UNIQUE KEY uq_attempts_exam_id_id (exam_id, id);

ALTER TABLE question_options
    ADD UNIQUE KEY uq_options_question_id_id (question_id, id);

ALTER TABLE answers
    ADD COLUMN exam_id BIGINT UNSIGNED NULL AFTER attempt_id;

UPDATE answers answer
JOIN attempts attempt ON attempt.id = answer.attempt_id
SET answer.exam_id = attempt.exam_id;

ALTER TABLE answers
    MODIFY exam_id BIGINT UNSIGNED NOT NULL,
    DROP FOREIGN KEY fk_answers_attempt,
    DROP FOREIGN KEY fk_answers_question,
    DROP FOREIGN KEY fk_answers_option,
    ADD CONSTRAINT fk_answers_attempt_exam
        FOREIGN KEY (exam_id, attempt_id) REFERENCES attempts(exam_id, id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_answers_question_exam
        FOREIGN KEY (exam_id, question_id) REFERENCES questions(exam_id, id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_answers_question_option
        FOREIGN KEY (question_id, selected_option_id)
        REFERENCES question_options(question_id, id) ON DELETE RESTRICT;
