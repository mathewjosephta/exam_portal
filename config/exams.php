<?php
declare(strict_types=1);

function exam_is_open(array $exam): bool
{
    $now = new DateTimeImmutable();
    if ($exam['status'] !== 'published') {
        return false;
    }
    if ($exam['starts_at'] !== null && new DateTimeImmutable($exam['starts_at']) > $now) {
        return false;
    }
    return $exam['ends_at'] === null || new DateTimeImmutable($exam['ends_at']) >= $now;
}

function submit_attempt(PDO $pdo, int $attemptId, int $studentId): void
{
    $pdo->beginTransaction();
    try {
        $attemptQuery = $pdo->prepare(
            'SELECT id, status FROM attempts WHERE id = :id AND student_id = :student_id FOR UPDATE'
        );
        $attemptQuery->execute(['id' => $attemptId, 'student_id' => $studentId]);
        $attempt = $attemptQuery->fetch();
        if (!$attempt) {
            throw new RuntimeException('Exam attempt not found.');
        }
        if ($attempt['status'] === 'in_progress') {
            $scoreQuery = $pdo->prepare(
                'SELECT COALESCE(SUM(q.marks), 0)
                 FROM attempts attempt
                 JOIN answers answer ON answer.attempt_id = attempt.id
                 JOIN questions q ON q.id = answer.question_id AND q.exam_id = attempt.exam_id
                 JOIN question_options option_row
                   ON option_row.id = answer.selected_option_id
                  AND option_row.question_id = q.id
                  AND option_row.is_correct = 1
                 WHERE attempt.id = :attempt_id'
            );
            $scoreQuery->execute(['attempt_id' => $attemptId]);
            $score = (int) $scoreQuery->fetchColumn();

            $update = $pdo->prepare(
                "UPDATE attempts
                 SET status = 'submitted', score = :score, submitted_at = CURRENT_TIMESTAMP
                 WHERE id = :id"
            );
            $update->execute(['score' => $score, 'id' => $attemptId]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function format_datetime(?string $value): string
{
    return $value === null ? 'No limit' : date('M j, Y g:i A', strtotime($value));
}
