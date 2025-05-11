-- Ajout d'index pour améliorer les performances des requêtes dans PMB

-- Index sur les colonnes fréquemment utilisées dans les recherches
CREATE INDEX IF NOT EXISTS idx_notices_tit1 ON notices(tit1);
CREATE INDEX IF NOT EXISTS idx_notices_year ON notices(year);
CREATE INDEX IF NOT EXISTS idx_exemplaires_cote ON exemplaires(expl_cote);
CREATE INDEX IF NOT EXISTS idx_exemplaires_cb ON exemplaires(expl_cb);
CREATE INDEX IF NOT EXISTS idx_authors_name ON authors(author_name);
CREATE INDEX IF NOT EXISTS idx_categories_libelle ON categories(libelle_categorie);

-- Vérifier si la colonne langue existe et créer un index si c'est le cas
DELIMITER //
CREATE PROCEDURE create_index_if_column_exists()
BEGIN
    DECLARE column_exists INT;
    
    -- Vérifier si la colonne langue existe
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'notices'
    AND column_name = 'langue';
    
    IF column_exists > 0 THEN
        -- Créer l'index si la colonne existe
        CREATE INDEX IF NOT EXISTS idx_notices_langue ON notices(langue);
    END IF;
    
    -- Vérifier si la colonne code_langue existe
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'notices'
    AND column_name = 'code_langue';
    
    IF column_exists > 0 THEN
        -- Créer l'index si la colonne existe
        CREATE INDEX IF NOT EXISTS idx_notices_code_langue ON notices(code_langue);
    END IF;
END //
DELIMITER ;

-- Exécuter la procédure
CALL create_index_if_column_exists();

-- Supprimer la procédure temporaire
DROP PROCEDURE IF EXISTS create_index_if_column_exists;

-- Index composites pour les jointures fréquentes
CREATE INDEX IF NOT EXISTS idx_notices_categories_notice ON notices_categories(notcateg_notice);
CREATE INDEX IF NOT EXISTS idx_responsability_notice ON responsability(responsability_notice);
CREATE INDEX IF NOT EXISTS idx_responsability_author ON responsability(responsability_author);

-- Analyse des tables pour optimiser les statistiques
ANALYZE TABLE notices;
ANALYZE TABLE exemplaires;
ANALYZE TABLE authors;
ANALYZE TABLE categories;
ANALYZE TABLE notices_categories;
ANALYZE TABLE responsability;
ANALYZE TABLE publishers;
