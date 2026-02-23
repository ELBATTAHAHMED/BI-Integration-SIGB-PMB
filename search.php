<?php
// Connexion a la base PMB
$host = 'localhost';
$dbname = 'pmb';
$user = 'root';
$pass = 'root123';

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur de connexion a la base de donnees: ' . $e->getMessage()]);
    exit;
}

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$matiere = isset($_GET['matiere']) ? trim($_GET['matiere']) : '';
$langue = isset($_GET['langue']) ? strtolower(trim($_GET['langue'])) : '';
$anneeMin = isset($_GET['annee_min']) && is_numeric($_GET['annee_min']) ? (int)$_GET['annee_min'] : null;
$anneeMax = isset($_GET['annee_max']) && is_numeric($_GET['annee_max']) ? (int)$_GET['annee_max'] : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 12;
$sortField = isset($_GET['sort']) ? $_GET['sort'] : 'titre';
$sortOrder = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';

if ($langue !== '') {
    $langue = substr($langue, 0, 2);
}

$allowedFields = ['titre', 'auteur', 'annee', 'matiere'];
$allowedOrders = ['ASC', 'DESC'];

if (!in_array($sortField, $allowedFields, true)) {
    $sortField = 'titre';
}
if (!in_array($sortOrder, $allowedOrders, true)) {
    $sortOrder = 'ASC';
}

$offset = ($page - 1) * $perPage;

// Detecter colonne langue
$languageColumn = null;
$stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'langue'");
if ($stmt && $stmt->rowCount() > 0) {
    $languageColumn = 'n.langue';
} else {
    $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'code_langue'");
    if ($stmt && $stmt->rowCount() > 0) {
        $languageColumn = 'n.code_langue';
    }
}

$languageBaseExpr = $languageColumn ? "LEFT(LOWER(MAX($languageColumn)), 2)" : "''";
$langueExpr = "CASE
    WHEN $languageBaseExpr IN ('fr', 'en', 'ar') THEN $languageBaseExpr
    WHEN MAX(e.expl_cb) LIKE 'INV_BUA_%' THEN 'ar'
    WHEN MAX(e.expl_cb) LIKE 'INV_BUF_%' THEN 'fr'
    WHEN MAX(n.tit1) REGEXP '[ء-ي]' THEN 'ar'
    WHEN LOWER(MAX(n.tit1)) REGEXP '(^|[^a-z])(the|and|of|in|for|with|to|from|on|at)([^a-z]|$)' THEN 'en'
    ELSE 'fr'
END";

$joins = " FROM notices n
    LEFT JOIN exemplaires e ON n.notice_id = e.expl_notice
    LEFT JOIN responsability r ON n.notice_id = r.responsability_notice
    LEFT JOIN authors a ON r.responsability_author = a.author_id
    LEFT JOIN publishers p ON n.ed1_id = p.ed_id
    LEFT JOIN notices_categories nc ON n.notice_id = nc.notcateg_notice
    LEFT JOIN categories c ON nc.num_noeud = c.num_noeud ";

$where = [];
$params = [];

if ($searchTerm !== '') {
    $like = '%' . $searchTerm . '%';
    $where[] = "(n.tit1 LIKE ? OR a.author_name LIKE ? OR e.expl_cote LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($matiere !== '') {
    $where[] = "c.libelle_categorie = ?";
    $params[] = $matiere;
}

if ($anneeMin !== null) {
    $where[] = "CAST(n.year AS SIGNED) >= ?";
    $params[] = $anneeMin;
}

if ($anneeMax !== null) {
    $where[] = "CAST(n.year AS SIGNED) <= ?";
    $params[] = $anneeMax;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$havingSql = '';
$queryParams = $params;
if ($langue !== '') {
    $havingSql = ' HAVING langue = ?';
    $queryParams[] = $langue;
}

$countSql = "SELECT COUNT(*) AS count FROM (
    SELECT n.notice_id, $langueExpr AS langue
    $joins
    $whereSql
    GROUP BY n.notice_id
    $havingSql
) t";

$stmt = $pdo->prepare($countSql);
$stmt->execute($queryParams);
$totalResults = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

$sortMap = [
    'titre' => 'titre',
    'auteur' => 'auteur',
    'annee' => 'annee',
    'matiere' => 'matiere'
];

$sql = "SELECT
    n.notice_id AS id,
    MAX(n.tit1) AS titre,
    MAX(a.author_name) AS auteur,
    MAX(e.expl_cote) AS cote,
    MAX(e.expl_cb) AS inventaire,
    MAX(p.ed_ville) AS lieu,
    MAX(p.ed_name) AS edition,
    MAX(n.npages) AS nb_pages,
    MAX(c.libelle_categorie) AS matiere,
    MAX(CAST(n.year AS SIGNED)) AS annee,
    $langueExpr AS langue
    $joins
    $whereSql
    GROUP BY n.notice_id
    $havingSql
    ORDER BY " . $sortMap[$sortField] . " $sortOrder
    LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($queryParams);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'total' => $totalResults,
    'page' => $page,
    'per_page' => $perPage,
    'total_pages' => max(1, (int)ceil($totalResults / $perPage)),
    'results' => $results
]);
?>
