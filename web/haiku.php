<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

header('Content-Type: application/json');

// Pages backward through the haiku archive, newest-first — used by the
// "found haiku only" view's "load earlier haiku" control. Live new haiku
// don't need a separate endpoint: they already arrive via api.php's
// normal combined poll, and the front end's CSS filter just hides
// non-haiku entries rather than stopping haiku from appearing.

$pdo = overheard_db();
$limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 20;
$before = isset($_GET['before']) ? (int) $_GET['before'] : 0;

$sql = $before > 0
    ? 'SELECT id, line1, line2, line3, source_uris, captured_at FROM haiku WHERE id < :before ORDER BY id DESC LIMIT :lim'
    : 'SELECT id, line1, line2, line3, source_uris, captured_at FROM haiku ORDER BY id DESC LIMIT :lim';
$stmt = $pdo->prepare($sql);
if ($before > 0) {
    $stmt->bindValue(':before', $before, PDO::PARAM_INT);
}
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

echo json_encode([
    'events' => array_map(fn($row) => overheard_to_event('haiku', $row), $rows),
    'next_before' => count($rows) === $limit ? (int) end($rows)['id'] : null,
]);
