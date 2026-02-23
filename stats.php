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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    exit;
}

// Récupération des statistiques
$stats = [];

// Nombre total de notices
$stmt = $pdo->query("SELECT COUNT(*) as total FROM notices");
$stats['total'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre de livres par matière (limité à 10)
$stmt = $pdo->query("SELECT c.libelle_categorie as matiere, COUNT(DISTINCT nc.notcateg_notice) as count 
                     FROM categories c 
                     JOIN notices_categories nc ON c.num_noeud = nc.num_noeud 
                     WHERE c.langue = 'fr_FR' 
                     GROUP BY c.libelle_categorie 
                     ORDER BY count DESC 
                     LIMIT 10");
$stats['matieres'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nombre de livres par année (limité aux 10 plus récentes)
$stmt = $pdo->query("SELECT year as annee, COUNT(*) as count 
                     FROM notices 
                     WHERE year IS NOT NULL AND year > 0 
                     GROUP BY year 
                     ORDER BY CAST(year AS SIGNED) DESC 
                     LIMIT 10");
$stats['annees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 10 des auteurs
$stmt = $pdo->query("SELECT a.author_name as auteur, COUNT(DISTINCT r.responsability_notice) as count 
                     FROM authors a 
                     JOIN responsability r ON a.author_id = r.responsability_author 
                     GROUP BY a.author_name 
                     ORDER BY count DESC 
                     LIMIT 10");
$stats['auteurs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Répartition par langue (si la colonne existe)
$stats['langues'] = [];
if ($colonneLangueExiste) {
    $stmt = $pdo->query("SELECT langue as code, COUNT(*) as count 
                         FROM notices 
                         WHERE langue IS NOT NULL AND langue != '' 
                         GROUP BY langue 
                         ORDER BY count DESC");
    $stats['langues'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($colonneCodeLangueExiste) {
    $stmt = $pdo->query("SELECT code_langue as code, COUNT(*) as count 
                         FROM notices 
                         WHERE code_langue IS NOT NULL AND code_langue != '' 
                         GROUP BY code_langue 
                         ORDER BY count DESC");
    $stats['langues'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Annees min et max (range de publication raisonnable)
$stmt = $pdo->query("SELECT MIN(CAST(year AS SIGNED)) as min_annee, MAX(CAST(year AS SIGNED)) as max_annee FROM notices WHERE year IS NOT NULL AND year > 0 AND CAST(year AS SIGNED) BETWEEN 1800 AND YEAR(CURDATE()) + 1");
$anneeRange = $stmt->fetch(PDO::FETCH_ASSOC);

// Fallback brut si aucune annee dans la plage raisonnable
if (empty($anneeRange['min_annee']) || empty($anneeRange['max_annee'])) {
    $stmt = $pdo->query("SELECT MIN(CAST(year AS SIGNED)) as min_annee, MAX(CAST(year AS SIGNED)) as max_annee FROM notices WHERE year IS NOT NULL AND year > 0");
    $anneeRange = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stats['annee_range'] = [
    'min' => (int)$anneeRange['min_annee'],
    'max' => (int)$anneeRange['max_annee']
];

// Envoi de la réponse JSON
header('Content-Type: application/json');
echo json_encode($stats);
?>
