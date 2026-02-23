<?php
set_time_limit(0);

$dbHost = 'localhost';
$dbName = 'pmb';
$dbUser = 'root';
$dbPass = 'root123';

// Dossier des CSV
$baseDir = realpath(__DIR__ . '/CleanedData') . DIRECTORY_SEPARATOR;
$csvFiles = [
    $baseDir . 'cleaned_bua.csv',
    $baseDir . 'cleaned_buf.csv'
];
foreach ($csvFiles as $file) {
    if (!file_exists($file)) {
        die("❌ Fichier introuvable : $file<br>");
    }
    echo "✔ Found: $file<br>";
}

try {
    $pdo = null;
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    // Préparation des requêtes notices, exemplaires, auteurs, éditeurs…
    $stmtNotice = $pdo->prepare("INSERT INTO notices (typdoc, tit1, year, npages) VALUES ('a', ?, ?, ?)");
    $stmtExpl   = $pdo->prepare("INSERT INTO exemplaires (expl_notice, expl_cb, expl_cote, expl_statut, expl_location) VALUES (?, ?, ?, 1, 1)");
    $stmtCheckExpl = $pdo->prepare("SELECT COUNT(*) FROM exemplaires WHERE expl_cb = ?");
    $stmtCheckNoticeCore = $pdo->prepare("SELECT notice_id FROM notices WHERE tit1 = ? AND year = ? AND npages = ? LIMIT 1");

    $stmtAuthor     = $pdo->prepare("INSERT INTO authors (author_name) VALUES (?)");
    $stmtFindAuthor = $pdo->prepare("SELECT author_id FROM authors WHERE author_name = ?");

    $stmtPublisher     = $pdo->prepare("INSERT INTO publishers (ed_name, ed_ville) VALUES (?, ?)");
    $stmtFindPublisher = $pdo->prepare("SELECT ed_id FROM publishers WHERE ed_name = ?");
    $stmtUpdatePublisherId = $pdo->prepare("UPDATE notices SET ed1_id = ? WHERE notice_id = ?");

    $stmtLinkAuthor = $pdo->prepare("INSERT INTO responsability (responsability_notice, responsability_author, responsability_fonction, responsability_type) VALUES (?, ?, '0010', 0)");
    $stmtCheckLinkAuthor = $pdo->prepare("SELECT COUNT(*) FROM responsability WHERE responsability_notice = ? AND responsability_author = ? AND responsability_fonction = '0010' AND responsability_type = 0");

    // Requêtes pour catégories
    $stmtFindCategoryByLabel = $pdo->prepare("SELECT num_noeud FROM categories WHERE libelle_categorie = ?");
    $stmtGetMaxNoeud         = $pdo->prepare("SELECT COALESCE(MAX(num_noeud), 0) FROM categories WHERE langue = 'fr_FR'");
    $stmtInsertCategoryFull  = $pdo->prepare(
        "INSERT INTO categories (
            num_noeud, libelle_categorie, langue, num_thesaurus,
            note_application, comment_public, comment_voir,
            index_categorie, path_word_categ, index_path_word_categ
        ) VALUES (?, ?, 'fr_FR', 1, '', '', '', '', '', '')"
    );
    $stmtLinkCategory = $pdo->prepare("INSERT INTO notices_categories (notcateg_notice, num_noeud, num_vedette, ordre_vedette, ordre_categorie) VALUES (?, ?, 0, 1, 0)");
    $stmtCheckLinkCategory = $pdo->prepare("SELECT COUNT(*) FROM notices_categories WHERE notcateg_notice = ? AND num_noeud = ? AND num_vedette = 0");

    // Boucle CSV
    foreach ($csvFiles as $csvFile) {
        $handle = fopen($csvFile, 'r');
        fgetcsv($handle, 1000, ","); // header

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 9) continue;
            list($cote, $titre, $auteur, $lieu, $edition, $annee, $nbPages, $matiere, $inventaire) = $data;

            $cote = trim((string)$cote);
            $titre = trim((string)$titre);
            $auteur = trim((string)$auteur);
            $lieu = trim((string)$lieu);
            $edition = trim((string)$edition);
            $annee = trim((string)$annee);
            $nbPages = trim((string)$nbPages);
            $matiere = trim((string)$matiere);
            $inventaire = trim((string)$inventaire);

            // Eviter les doublons au reimport:
            // 1) si inventaire existe deja, on saute la ligne
            if ($inventaire !== '') {
                $stmtCheckExpl->execute([$inventaire]);
                if ((int)$stmtCheckExpl->fetchColumn() > 0) {
                    continue;
                }
            } else {
                // 2) fallback si inventaire vide: verifier noyau notice
                $stmtCheckNoticeCore->execute([$titre, $annee, $nbPages]);
                if ($stmtCheckNoticeCore->fetchColumn()) {
                    continue;
                }
            }

            // 1️⃣ Notice
            $stmtNotice->execute([$titre, $annee, $nbPages]);
            $noticeId = $pdo->lastInsertId();

            // 2️⃣ Exemplaire
            if ($inventaire !== '') {
                $stmtExpl->execute([$noticeId, $inventaire, $cote]);
            }

            // 3️⃣ Auteurs
            foreach (explode(';', $auteur) as $authorName) {
                $authorName = trim($authorName);
                if (!$authorName) continue;

                $stmtFindAuthor->execute([$authorName]);
                $authorId = $stmtFindAuthor->fetchColumn();
                if (!$authorId) {
                    $stmtAuthor->execute([$authorName]);
                    $authorId = $pdo->lastInsertId();
                }

                if ($authorId) {
                    $stmtCheckLinkAuthor->execute([$noticeId, $authorId]);
                    if ((int)$stmtCheckLinkAuthor->fetchColumn() === 0) {
                        $stmtLinkAuthor->execute([$noticeId, $authorId]);
                    }
                }
            }

            // 4️⃣ Éditeur
            if ($edition !== '') {
                $stmtFindPublisher->execute([$edition]);
                $publisherId = $stmtFindPublisher->fetchColumn();

                if (!$publisherId) {
                    $stmtPublisher->execute([$edition, $lieu]);
                    $publisherId = $pdo->lastInsertId();
                }

                if ($publisherId) {
                    $stmtUpdatePublisherId->execute([$publisherId, $noticeId]);
                }
            }

            // 5️⃣ Catégorie (matière)
            if ($matiere !== '') {
                // Nettoyage NBSP + trim
                $matiere = trim(str_replace("\xc2\xa0", ' ', $matiere));

                // Chercher si existe
                $stmtFindCategoryByLabel->execute([$matiere]);
                $categoryId = $stmtFindCategoryByLabel->fetchColumn();

                if (!$categoryId) {
                    // Générer un nouveau num_noeud
                    $stmtGetMaxNoeud->execute();
                    $maxNoeud = $stmtGetMaxNoeud->fetchColumn();
                    $newNoeud = $maxNoeud + 1;

                    // Insérer la catégorie complète
                    $stmtInsertCategoryFull->execute([$newNoeud, $matiere]);
                    $categoryId = $newNoeud;
                }

                if ($categoryId) {
                    // Lier la notice
                    $stmtCheckLinkCategory->execute([$noticeId, $categoryId]);
                    if ((int)$stmtCheckLinkCategory->fetchColumn() === 0) {
                        $stmtLinkCategory->execute([$noticeId, $categoryId]);
                    }
                }
            }
        }
        fclose($handle);
    }

    // Commit & réindexation
    $pdo->commit();
    echo "<br>✅ Importation réussie : " . date('Y-m-d H:i:s');


} catch (Exception $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("❌ Erreur : " . $e->getMessage());
}
?>
