# Association Utilisateur WordPress → Club - Documentation

Cette fonctionnalité permet d'associer un utilisateur WordPress à un club, tant depuis l'interface d'administration que lors de l'affiliation frontend, garantissant un contrôle d'accès sécurisé pour l'espace club.

## 📋 Fonctionnalités implémentées

### 1. Interface d'administration

#### Formulaire de club (Création/Modification)
- **Emplacement** : `includes/clubs/form-club.php`
- **Champ ajouté** : "Utilisateur WordPress associé" (visible uniquement en admin)
- **Fonctionnalités** :
  - Liste déroulante avec tous les utilisateurs WordPress
  - Affichage : Nom d'affichage (login) - email
  - Prévention des associations multiples (utilisateurs déjà associés sont désactivés)
  - Affichage de l'utilisateur actuellement associé

#### Liste des clubs
- **Emplacement** : `includes/clubs/admin-club-list-page.php`
- **Colonne ajoutée** : "Utilisateur associé"
- **Affichage** :
  - Nom d'affichage de l'utilisateur
  - Login WordPress
  - Adresse email
  - Gestion des cas d'erreur (utilisateur supprimé, aucun utilisateur)

#### Profil utilisateur WordPress
- **Emplacement** : `includes/admin/user-profile-enhancement.php`
- **Champ ajouté** : "Club associé" dans la page d'édition d'utilisateur
- **Fonctionnalités** :
  - Liste déroulante avec tous les clubs disponibles
  - Synchronisation bidirectionnelle avec la table des clubs
  - Validation de l'unicité (un utilisateur = un club, un club = un utilisateur)
  - Gestion transactionnelle pour éviter les incohérences

### 2. Interface frontend (Affiliation)

#### Création d'utilisateur pendant l'affiliation
- **Emplacement** : `includes/clubs/form-club.php` (section frontend)
- **Options disponibles** :
  - **Utiliser le compte actuel** : Associe l'utilisateur connecté au club
  - **Créer un nouveau compte** : Crée automatiquement un utilisateur WordPress
  - **Associer un compte existant** : Sélectionne un utilisateur existant non associé
- **Fonctionnalités** :
  - Interface JavaScript responsive avec champs conditionnels
  - Création automatique de mot de passe et envoi par email
  - Validation des doublons (nom d'utilisateur et email)
  - Prévention des associations multiples

### 3. Validation et sécurité

#### Contrôleur de sauvegarde
- **Emplacement** : `includes/controllers/save-club.php`
- **Validations ajoutées** :
  - Vérification de l'existence de l'utilisateur sélectionné
  - Prévention des associations multiples avec message d'erreur explicite
  - Gestion des permissions admin uniquement pour modification
  - Support création et modification de clubs
  - Gestion différenciée frontend/backend

#### Fonctions helper
- **Emplacement** : `includes/helpers.php`
- **Nouvelles fonctions** :
  - `ufsc_handle_frontend_user_association()` : Gère l'association frontend
  - `ufsc_create_user_for_club()` : Crée un nouvel utilisateur WordPress
  - `ufsc_get_all_clubs_for_user_association()` : Récupère les clubs pour association
  - `ufsc_get_club_by_id()` : Récupère un club par ID
  - `ufsc_get_user_display_name()` : Récupère le nom d'affichage d'un utilisateur

### 4. Base de données

#### Utilisation du champ existant
- **Table** : `wp_ufsc_clubs`
- **Champ** : `responsable_id` (int, nullable)
- **Fonctionnement** : Stocke l'ID de l'utilisateur WordPress associé au club
- **Synchronisation bidirectionnelle** : Mise à jour automatique des deux côtés (club → utilisateur et utilisateur → club)

## 🚀 Utilisation

### Pour les administrateurs

1. **Associer un utilisateur à un club** :
   - **Option A - Via le club :**
     - Aller dans "UFSC" → "Liste des clubs"
     - Cliquer sur "Modifier" pour un club
     - Dans la section "Informations générales", sélectionner un utilisateur dans "Utilisateur WordPress associé"
     - Sauvegarder
   
   - **Option B - Via l'utilisateur :**
     - Aller dans "Utilisateurs" → "Tous les utilisateurs"
     - Cliquer sur "Modifier" pour un utilisateur
     - Dans la section "Association Club UFSC", sélectionner un club
     - Sauvegarder

2. **Créer un nouveau club avec utilisateur** :
   - Aller dans "UFSC" → "Ajouter un club"
   - Remplir les informations du club
   - Sélectionner un utilisateur dans "Utilisateur WordPress associé"
   - Sauvegarder

3. **Voir les associations** :
   - Dans "UFSC" → "Liste des clubs", la colonne "Utilisateur associé" affiche l'utilisateur lié à chaque club
   - Dans "Utilisateurs" → "Tous les utilisateurs", l'information de club associé apparaît dans la fiche utilisateur

### Pour les clubs (affiliation frontend)

1. **Lors de l'affiliation d'un nouveau club** :
   - Remplir le formulaire d'affiliation
   - Dans la section "Utilisateur WordPress pour gérer le club", choisir une option :
     - **"Utiliser mon compte actuel"** : Votre compte sera associé au club
     - **"Créer un nouveau compte utilisateur"** : Un nouveau compte sera créé avec les informations fournies
     - **"Associer un compte existant"** : Sélectionner un utilisateur existant dans la liste
   - Finaliser l'affiliation (paiement WooCommerce)
   - L'association sera créée automatiquement

2. **Important** : Une fois le club créé via le frontend, l'association utilisateur ne peut plus être modifiée que par un administrateur dans le back-office.

### Pour les utilisateurs WordPress

1. **Accès à l'espace club** :
   - Une fois associé par un admin ou lors de l'affiliation, l'utilisateur peut accéder à l'espace club frontend
   - L'accès est automatiquement vérifié via `ufsc_verify_club_access()`
   - Fonctionnalités disponibles : consultation des documents, gestion des licences, etc.

## 🔒 Sécurité et contrôles

### Validations mises en place

1. **Unicité de l'association** :
   - Un utilisateur ne peut être associé qu'à un seul club
   - Tentative d'association multiple → erreur explicite
   - Vérification lors de la sauvegarde

2. **Permissions** :
   - Seuls les administrateurs peuvent gérer les associations
   - Champ visible uniquement en interface admin
   - Vérification `manage_options` dans le contrôleur

3. **Validation des données** :
   - Vérification de l'existence de l'utilisateur
   - Sanitisation des données
   - Gestion des cas d'erreur

### Contrôle d'accès frontend

L'accès frontend utilise la fonction existante `ufsc_verify_club_access()` qui :
- Vérifie si l'utilisateur est connecté
- Récupère le club associé via `ufsc_get_user_club()`
- Compare l'ID du club demandé avec celui de l'utilisateur

## 🧪 Test et validation

### Test automatisé
- **Fichier** : `includes/tests/user-club-association-enhancement-test.php`
- **Exécution** : Ajouter `?run_ufsc_association_test=1` à l'URL d'admin
- **Tests inclus** :
  - Existence des fonctions helper nouvelles et existantes
  - Vérification des hooks WordPress pour profil utilisateur
  - Vérification du schéma de base de données (champ responsable_id)
  - Test des fonctions de validation et récupération de données
  - Test de récupération des utilisateurs et clubs

### Test manuel

1. **Test de l'association via le profil utilisateur** :
   - Aller dans "Utilisateurs" → "Modifier un utilisateur"
   - Vérifier la présence du champ "Club associé"
   - Sélectionner un club et sauvegarder
   - Vérifier que l'association apparaît dans la fiche club correspondante

2. **Test de l'affiliation frontend avec création d'utilisateur** :
   - Aller sur la page d'affiliation frontend
   - Remplir le formulaire club
   - Choisir "Créer un nouveau compte utilisateur"
   - Remplir les informations utilisateur
   - Soumettre et vérifier la création de l'utilisateur et l'association

3. **Test de la prévention des doublons** :
   - Essayer d'associer le même utilisateur à deux clubs différents
   - Vérifier le message d'erreur approprié
   - Essayer de créer un utilisateur avec un login/email existant
   - Vérifier les messages d'erreur

4. **Test de l'accès frontend** :
   - Se connecter avec l'utilisateur associé
   - Accéder à l'espace club
   - Vérifier les permissions et l'accès aux fonctionnalités

5. **Test de synchronisation bidirectionnelle** :
   - Modifier l'association depuis le profil utilisateur
   - Vérifier que le changement apparaît dans la fiche club
   - Modifier l'association depuis la fiche club
   - Vérifier que le changement apparaît dans le profil utilisateur

## 📝 Notes techniques

### Compatibilité
- Compatible avec l'architecture existante
- Utilise le champ `responsable_id` déjà présent en BDD
- Intégration transparente avec le système d'accès frontend

### Performance
- Requêtes optimisées avec `wpdb->prepare()`
- Récupération des utilisateurs mise en cache par WordPress
- Validation côté serveur pour éviter les requêtes inutiles

### Maintenabilité
- Code modulaire avec fonctions helper réutilisables
- Documentation inline complète
- Tests automatisés pour validation continue

## 🔧 Intégration

### Aucune action requise
L'intégration est automatique car :
- Utilise les tables existantes
- S'appuie sur les fonctions déjà présentes
- Compatible avec le flux existant
- Les hooks WordPress se chargent automatiquement

### Si personnalisation nécessaire
- **Frontend** : Modifier `includes/clubs/form-club.php` pour l'interface d'affiliation
- **Backend** : Adapter `includes/admin/user-profile-enhancement.php` pour les profils utilisateur
- **Validation** : Ajuster `includes/controllers/save-club.php` pour la logique de sauvegarde
- **Helpers** : Modifier `includes/helpers.php` pour les fonctions utilitaires
- **Styles** : Personnaliser `assets/css/frontend.css` pour l'apparence
- **JavaScript** : Adapter `assets/js/frontend.js` pour les interactions

### Hooks disponibles pour développement
- `ufsc_before_user_club_association` : Avant association utilisateur-club
- `ufsc_after_user_club_association` : Après association utilisateur-club
- Les hooks WordPress standard pour profils utilisateur sont utilisés

### Structure des fichiers
```
includes/
├── admin/
│   └── user-profile-enhancement.php    # Gestion profil utilisateur WordPress
├── clubs/
│   └── form-club.php                   # Formulaire club (frontend et admin)
├── controllers/
│   └── save-club.php                   # Contrôleur de sauvegarde
├── helpers.php                         # Fonctions utilitaires
└── tests/
    └── user-club-association-enhancement-test.php  # Tests automatisés
```