<?php
declare(strict_types=1);

function overheard_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $config = require __DIR__ . '/config.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['db_host'],
            $config['db_port'],
            $config['db_name']
        );
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/** Fetch up to $limit fragment/haiku rows with id > $sinceId from one table. */
function overheard_fetch_since(PDO $pdo, string $table, string $columns, int $sinceId, int $limit): array
{
    $stmt = $pdo->prepare("SELECT id, {$columns}, captured_at FROM {$table} WHERE id > :since ORDER BY id ASC LIMIT :lim");
    $stmt->bindValue(':since', $sinceId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Normalizes a fragments/haiku row into the shape the front end renders. */
function overheard_to_event(string $table, array $row): array
{
    if ($table === 'haiku') {
        return [
            'kind' => 'haiku',
            'id' => (int) $row['id'],
            'lines' => [$row['line1'], $row['line2'], $row['line3']],
            'sources' => json_decode($row['source_uris'], true) ?: [],
            'captured_at' => $row['captured_at'],
        ];
    }
    return [
        'kind' => 'fragment',
        'id' => (int) $row['id'],
        'text' => $row['text'],
        'captured_at' => $row['captured_at'],
    ];
}

function overheard_max_id(array $rows, int $floor): int
{
    $max = $floor;
    foreach ($rows as $row) {
        $max = max($max, (int) $row['id']);
    }
    return $max;
}
