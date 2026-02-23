<?php
// Connexion à la base de données PMB
$host = 'localhost';
$dbname = 'pmb';
$user = 'root';
$pass = 'root123'; // Modifié selon votre script

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérifier si la colonne langue existe dans la table notices
$colonneLangueExiste = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'langue'");
    $colonneLangueExiste = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    // La colonne n'existe pas
}

// Vérifier si la colonne code_langue existe dans la table notices
$colonneCodeLangueExiste = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'code_langue'");
    $colonneCodeLangueExiste = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    // La colonne n'existe pas
}

// Récupération des paramètres
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$anneeMin = isset($_GET['annee_min']) && is_numeric($_GET['annee_min']) ? (int)$_GET['annee_min'] : null;
$anneeMax = isset($_GET['annee_max']) && is_numeric($_GET['annee_max']) ? (int)$_GET['annee_max'] : null;
$sortField = isset($_GET['sort']) ? $_GET['sort'] : 'titre';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validation des paramètres de tri
$allowedFields = ['titre', 'auteur', 'annee'];
$allowedOrders = ['ASC', 'DESC'];

if (!in_array($sortField, $allowedFields)) $sortField = 'titre';
if (!in_array($sortOrder, $allowedOrders)) $sortOrder = 'ASC';

// Mapping des champs pour le tri
$sortMapping = [
    'titre' => 'n.tit1',
    'auteur' => 'a.author_name',
    'annee' => 'CAST(n.year AS SIGNED)'
];

// Construction de la requête
$query = "SELECT DISTINCT n.notice_id, n.tit1 as titre, n.year as annee, n.npages as nb_pages, ";

// Ajouter la colonne langue si elle existe
if ($colonneLangueExiste) {
    $query .= "n.langue as langue, ";
} elseif ($colonneCodeLangueExiste) {
    $query .= "n.code_langue as langue, ";
} else {
    $query .= "NULL as langue, ";
}

$query .= "e.expl_cb as inventaire, e.expl_cote as cote, 
           a.author_name as auteur, 
           p.ed_name as edition, p.ed_ville as lieu,
           c.libelle_categorie as matiere
      FROM notices n
      LEFT JOIN exemplaires e ON n.notice_id = e.expl_notice
      LEFT JOIN responsability r ON n.notice_id = r.responsability_notice
      LEFT JOIN authors a ON r.responsability_author = a.author_id
      LEFT JOIN publishers p ON n.ed1_id = p.ed_id
      LEFT JOIN notices_categories nc ON n.notice_id = nc.notcateg_notice
      LEFT JOIN categories c ON nc.num_noeud = c.num_noeud
      WHERE 1=1";

$params = [];

if (!empty($searchTerm)) {
    $searchTerm = '%' . $searchTerm . '%';
    $query .= " AND (n.tit1 LIKE ? OR a.author_name LIKE ? OR e.expl_cote LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($anneeMin !== null) {
    $query .= " AND CAST(n.year AS SIGNED) >= ?";
    $params[] = $anneeMin;
}

if ($anneeMax !== null) {
    $query .= " AND CAST(n.year AS SIGNED) <= ?";
    $params[] = $anneeMax;
}

// Ajout du tri
$query .= " ORDER BY " . $sortMapping[$sortField] . " $sortOrder LIMIT 1000"; // Limiter à 1000 résultats pour l'export

// Exécution de la requête
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Génération du CSV
$filename = 'export_bibliotheque_' . date('Y-m-d_H-i-s') . '.csv';

// En-têtes pour le téléchargement
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Ouvrir le flux de sortie
$output = fopen('php://output', 'w');

// Ajouter le BOM UTF-8 pour Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes CSV
fputcsv($output, ['Cote', 'Titre', 'Auteur', 'Lieu', 'Edition', 'Année', 'Nombre de pages', 'Matière', 'Langue', 'Inventaire']);

// Données
foreach ($results as $row) {
    fputcsv($output, [
        $row['cote'] ?? '',
        $row['titre'] ?? '',
        $row['auteur'] ?? '',
        $row['lieu'] ?? '',
        $row['edition'] ?? '',
        $row['annee'] ?? '',
        $row['nb_pages'] ?? '',
        $row['matiere'] ?? '',
        $row['langue'] ?? '',
        $row['inventaire'] ?? ''
    ]);
}

fclose($output);
exit;
?>
