<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

header('Content-Type: application/json');

$sinceFragment = isset($_GET['since_fragment']) ? (int) $_GET['since_fragment'] : 0;
$sinceHaiku = isset($_GET['since_haiku']) ? (int) $_GET['since_haiku'] : 0;
$limit = 40;

$pdo = overheard_db();

$fragmentRows = overheard_fetch_since($pdo, 'fragments', 'text', $sinceFragment, $limit);
$haikuRows = overheard_fetch_since($pdo, 'haiku', 'line1, line2, line3, source_uris', $sinceHaiku, $limit);

$events = [];
foreach ($fragmentRows as $row) {
    $events[] = overheard_to_event('fragments', $row);
}
foreach ($haikuRows as $row) {
    $events[] = overheard_to_event('haiku', $row);
}
usort($events, fn($a, $b) => strcmp($a['captured_at'], $b['captured_at']));

echo json_encode([
    'events' => $events,
    'since_fragment' => overheard_max_id($fragmentRows, $sinceFragment),
    'since_haiku' => overheard_max_id($haikuRows, $sinceHaiku),
    'total_fragments' => (int) $pdo->query('SELECT COUNT(*) FROM fragments')->fetchColumn(),
    'total_haiku' => (int) $pdo->query('SELECT COUNT(*) FROM haiku')->fetchColumn(),
]);
