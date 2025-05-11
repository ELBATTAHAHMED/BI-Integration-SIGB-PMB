<?php
// Connexion à la base de données PMB
$host = 'localhost';
$dbname = 'pmb';
$user = 'root';
$pass = 'root'; // Modifié selon votre script

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $errorMessage = $e->getMessage();
}

// Récupération des statistiques de base seulement (optimisé)
$stats = [];
if ($dbConnected) {
    // Nombre total de notices
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notices");
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Nombre total d'exemplaires
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM exemplaires");
    $stats['exemplaires'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Nombre total d'auteurs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM authors");
    $stats['auteurs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
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
}

// Traitement de la recherche
$searchResults = [];
$searchPerformed = false;
$totalResults = 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // Réduit pour de meilleures performances
$offset = ($page - 1) * $perPage;

// Debug des paramètres de recherche
$debugInfo = [];

if (isset($_GET['q']) || isset($_GET['annee_min']) || isset($_GET['annee_max'])) {
    $searchPerformed = true;
    
    // Vérifier si la colonne langue existe
    $colonneLangueExiste = false;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'langue'");
        $colonneLangueExiste = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // La colonne n'existe pas
    }
    
    // Vérifier si la colonne code_langue existe
    $colonneCodeLangueExiste = false;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM notices LIKE 'code_langue'");
        $colonneCodeLangueExiste = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // La colonne n'existe pas
    }
    
    // Requête de base avec jointures pour PMB
    $query = "SELECT n.notice_id, n.tit1 as titre, n.year as annee, n.npages as nb_pages, ";
    
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
    
    if (!empty($_GET['q'])) {
        $searchTerm = '%' . $_GET['q'] . '%';
        $query .= " AND (n.tit1 LIKE ? OR a.author_name LIKE ? OR e.expl_cote LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $debugInfo[] = "Recherche texte: " . $_GET['q'];
    }
    
    // Correction de la recherche par année
    if (!empty($_GET['annee_min'])) {
        $anneeMin = (int)$_GET['annee_min'];
        $query .= " AND CAST(n.year AS SIGNED) >= ?";
        $params[] = $anneeMin;
        $debugInfo[] = "Année min: " . $anneeMin;
    }
    
    if (!empty($_GET['annee_max'])) {
        $anneeMax = (int)$_GET['annee_max'];
        $query .= " AND CAST(n.year AS SIGNED) <= ?";
        $params[] = $anneeMax;
        $debugInfo[] = "Année max: " . $anneeMax;
    }
    
    // Comptage total pour pagination (optimisé)
    $countQuery = "SELECT COUNT(DISTINCT n.notice_id) as count FROM notices n
                  LEFT JOIN exemplaires e ON n.notice_id = e.expl_notice
                  LEFT JOIN responsability r ON n.notice_id = r.responsability_notice
                  LEFT JOIN authors a ON r.responsability_author = a.author_id
                  LEFT JOIN notices_categories nc ON n.notice_id = nc.notcateg_notice
                  LEFT JOIN categories c ON nc.num_noeud = c.num_noeud
                  WHERE 1=1";
    
    if (!empty($_GET['q'])) {
        $countQuery .= " AND (n.tit1 LIKE ? OR a.author_name LIKE ? OR e.expl_cote LIKE ?)";
    }
    
    if (!empty($_GET['annee_min'])) {
        $countQuery .= " AND CAST(n.year AS SIGNED) >= ?";
    }
    
    if (!empty($_GET['annee_max'])) {
        $countQuery .= " AND CAST(n.year AS SIGNED) <= ?";
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalResults = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Tri
    $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'titre';
    $sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';
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
    
    $query .= " GROUP BY n.notice_id ORDER BY " . $sortMapping[$sortField] . " $sortOrder LIMIT $perPage OFFSET $offset";
    
    // Exécution de la requête
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug de la requête SQL
    $debugInfo[] = "Requête SQL: " . $query;
    $debugInfo[] = "Paramètres: " . implode(", ", $params);
    $debugInfo[] = "Nombre de résultats: " . $totalResults;
}

// Récupération des années min et max (optimisé)
$anneeRange = [null, null];
if ($dbConnected) {
    $stmt = $pdo->query("SELECT MIN(CAST(year AS SIGNED)) as min_annee, MAX(CAST(year AS SIGNED)) as max_annee FROM notices WHERE year IS NOT NULL AND year > 0");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $anneeRange = [$result['min_annee'], $result['max_annee']];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque Universitaire - Recherche</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Optimisé: Chargement sélectif des icônes Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2D7kSs0v4LLanw2qksYuRlEzO+tcaEPQogQ0KaoGN26/zrn20ImR1DfuLWnOo7aBA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Optimisé: Chargement différé de Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <style>
        .book-card {
            transition: transform 0.2s ease;
        }
        .book-card:hover {
            transform: translateY(-3px);
        }
        .dark {
            background-color: #1a202c;
            color: #e2e8f0;
        }
        .dark .book-card, .dark .bg-white {
            background-color: #2d3748;
        }
        .dark .text-gray-700, .dark .text-gray-800 {
            color: #e2e8f0;
        }
        .dark .text-gray-600, .dark .text-gray-500 {
            color: #cbd5e0;
        }
        .dark .border-gray-200 {
            border-color: #4a5568;
        }
        .dark .bg-gray-100, .dark .bg-gray-50 {
            background-color: #4a5568;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 transition-colors duration-200">
    <header class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center mb-4 md:mb-0">
                    <i class="fas fa-book-open text-3xl mr-3"></i>
                    <h1 class="text-2xl md:text-3xl font-bold">Bibliothèque Universitaire</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="darkModeToggle" class="p-2 rounded-full hover:bg-blue-500 transition-colors">
                        <i class="fas fa-moon"></i>
                    </button>
                    <a href="#statistiques" class="px-4 py-2 rounded-lg bg-white bg-opacity-20 hover:bg-opacity-30 transition-colors">
                        <i class="fas fa-chart-bar mr-2"></i>Statistiques
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <?php if (!$dbConnected): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Erreur de connexion à la base de données</p>
                <p><?php echo $errorMessage; ?></p>
            </div>
        <?php else: ?>
            <!-- Formulaire de recherche -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Recherche de livres</h2>
                <form action="" method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label for="q" class="block text-sm font-medium text-gray-700 mb-1">Recherche par titre, auteur ou cote</label>
                            <div class="relative">
                                <input type="text" id="q" name="q" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" 
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Année de publication</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <input type="number" id="annee_min" name="annee_min" placeholder="Min" min="<?php echo $anneeRange[0]; ?>" max="<?php echo $anneeRange[1]; ?>" 
                                        value="<?php echo isset($_GET['annee_min']) ? htmlspecialchars($_GET['annee_min']) : ''; ?>" 
                                        class="block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <input type="number" id="annee_max" name="annee_max" placeholder="Max" min="<?php echo $anneeRange[0]; ?>" max="<?php echo $anneeRange[1]; ?>" 
                                        value="<?php echo isset($_GET['annee_max']) ? htmlspecialchars($_GET['annee_max']) : ''; ?>" 
                                        class="block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <div>
                            <label for="sort" class="text-sm font-medium text-gray-700 mr-2">Trier par:</label>
                            <select id="sort" name="sort" class="py-1 px-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="titre" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'titre') ? 'selected' : ''; ?>>Titre</option>
                                <option value="auteur" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'auteur') ? 'selected' : ''; ?>>Auteur</option>
                                <option value="annee" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'annee') ? 'selected' : ''; ?>>Année</option>
                            </select>
                            <select id="order" name="order" class="ml-2 py-1 px-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="ASC" <?php echo (!isset($_GET['order']) || $_GET['order'] === 'ASC') ? 'selected' : ''; ?>>Croissant</option>
                                <option value="DESC" <?php echo (isset($_GET['order']) && $_GET['order'] === 'DESC') ? 'selected' : ''; ?>>Décroissant</option>
                            </select>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                    </div>
                </form>
            </div>

            <!-- Résultats de recherche -->
            <?php if ($searchPerformed): ?>
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">
                            <?php echo $totalResults; ?> résultat<?php echo $totalResults > 1 ? 's' : ''; ?> trouvé<?php echo $totalResults > 1 ? 's' : ''; ?>
                        </h2>
                        <?php if ($totalResults > 0): ?>
                            <div class="text-sm text-gray-500">
                                Affichage de <?php echo min($offset + 1, $totalResults); ?> à <?php echo min($offset + $perPage, $totalResults); ?> sur <?php echo $totalResults; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($searchResults) > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($searchResults as $livre): ?>
                                <div class="book-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                                    <div class="p-4">
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($livre['titre']); ?></h3>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <?php echo !empty($livre['inventaire']) ? htmlspecialchars(substr($livre['inventaire'], 0, 5)) : 'N/A'; ?>
                                            </span>
                                        </div>
                                        <p class="text-gray-600 mb-2">
                                            <i class="fas fa-user text-gray-400 mr-1"></i> 
                                            <?php echo !empty($livre['auteur']) ? htmlspecialchars($livre['auteur']) : '<span class="text-gray-400 italic">Auteur inconnu</span>'; ?>
                                        </p>
                                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                                            <span>
                                                <i class="fas fa-map-marker-alt mr-1"></i> 
                                                <?php echo !empty($livre['lieu']) ? htmlspecialchars($livre['lieu']) : 'N/A'; ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-calendar-alt mr-1"></i> 
                                                <?php echo !empty($livre['annee']) ? htmlspecialchars($livre['annee']) : 'N/A'; ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">
                                                <i class="fas fa-bookmark mr-1"></i> 
                                                <?php echo !empty($livre['matiere']) ? htmlspecialchars($livre['matiere']) : 'N/A'; ?>
                                            </span>
                                            <span class="text-gray-500">
                                                <i class="fas fa-file-alt mr-1"></i> 
                                                <?php echo !empty($livre['nb_pages']) ? htmlspecialchars($livre['nb_pages']) . ' p.' : 'N/A'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-gray-500">
                                                <i class="fas fa-barcode mr-1"></i> <?php echo !empty($livre['cote']) ? htmlspecialchars($livre['cote']) : 'N/A'; ?>
                                            </span>
                                            <button class="text-indigo-600 hover:text-indigo-800" 
                                                    onclick="showDetails('<?php echo addslashes(htmlspecialchars($livre['titre'])); ?>', '<?php echo addslashes(htmlspecialchars($livre['auteur'])); ?>', '<?php echo addslashes(htmlspecialchars($livre['cote'])); ?>', '<?php echo addslashes(htmlspecialchars($livre['lieu'])); ?>', '<?php echo addslashes(htmlspecialchars($livre['edition'])); ?>', '<?php echo addslashes(htmlspecialchars($livre['annee'])); ?>', '<?php echo addslashes(htmlspecialchars($livre['nb_pages'])); ?>', '<?php echo addslashes(htmlspecialchars($livre['matiere'])); ?>', '<?php echo addslashes(htmlspecialchars($livre['inventaire'])); ?>', '<?php echo isset($livre['langue']) ? addslashes(htmlspecialchars($livre['langue'])) : 'N/A'; ?>')">
                                                <i class="fas fa-info-circle"></i> Détails
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination simplifiée -->
                        <?php if ($totalResults > $perPage): ?>
                            <div class="mt-6 flex justify-center">
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php
                                    $totalPages = ceil($totalResults / $perPage);
                                    $queryParams = $_GET;
                                    
                                    // Bouton précédent
                                    $queryParams['page'] = max(1, $page - 1);
                                    $prevLink = '?' . http_build_query($queryParams);
                                    ?>
                                    <a href="<?php echo $page > 1 ? $prevLink : '#'; ?>" class="<?php echo $page > 1 ? '' : 'opacity-50 cursor-not-allowed'; ?> relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Précédent</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    
                                    <?php
                                    // Affichage des pages (limité à 5 pages)
                                    $startPage = max(1, min($page - 2, $totalPages - 4));
                                    $endPage = min($totalPages, $startPage + 4);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++) {
                                        $queryParams['page'] = $i;
                                        $pageLink = '?' . http_build_query($queryParams);
                                        $isActive = $i === $page;
                                    ?>
                                        <a href="<?php echo $pageLink; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $isActive ? 'bg-indigo-50 text-indigo-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php } ?>
                                    
                                    <?php
                                    // Bouton suivant
                                    $queryParams['page'] = min($totalPages, $page + 1);
                                    $nextLink = '?' . http_build_query($queryParams);
                                    ?>
                                    <a href="<?php echo $page < $totalPages ? $nextLink : '#'; ?>" class="<?php echo $page < $totalPages ? '' : 'opacity-50 cursor-not-allowed'; ?> relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Suivant</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Aucun livre ne correspond à votre recherche. Essayez de modifier vos critères.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Section Statistiques simplifiée -->
            <div id="statistiques" class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-6 text-gray-800">Statistiques de la bibliothèque</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4 shadow">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-500 text-white mr-4">
                                <i class="fas fa-book"></i>
                            </div>
                            <div>
                                <p class="text-sm text-blue-600">Total des notices</p>
                                <p class="text-2xl font-bold text-blue-800"><?php echo number_format($stats['total']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-purple-50 rounded-lg p-4 shadow">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-500 text-white mr-4">
                                <i class="fas fa-copy"></i>
                            </div>
                            <div>
                                <p class="text-sm text-purple-600">Total des exemplaires</p>
                                <p class="text-2xl font-bold text-purple-800"><?php echo number_format($stats['exemplaires']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 rounded-lg p-4 shadow">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-500 text-white mr-4">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <p class="text-sm text-green-600">Total des auteurs</p>
                                <p class="text-2xl font-bold text-green-800"><?php echo number_format($stats['auteurs']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bouton pour charger les graphiques à la demande -->
                <div class="text-center">
                    <button id="loadChartsBtn" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <i class="fas fa-chart-bar mr-2"></i>Charger les graphiques détaillés
                    </button>
                    <div id="chartsContainer" class="hidden mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h3 class="text-lg font-medium mb-4 text-gray-700">Top 10 des matières</h3>
                                <div class="h-80">
                                    <canvas id="matieresChart"></canvas>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium mb-4 text-gray-700">Top 10 des années de publication</h3>
                                <div class="h-80">
                                    <canvas id="anneesChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($stats['langues'])): ?>
                        <!-- Ajout du graphique de répartition par langue -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium mb-4 text-gray-700">Répartition par langue</h3>
                            <div class="h-80">
                                <canvas id="languesChart"></canvas>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Affichage des informations de débogage (à supprimer en production) -->
            <?php if (!empty($debugInfo) && isset($_GET['debug'])): ?>
            <div class="bg-gray-100 p-4 rounded-lg mb-8">
                <h3 class="text-lg font-medium mb-2">Informations de débogage</h3>
                <pre class="text-xs overflow-auto"><?php echo implode("\n", $debugInfo); ?></pre>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- Modal de détails (simplifié) -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 overflow-hidden">
            <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Détails du livre</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Titre</p>
                        <p class="text-base text-gray-900 mb-3" id="modalTitre"></p>
                        
                        <p class="text-sm font-medium text-gray-500">Auteur</p>
                        <p class="text-base text-gray-900 mb-3" id="modalAuteur"></p>
                        
                        <p class="text-sm font-medium text-gray-500">Cote</p>
                        <p class="text-base text-gray-900 mb-3" id="modalCote"></p>
                        
                        <p class="text-sm font-medium text-gray-500">Matière</p>
                        <p class="text-base text-gray-900 mb-3" id="modalMatiere"></p>
                        
                        <p class="text-sm font-medium text-gray-500">Langue</p>
                        <p class="text-base text-gray-900 mb-3" id="modalLangue"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Lieu d'édition</p>
                        <p class="text-base text-gray-900 mb-3" id="modalLieu"></p>
                        
                        <p class="text-sm font-medium text-gray-500">Éditeur</p>
                        <p class="text-base text-gray-900 mb-3" id="modalEdition"></p>
                        
                        <p class="text-sm font-medium text-gray-500">Année</p>
                        <p class="text-base text-gray-900 mb-3" id="modalAnnee"></p>
                        
                        <p class="text-sm font-medium text-gray-500">Nombre de pages</p>
                        <p class="text-base text-gray-900" id="modalPages"></p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 focus:outline-none">
                    Fermer
                </button>
            </div>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-4">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Bibliothèque Universitaire</p>
        </div>
    </footer>

    <script>
        // Fonctions pour le modal
        function showDetails(titre, auteur, cote, lieu, edition, annee, pages, matiere, inventaire, langue) {
            document.getElementById('modalTitre').textContent = titre || 'Non spécifié';
            document.getElementById('modalAuteur').textContent = auteur || 'Non spécifié';
            document.getElementById('modalCote').textContent = cote || 'Non spécifié';
            document.getElementById('modalLieu').textContent = lieu || 'Non spécifié';
            document.getElementById('modalEdition').textContent = edition || 'Non spécifié';
            document.getElementById('modalAnnee').textContent = annee || 'Non spécifié';
            document.getElementById('modalPages').textContent = pages ? pages + ' pages' : 'Non spécifié';
            document.getElementById('modalMatiere').textContent = matiere || 'Non spécifié';
            document.getElementById('modalLangue').textContent = langue || 'Non spécifié';
            
            document.getElementById('detailsModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.getElementById('detailsModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        // Fermer le modal en cliquant en dehors
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Mode sombre
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (darkModeToggle) {
            // Vérifier si le mode sombre est activé
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.documentElement.classList.add('dark');
                darkModeToggle.querySelector('i').classList.remove('fa-moon');
                darkModeToggle.querySelector('i').classList.add('fa-sun');
            }
            
            // Toggle mode sombre
            darkModeToggle.addEventListener('click', function() {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('darkMode', isDark);
                
                // Changer l'icône
                const icon = this.querySelector('i');
                if (isDark) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            });
        }
        
        // Chargement des graphiques à la demande
        const loadChartsBtn = document.getElementById('loadChartsBtn');
        const chartsContainer = document.getElementById('chartsContainer');
        
        if (loadChartsBtn && chartsContainer) {
            loadChartsBtn.addEventListener('click', function() {
                // Afficher le conteneur de graphiques
                chartsContainer.classList.remove('hidden');
                loadChartsBtn.classList.add('hidden');
                
                // Charger les données des graphiques via AJAX
                fetch('stats.php')
                    .then(response => response.json())
                    .then(data => {
                        // Créer les graphiques une fois les données chargées
                        createCharts(data);
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des statistiques:', error);
                        chartsContainer.innerHTML = '<div class="text-red-500 text-center">Erreur lors du chargement des graphiques</div>';
                    });
            });
        }
        
        function createCharts(data) {
            // Configuration des couleurs pour le mode sombre/clair
            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? '#e2e8f0' : '#4a5568';
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            
            // Graphique des matières
            if (data.matieres && data.matieres.length > 0) {
                const matieresCtx = document.getElementById('matieresChart').getContext('2d');
                const matieresData = data.matieres.slice(0, 10); // Limiter à 10 matières
                
                new Chart(matieresCtx, {
                    type: 'bar',
                    data: {
                        labels: matieresData.map(item => item.matiere),
                        datasets: [{
                            label: 'Nombre de livres',
                            data: matieresData.map(item => item.count),
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(255, 206, 86, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)',
                                'rgba(255, 159, 64, 0.7)',
                                'rgba(199, 199, 199, 0.7)',
                                'rgba(83, 102, 255, 0.7)',
                                'rgba(40, 159, 64, 0.7)',
                                'rgba(210, 199, 199, 0.7)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: gridColor
                                },
                                ticks: {
                                    color: textColor
                                }
                            },
                            x: {
                                grid: {
                                    color: gridColor
                                },
                                ticks: {
                                    color: textColor,
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: textColor
                                }
                            }
                        }
                    }
                });
            }
            
            // Graphique des années
            if (data.annees && data.annees.length > 0) {
                const anneesCtx = document.getElementById('anneesChart').getContext('2d');
                // Trier par année et prendre les 10 plus récentes
                const anneesData = data.annees
                    .sort((a, b) => b.annee - a.annee)
                    .slice(0, 10);
                
                new Chart(anneesCtx, {
                    type: 'line',
                    data: {
                        labels: anneesData.map(item => item.annee),
                        datasets: [{
                            label: 'Nombre de livres',
                            data: anneesData.map(item => item.count),
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 2,
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: gridColor
                                },
                                ticks: {
                                    color: textColor
                                }
                            },
                            x: {
                                grid: {
                                    color: gridColor
                                },
                                ticks: {
                                    color: textColor
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: textColor
                                }
                            }
                        }
                    }
                });
            }
            
            // Graphique des langues
            if (data.langues && data.langues.length > 0 && document.getElementById('languesChart')) {
                const languesCtx = document.getElementById('languesChart').getContext('2d');
                const languesData = data.langues.slice(0, 10); // Limiter aux 10 langues les plus utilisées
                
                new Chart(languesCtx, {
                    type: 'pie',
                    data: {
                        labels: languesData.map(item => item.code || 'Non spécifié'),
                        datasets: [{
                            data: languesData.map(item => item.count),
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(255, 206, 86, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)',
                                'rgba(255, 159, 64, 0.7)',
                                'rgba(199, 199, 199, 0.7)',
                                'rgba(83, 102, 255, 0.7)',
                                'rgba(40, 159, 64, 0.7)',
                                'rgba(210, 199, 199, 0.7)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: textColor
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>
