<?php
declare(strict_types=1);

// ============================================================
// migrate_recruitment_seed_bbcode.php
// ============================================================
// Converts old forum content from escaped HTML-ish text into the raw
// plain-text / BBCode format expected by the updated forum renderer.
//
// Intended for legacy topics/posts that currently look like:
//   &lt;Guild Name&gt; is Recruiting!
//   <b>Name</b> text
//
// And converts them toward:
//   <Guild Name> is Recruiting!
//   [b]Name[/b] text
//
// Usage:
//   php tools/migrate_recruitment_seed_bbcode.php --dry-run
//   php tools/migrate_recruitment_seed_bbcode.php --realm=1
//   php tools/migrate_recruitment_seed_bbcode.php --realm=1 --forum=5
//
// Options:
//   --dry-run     Preview counts without writing changes.
//   --realm=ID    Realm ID to process. Defaults to 1.
//   --forum=ID    Optional: limit processing to one forum.
//
// Safe to re-run:
// - Only updates rows whose migrated value differs from the current value.
// ============================================================

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$siteRoot = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $siteRoot;
require_once($siteRoot . '/config/config-protected.php');

$dryRun = in_array('--dry-run', $argv, true);
$realmId = 1;
$forumId = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--realm=') === 0) {
        $realmId = max(1, (int)substr($arg, 8));
    } elseif (strpos($arg, '--forum=') === 0) {
        $forumId = max(1, (int)substr($arg, 8));
    }
}

function log_line(string $msg): void
{
    echo '[' . date('H:i:s') . '] ' . $msg . "\n";
}

function migrate_seed_markup(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

    // Normalize old stored HTML line breaks back to plain text.
    $text = preg_replace('#<br\s*/?>#i', "\n", $text);

    // Convert simple HTML emphasis tags to BBCode.
    $text = preg_replace('#<\s*(b|strong)\s*>#i', '[b]', $text);
    $text = preg_replace('#<\s*/\s*(b|strong)\s*>#i', '[/b]', $text);
    $text = preg_replace('#<\s*(i|em)\s*>#i', '[i]', $text);
    $text = preg_replace('#<\s*/\s*(i|em)\s*>#i', '[/i]', $text);
    $text = preg_replace('#<\s*u\s*>#i', '[u]', $text);
    $text = preg_replace('#<\s*/\s*u\s*>#i', '[/u]', $text);
    $text = preg_replace('#<\s*s\s*>#i', '[s]', $text);
    $text = preg_replace('#<\s*/\s*s\s*>#i', '[/s]', $text);

    // Remove any wrapping paragraph tags left from old formatting.
    $text = preg_replace('#</p>\s*<p>#i', "\n\n", $text);
    $text = preg_replace('#<\s*/?\s*p\s*>#i', '', $text);

    // Clean up repeated whitespace without destroying paragraph breaks.
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);

    return trim($text);
}

try {
    $pdo = spp_get_pdo('realmd', $realmId);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    fwrite(STDERR, "Could not connect to realmd for realm {$realmId}: {$e->getMessage()}\n");
    exit(1);
}

log_line(
    "Realm {$realmId}" .
    ($forumId !== null ? ", forum {$forumId}" : ", all forums") .
    ($dryRun ? " (dry-run)" : '')
);

$topicUpdates = [];
$postUpdates = [];

// Migrate topic titles in the target forum or the whole realm.
if ($forumId !== null) {
    $stmtTopics = $pdo->prepare("
        SELECT topic_id, topic_name
        FROM `f_topics`
        WHERE forum_id = ?
        ORDER BY topic_id ASC
    ");
    $stmtTopics->execute([$forumId]);
} else {
    $stmtTopics = $pdo->query("
        SELECT topic_id, topic_name
        FROM `f_topics`
        ORDER BY topic_id ASC
    ");
}

foreach ($stmtTopics->fetchAll(PDO::FETCH_ASSOC) as $topic) {
    $original = (string)($topic['topic_name'] ?? '');
    $migrated = migrate_seed_markup($original);
    if ($migrated !== $original) {
        $topicUpdates[] = [
            'topic_id' => (int)$topic['topic_id'],
            'old' => $original,
            'new' => $migrated,
        ];
    }
}

// Migrate post bodies in the target forum or the whole realm.
if ($forumId !== null) {
    $stmtPosts = $pdo->prepare("
        SELECT p.post_id, p.message
        FROM `f_posts` p
        INNER JOIN `f_topics` t ON t.topic_id = p.topic_id
        WHERE t.forum_id = ?
        ORDER BY p.post_id ASC
    ");
    $stmtPosts->execute([$forumId]);
} else {
    $stmtPosts = $pdo->query("
        SELECT post_id, message
        FROM `f_posts`
        ORDER BY post_id ASC
    ");
}

foreach ($stmtPosts->fetchAll(PDO::FETCH_ASSOC) as $post) {
    $original = (string)($post['message'] ?? '');
    $migrated = migrate_seed_markup($original);
    if ($migrated !== $original) {
        $postUpdates[] = [
            'post_id' => (int)$post['post_id'],
            'old' => $original,
            'new' => $migrated,
        ];
    }
}

log_line('Topic titles to update: ' . count($topicUpdates));
log_line('Post bodies to update : ' . count($postUpdates));

if (!empty($topicUpdates)) {
    $sample = $topicUpdates[0];
    log_line('Sample topic title:');
    log_line('  OLD: ' . $sample['old']);
    log_line('  NEW: ' . $sample['new']);
}

if (!empty($postUpdates)) {
    $sample = $postUpdates[0];
    log_line('Sample post body:');
    log_line('  OLD: ' . preg_replace('/\s+/', ' ', $sample['old']));
    log_line('  NEW: ' . preg_replace('/\s+/', ' ', $sample['new']));
}

if ($dryRun) {
    log_line('Dry run complete. No changes written.');
    exit(0);
}

$pdo->beginTransaction();

try {
    if (!empty($topicUpdates)) {
        $updTopic = $pdo->prepare("UPDATE `f_topics` SET topic_name = ? WHERE topic_id = ? LIMIT 1");
        foreach ($topicUpdates as $row) {
            $updTopic->execute([$row['new'], $row['topic_id']]);
        }
    }

    if (!empty($postUpdates)) {
        $updPost = $pdo->prepare("UPDATE `f_posts` SET message = ? WHERE post_id = ? LIMIT 1");
        foreach ($postUpdates as $row) {
            $updPost->execute([$row['new'], $row['post_id']]);
        }
    }

    $pdo->commit();
    log_line('Migration complete.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Migration failed: {$e->getMessage()}\n");
    exit(1);
}
