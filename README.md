![image_alt](https://github.com/ELBATTAHAHMED/BI-Integration-SIGB-PMB/blob/8a5f5dfd05d47b439e277a893e53c1e308c6b4f6/BI-Integration-SIGB-PMB.png)

# BI-Integration-SIGB-PMB

Ce projet de Business Intelligence sert a integrer des donnees bibliographiques (BUA/BUF) dans PMB, puis a les exploiter avec une interface web de recherche et de statistiques. Le flux est: nettoyage des donnees, import automatique vers PMB, reindexation PMB, puis consultation dans un dashboard PHP.

On travaille sur des champs comme: cote, titre, auteur, lieu, edition, annee, nombre de pages, matiere et inventaire.

Le projet contient:

- un script d import (`import_to_pmb.php`)
- une interface web de recherche/statistiques (`index.php`)
- un endpoint stats pour les graphes (`stats.php`)
- un export CSV (`export.php`)
- un script SQL d optimisation (`optimistion.sql`)

Les tables PMB principales utilisees:

- `notices`
- `exemplaires`
- `authors`
- `responsability`
- `publishers`
- `categories`
- `notices_categories`

## Prerequis

- Windows
- XAMPP installe
- PMB installe dans XAMPP
- PowerShell

## Etapes completes (dans l ordre)

### 1) Installer et demarrer XAMPP

1. Ouvrir XAMPP Control Panel
2. Demarrer `Apache`
3. Demarrer `MySQL`

### 2) Installer PMB

1. Mettre PMB dans:

   - `C:\xampp\htdocs\pmb`

2. Ouvrir dans le navigateur:

   - `http://localhost/pmb`

3. Finir l installation PMB
4. Garder les infos MySQL notees (user/pass)

Important: PMB doit etre installe avant ce projet, sinon les tables (`notices`, etc.) n existent pas.

### 3) Ouvrir le projet BI dans PowerShell

```powershell
$project = "C:\Users\LENOVO\Desktop\BI-Integration-SIGB-PMB"
$php = "C:\xampp\php\php.exe"
$mysql = "C:\xampp\mysql\bin\mysql.exe"
$dbPass = "root123"

Set-Location $project
```

Si ton mot de passe root est different, change juste `$dbPass`.

### 4) Verifier PHP + MySQL

```powershell
& $php -v
& $mysql -u root --password=$dbPass -e "SELECT 1;"
```

### 5) Verifier que PMB est bien pret

```powershell
& $mysql -u root --password=$dbPass -D pmb -e "SHOW TABLES LIKE 'notices';"
```

Si tu ne vois pas `notices`, PMB n est pas encore bien installe.

### 6) Verifier le mot de passe dans les fichiers PHP

Les fichiers suivants doivent avoir le meme mot de passe MySQL que PMB:

- `index.php`
- `stats.php`
- `export.php`
- `import_to_pmb.php`
- `search.php`

### 7) Importer les donnees CSV dans PMB

```powershell
& $php .\import_to_pmb.php
```

Puis verifier les compteurs:

```powershell
& $mysql -u root --password=$dbPass -D pmb -e "SELECT COUNT(*) AS notices_count FROM notices; SELECT COUNT(*) AS exemplaires_count FROM exemplaires;"
```

Important:

- ne pas relancer l import plusieurs fois (sinon doublons possibles)
- les donnees importees viennent de:
  - `CleanedData/cleaned_bua.csv`
  - `CleanedData/cleaned_buf.csv`

### 8) Lancer l optimisation SQL

```powershell
& $mysql -u root --password=$dbPass --init-command="SET SESSION sql_mode=''" -D pmb -e "source C:/Users/LENOVO/Desktop/BI-Integration-SIGB-PMB/optimistion.sql"
```

### 9) Faire la reindexation dans PMB (obligatoire)

Dans l interface admin PMB (`http://localhost/pmb`), lancer:

- `Reindex global indexes`
- `Reindex all the records search fields`

Attendre la fin.

### 10) Lancer le dashboard BI

```powershell
& $php -S localhost:8000 -t .
```

Ouvrir:

- `http://localhost:8000/index.php`

## URLs utiles

- PMB: `http://localhost/pmb`
- Dashboard BI: `http://localhost:8000/index.php`
- API stats: `http://localhost:8000/stats.php`

## Problemes frequents

### Erreur: `php is not recognized`

Utiliser le chemin complet:

```powershell
& "C:\xampp\php\php.exe" -v
```

### Erreur: `Access denied for user 'root'@'localhost'`

- mauvais mot de passe MySQL
- verifier le pass PMB ici:
  - `C:\xampp\htdocs\pmb\includes\db_param.inc.php`
- mettre le meme pass dans les fichiers PHP du projet

### Erreur: `Table 'pmb.notices' doesn't exist`

PMB pas installe completement. Refaire l installation PMB d abord.

### `404 /favicon.ico`

Pas grave. Tu peux ignorer.

## Fichiers importants

- `import_to_pmb.php`: import CSV vers PMB
- `optimistion.sql`: index + analyze
- `index.php`: interface principale
- `stats.php`: donnees des graphes
- `export.php`: export CSV
- `CleanedData/`: donnees nettoyees

## Note finale

Ce setup est pour dev local / demo / projet academique.
Pour production, il faut securiser les credentials et la config serveur.

## Auteur

EL BATTAH Ahmed
