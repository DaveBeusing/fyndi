<?php

require_once 'src/autoload.php';

use app\config\Config;
use app\database\MySQLPDO;

$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );














header('Content-Type: application/json');
$fields = [
  "status", "title", "description", "manufacturer", "gpsr", "mpn", "ean",
  "taric", "unspc", "eclass", "weeenr", "tax", "category1", "category2",
  "category3", "category4", "category5", "weight", "width", "depth", "height",
  "volweight", "iseol", "eoldate", "minorderqty", "maxorderqty", "copyrightcharge",
  "shipping", "sku", "iscondition", "availability", "stock", "stocketa", "price"
];

// LOAD
if ($_GET['action'] === 'load' && isset($_GET['uid'])) {
  $stmt = $pdo->prepare("SELECT * FROM catalog WHERE uid = ?");
  $stmt->execute([$_GET['uid']]);
  echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
  exit;
}

// POST-ACTIONS
$data = json_decode(file_get_contents("php://input"), true);

if ($data['action'] === 'save') {
  $uid = $data['uid'];
  $entry = [];
  foreach ($fields as $f) {
    $entry[$f] = $data[$f] ?? null;
  }

  $exists = $pdo->prepare("SELECT COUNT(*) FROM catalog WHERE uid = ?");
  $exists->execute([$uid]);
  $now = date("Y-m-d H:i:s");

  if ($exists->fetchColumn()) {
    $set = implode(", ", array_map(fn($f) => "$f = ?", array_keys($entry)));
    $pdo->prepare("UPDATE catalog SET $set, updated = ? WHERE uid = ?")
        ->execute([...array_values($entry), $now, $uid]);
  } else {
    $cols = implode(", ", array_keys($entry));
    $qs = implode(", ", array_fill(0, count($entry), "?"));
    $pdo->prepare("INSERT INTO catalog (uid, $cols, created) VALUES (?, $qs, ?)")
        ->execute([$uid, ...array_values($entry), $now]);
  }
  echo json_encode(['success' => true]);
  exit;
}

if ($data['action'] === 'delete' && isset($data['uid'])) {
  $pdo->prepare("DELETE FROM catalog WHERE uid = ?")->execute([$data['uid']]);
  echo json_encode(['deleted' => true]);
  exit;
}





// Eingabewerte mit Fallbacks
$query = $_GET['query'] ?? '';
$sort = $_GET['sort'] ?? 'uid';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 10));
$offset = ($page - 1) * $limit;

// Erlaubte Spalten zum Sortieren
$allowedSorts = ['uid', 'title', 'status', 'price', 'stock'];
if (!in_array($sort, $allowedSorts)) $sort = 'uid';

// WHERE-Klausel vorbereiten
$like = '%' . $query . '%';
$whereSql = "WHERE uid LIKE :q OR title LIKE :q";

// Gesamteinträge zählen
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM catalog $whereSql");
$countStmt->execute(['q' => $like]);
$total = $countStmt->fetchColumn();

// Datensätze abfragen
$dataStmt = $pdo->prepare("
    SELECT uid, title, status, price, stock
    FROM catalog
    $whereSql
    ORDER BY $sort $dir
    LIMIT :limit OFFSET :offset
");
$dataStmt->bindValue(':q', $like);
$dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// JSON-Antwort zurückgeben
echo json_encode([
    'rows' => $rows,
    'total' => (int)$total,
]);