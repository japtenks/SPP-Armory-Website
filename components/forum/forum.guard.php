<?php

if (!function_exists('spp_forum_action_url')) {
    function spp_forum_action_url($path, array $params = array(), $formName = 'forum_actions') {
        return spp_action_url($path, $params, $formName);
    }
}

function spp_forum_normalize_post_text(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', trim($value));
    return mb_strtolower($value, 'UTF-8');
}

function spp_forum_validate_submission(string $subject, string $message, bool $isNewTopic): array
{
    $errors = array();
    $messageLength = function_exists('mb_strlen') ? mb_strlen($message, 'UTF-8') : strlen($message);

    if ($isNewTopic) {
        $subjectLength = function_exists('mb_strlen') ? mb_strlen($subject, 'UTF-8') : strlen($subject);
        if ($subjectLength < 5) {
            $errors[] = 'Topic titles must be at least 5 characters long.';
        } elseif ($subjectLength > 80) {
            $errors[] = 'Topic titles cannot be longer than 80 characters.';
        }
    }

    if ($messageLength < 10) {
        $errors[] = 'Posts must be at least 10 characters long.';
    } elseif ($messageLength > 10000) {
        $errors[] = 'Posts cannot be longer than 10,000 characters.';
    }

    return $errors;
}

function spp_forum_guard_against_repeat_posts(PDO $pdo, int $accountId, int $forumId, int $topicId, string $subject, string $message, bool $isNewTopic): array
{
    $errors = array();
    if ($accountId <= 0) {
        return $errors;
    }

    $cooldownSeconds = 15;
    $duplicateWindowSeconds = 600;
    $now = time();

    try {
        $stmtLastPost = $pdo->prepare("SELECT posted FROM f_posts WHERE poster_id = ? ORDER BY posted DESC LIMIT 1");
        $stmtLastPost->execute([$accountId]);
        $lastPostTime = (int)$stmtLastPost->fetchColumn();
        if ($lastPostTime > 0 && ($now - $lastPostTime) < $cooldownSeconds) {
            $remaining = $cooldownSeconds - ($now - $lastPostTime);
            $errors[] = 'Please wait ' . $remaining . ' more second' . ($remaining === 1 ? '' : 's') . ' before posting again.';
        }

        $normalizedMessage = spp_forum_normalize_post_text($message);
        if ($normalizedMessage !== '') {
            if ($isNewTopic) {
                $stmtDuplicate = $pdo->prepare("
                    SELECT t.topic_id
                    FROM f_topics t
                    JOIN f_posts p ON p.topic_id = t.topic_id
                    WHERE t.forum_id = ?
                      AND t.topic_poster_id = ?
                      AND t.topic_name = ?
                      AND p.poster_id = ?
                      AND p.posted >= ?
                    ORDER BY p.posted DESC
                    LIMIT 1
                ");
                $stmtDuplicate->execute([
                    $forumId,
                    $accountId,
                    $subject,
                    $accountId,
                    $now - $duplicateWindowSeconds,
                ]);
                $duplicateTopicId = (int)$stmtDuplicate->fetchColumn();
                if ($duplicateTopicId > 0) {
                    $stmtFirstPost = $pdo->prepare("SELECT message FROM f_posts WHERE topic_id = ? ORDER BY posted ASC LIMIT 1");
                    $stmtFirstPost->execute([$duplicateTopicId]);
                    $existingMessage = (string)$stmtFirstPost->fetchColumn();
                    if (spp_forum_normalize_post_text($existingMessage) === $normalizedMessage) {
                        $errors[] = 'That topic looks like a duplicate of one you already posted recently.';
                    }
                }
            } else {
                $stmtDuplicate = $pdo->prepare("
                    SELECT post_id, message
                    FROM f_posts
                    WHERE topic_id = ?
                      AND poster_id = ?
                      AND posted >= ?
                    ORDER BY posted DESC
                    LIMIT 1
                ");
                $stmtDuplicate->execute([
                    $topicId,
                    $accountId,
                    $now - $duplicateWindowSeconds,
                ]);
                $duplicatePost = $stmtDuplicate->fetch(PDO::FETCH_ASSOC);
                if (!empty($duplicatePost['message']) && spp_forum_normalize_post_text((string)$duplicatePost['message']) === $normalizedMessage) {
                    $errors[] = 'That reply matches your most recent post in this topic.';
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[forum.guard] repeat-post guard failed: ' . $e->getMessage());
    }

    return $errors;
}

function spp_increment_forum_unread(PDO $pdo, int $forumId, int $excludeMemberId = 0): void
{
    $stmt = $pdo->prepare("
        UPDATE f_markread
        SET marker_unread = marker_unread + 1,
            marker_last_update = ?
        WHERE marker_forum_id = ?
          AND (? = 0 OR marker_member_id <> ?)
    ");
    $stmt->execute([
        (int)$_SERVER['REQUEST_TIME'],
        $forumId,
        $excludeMemberId,
        $excludeMemberId,
    ]);
}

function spp_enforce_topic_view_floor(PDO $pdo, int $topicId, int $minimumViews = 1): void
{
    if ($topicId <= 0) {
        return;
    }

    $stmt = $pdo->prepare("
        UPDATE f_topics
        SET num_views = GREATEST(num_views, num_replies + 1, ?)
        WHERE topic_id = ?
    ");
    $stmt->execute([(int)$minimumViews, $topicId]);
}
