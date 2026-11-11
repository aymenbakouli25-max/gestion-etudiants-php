# Mini Projet : Gestion des étudiants (PHP + MySQL)

Application de gestion scolaire **CRUD** (Create, Read, Update, Delete) avec recherche et filtre.

## Prérequis
- Windows 11 (ou autre OS)
- XAMPP (Apache + MySQL), PHP 8.1+ avec `pdo_mysql` et `mbstring`
- Navigateur web

## Installation sur XAMPP
1. Décompressez `gestion_etudiants_php.zip`.
2. Copiez le dossier **gestion_etudiants_php** dans `C:\xampp\htdocs\`.
3. Ouvrez **XAMPP Control Panel** et cliquez **Start** pour **Apache** et **MySQL**.
4. Dans votre navigateur, ouvrez **http://localhost/phpmyadmin/**.
5. Cliquez sur **Importer**, choisissez **database/gestion_etudiants.sql** depuis votre dossier de projet, puis cliquez sur **Importer / Exécuter**. Le script crée la base `gestion_etudiants`, la table `etudiants` et 3 exemples.
6. Accédez à **http://localhost/gestion_etudiants_php/**.
7. Connectez-vous avec `admin` / `admin123` (modifiez ce compte en production).

**Si MySQL demande un mot de passe :** modifiez `$username` et `$password` dans `config/db.php` selon votre installation. Les identifiants par défaut de XAMPP local sont `root` et un mot de passe vide.

## Fonctionnalités
- Tableau de bord : total, étudiants 1ère année, étudiants 2ème année
- Afficher les étudiants
- Rechercher par nom, prénom, code Apogée, email ou filière
- Filtrer par niveau
- Ajouter un étudiant
- Modifier un étudiant
- Supprimer un étudiant (avec confirmation)
- Vérification de données, protection CSRF, requêtes préparées PDO
- Interface responsive HTML/CSS
- Authentification administrateur avec protection des pages et déconnexion sécurisée
- Dashboard avec statistiques par niveau
- Pagination, tri et export CSV avec filtres
- Gestion des utilisateurs administrateurs
- Gestion des notes et calcul de moyenne pondérée
- Gestion de présence (présent, absent, en retard)
- Impression des relevés de notes vers PDF depuis le navigateur
- Statistiques de présence et moyenne générale dans le dashboard
- Profil détaillé étudiant et rapport de présence par date
- Modification des notes avec statut de réussite
- Sauvegarde SQL complète de la base
- Présence collective et import CSV des notes
- Rapports filtrés et journal d’audit des opérations
- Rôles administrateur/employé et import CSV des étudiants
- Gestion académique des filières, modules et classes
- Workflow administratif des dossiers étudiants
- Dashboard Analytics institutionnel
- Documents étudiants avec upload sécurisé
- API REST en lecture protégée par token
- API CRUD avec limitation de débit
- Gestion et téléchargement sécurisé des documents
- Affectation des étudiants aux filières et classes
- Support de langue française/arabe
- Configuration production via `.env.example`
- Frontend progressif en HTML/CSS/JavaScript natif, sans framework obligatoire
- Logo officiel EST Oujda/UMP intégré depuis le site institutionnel `esto.ump.ac.ma`
- Notifications email via la configuration mail de PHP
- Avant la mise en ligne, renseignez les variables d’environnement et activez HTTPS.
- Validation locale : `C:\xampp\php\php.exe tests\validate.php`.

## Organisation des fichiers
```
gestion_etudiants_php/
├── index.php                 # Tableau de bord + liste + recherche
├── login.php                 # Connexion administrateur
├── logout.php                # Déconnexion (POST)
├── admin.php                 # Gestion des utilisateurs
├── grades.php                # Notes et moyenne d’un étudiant
├── export.php                # Export CSV filtré
├── attendance.php            # Présence et absence
├── attendance_report.php    # Rapport de présence par date
├── profile.php              # Profil détaillé étudiant
├── backup.php               # Sauvegarde SQL
├── bulk_attendance.php      # Présence collective
├── bulk_grades.php          # Import CSV des notes
├── reports.php              # Rapports étudiants
├── audit.php                # Journal des opérations
├── student_import.php       # Import CSV des étudiants
├── academic.php             # Filières, modules et classes
├── workflow.php              # Suivi administratif
├── analytics.php             # Analytics institutionnel
├── documents.php             # Documents étudiant
├── api.php                   # API étudiants
├── api_token.php             # Génération de token API
├── print.php                 # Relevé imprimable / PDF
├── create.php                # Ajouter
├── edit.php                  # Modifier
├── delete.php                # Supprimer (POST)
├── config/db.php             # Connexion PDO à MySQL
├── database/gestion_etudiants.sql # Base de données + exemples
├── includes/functions.php    # Fonctions de validation / sécurité
├── includes/student_form.php # Formulaire partagé
├── includes/header.php       # En-tête
├── includes/footer.php       # Pied de page
└── assets/style.css          # Style de l'interface
```

## Mini documentation (pour présentation en classe)
**Objectif :** Digitaliser la gestion des étudiants et faciliter leur suivi.

**Technologies :** PHP, MySQL, SQL, PDO, HTML5, CSS3.

**Table :** `etudiants(id, code_apogee, nom, prenom, email, filiere, niveau, date_naissance, created_at)`.

**CRUD :**
- Create : `create.php` + requête `INSERT`
- Read : `index.php` + requête `SELECT`
- Update : `edit.php` + requête `UPDATE`
- Delete : `delete.php` + requête `DELETE`

**Sécurité pédagogique :** requêtes préparées PDO (injection SQL), `htmlspecialchars` (XSS), jetons CSRF (actions POST), contrôles des données côté serveur. Le projet est destiné à un usage local de démonstration : avant une mise en ligne publique, ajoutez une authentification, des rôles et des mesures de déploiement adaptées.

## اختبار سريع بالدارجة
- حل المشروع فـ `localhost`.
- زيد طالب جديد من `+ Nouvel étudiant`، وتأكد بأنه بان فـ اللائحة.
- قلب عليه بالاسم، ومن بعد جرب `Modifier`.
- جرب `Supprimer` وشوف واش تحيد من اللائحة.

**Remarque :** Les adresses `example.com` sont des données fictives pour la démonstration.
