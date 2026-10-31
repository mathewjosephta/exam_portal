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
                 FROM answers a
                 JOIN question_options o ON o.id = a.selected_option_id
                 JOIN questions q ON q.id = a.question_id
                 WHERE a.attempt_id = :attempt_id AND o.is_correct = 1'
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
