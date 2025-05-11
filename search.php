<?php
// Connexion à la base de données
$host = 'localhost';
$dbname = 'biblio_clean';
$user = 'root';
$pass = 'root'; // modifie si besoin

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    exit;
}

// Récupération des paramètres
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$matiere = isset($_GET['matiere']) ? trim($_GET['matiere']) : '';
$anneeMin = isset($_GET['annee_min']) && is_numeric($_GET['annee_min']) ? (int)$_GET['annee_min'] : null;
$anneeMax = isset($_GET['annee_max']) && is_numeric($_GET['annee_max']) ? (int)$_GET['annee_max'] : null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
$sortField = isset($_GET['sort']) ? $_GET['sort'] : 'titre';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validation des paramètres de tri
$allowedFields = ['titre', 'auteur', 'annee', 'matiere'];
$allowedOrders = ['ASC', 'DESC'];

if (!in_array($sortField, $allowedFields)) $sortField = 'titre';
if (!in_array($sortOrder, $allowedOrders)) $sortOrder = 'ASC';

// Construction de la requête
$query = "SELECT * FROM livres WHERE 1=1";
$countQuery = "SELECT COUNT(*) as count FROM livres WHERE 1=1";
$params = [];

if (!empty($searchTerm)) {
    $searchTerm = '%' . $searchTerm . '%';
    $query .= " AND (titre LIKE ? OR auteur LIKE ? OR cote LIKE ?)";
    $countQuery .= " AND (titre LIKE ? OR auteur LIKE ? OR cote LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($matiere)) {
    $query .= " AND matiere = ?";
    $countQuery .= " AND matiere = ?";
    $params[] = $matiere;
}

if ($anneeMin !== null) {
    $query .= " AND annee >= ?";
    $countQuery .= " AND annee >= ?";
    $params[] = $anneeMin;
}

if ($anneeMax !== null) {
    $query .= " AND annee <= ?";
    $countQuery .= " AND annee <= ?";
    $params[] = $anneeMax;
}

// Comptage total pour pagination
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalResults = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Calcul de l'offset pour la pagination
$offset = ($page - 1) * $perPage;

// Ajout du tri et de la pagination
$query .= " ORDER BY $sortField $sortOrder LIMIT $perPage OFFSET $offset";

// Exécution de la requête
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparation de la réponse
$response = [
    'total' => (int)$totalResults,
    'page' => $page,
    'per_page' => $perPage,
    'total_pages' => ceil($totalResults / $perPage),
    'results' => $results
];

// Envoi de la réponse JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
