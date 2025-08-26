# API Documentation - Gestion des Dirigeants UFSC

## Vue d'ensemble

Cette documentation décrit la structure des données pour la gestion des dirigeants dans le système d'affiliation des clubs UFSC. Le backend traite quatre types de dirigeants avec des champs séparés pour le nom et le prénom.

## Structure des données dirigeants

### Rôles de dirigeants supportés

- `president` - Président (obligatoire)
- `secretaire` - Secrétaire (obligatoire)  
- `tresorier` - Trésorier (obligatoire)
- `entraineur` - Entraîneur (facultatif)

### Champs pour chaque dirigeant

Pour chaque rôle, les champs suivants sont disponibles :

- `{role}_nom` - Nom de famille (obligatoire pour president, secretaire, tresorier)
- `{role}_prenom` - Prénom (obligatoire pour president, secretaire, tresorier)
- `{role}_email` - Adresse email (obligatoire pour president, secretaire, tresorier)
- `{role}_tel` - Numéro de téléphone (obligatoire pour president, secretaire, tresorier)

## Endpoints API

### 1. Créer un club (POST)

**Endpoint :** `wp-ajax` action `ufsc_save_club`

**Payload d'exemple :**

```json
{
  "action": "ufsc_save_club",
  "ufsc_club_nonce": "abc123...",
  "nom": "ASPTT Tennis Club",
  "region": "UFSC Île-de-France",
  "adresse": "123 Avenue des Sports",
  "code_postal": "75001",
  "ville": "Paris",
  "email": "contact@asptt-tennis.fr",
  "telephone": "01 23 45 67 89",
  "num_declaration": "W751234567",
  "date_declaration": "2023-01-15",
  
  "president_nom": "Dupont",
  "president_prenom": "Jean",
  "president_email": "jean.dupont@email.fr",
  "president_tel": "01 23 45 67 01",
  
  "secretaire_nom": "Martin",
  "secretaire_prenom": "Marie",
  "secretaire_email": "marie.martin@email.fr", 
  "secretaire_tel": "01 23 45 67 02",
  
  "tresorier_nom": "Bernard",
  "tresorier_prenom": "Pierre",
  "tresorier_email": "pierre.bernard@email.fr",
  "tresorier_tel": "01 23 45 67 03",
  
  "entraineur_nom": "Moreau",
  "entraineur_prenom": "Sophie",
  "entraineur_email": "sophie.moreau@email.fr",
  "entraineur_tel": "01 23 45 67 04"
}
```

**Réponse succès :**

```json
{
  "success": true,
  "data": {
    "message": "Club créé avec succès.",
    "club_id": 123,
    "operation": "create",
    "club_data": {
      "id": 123,
      "nom": "ASPTT Tennis Club",
      "president_nom": "Dupont",
      "president_prenom": "Jean",
      "president_email": "jean.dupont@email.fr",
      "president_tel": "01 23 45 67 01",
      // ... autres champs
    },
    "timestamp": "2024-01-15 10:30:00"
  }
}
```

### 2. Mettre à jour un club (POST)

**Endpoint :** `wp-ajax` action `ufsc_save_club`

**Payload d'exemple :**

```json
{
  "action": "ufsc_save_club",
  "ufsc_club_nonce": "abc123...",
  "club_id": 123,
  "nom": "ASPTT Tennis Club Mis à Jour",
  
  "president_nom": "Dupont",
  "president_prenom": "Jean-Pierre",
  "president_email": "jean-pierre.dupont@email.fr",
  "president_tel": "01 23 45 67 01",
  
  // ... autres champs dirigeants
}
```

**Réponse succès :**

```json
{
  "success": true,
  "data": {
    "message": "Club mis à jour avec succès.",
    "club_id": 123,
    "operation": "update",
    "club_data": {
      "id": 123,
      "nom": "ASPTT Tennis Club Mis à Jour",
      "president_prenom": "Jean-Pierre",
      // ... champs mis à jour
    },
    "timestamp": "2024-01-15 11:00:00"
  }
}
```

### 3. Récupérer un club (POST)

**Endpoint :** `wp-ajax` action `ufsc_get_club_data`

**Payload d'exemple :**

```json
{
  "action": "ufsc_get_club_data",
  "club_id": 123,
  "nonce": "xyz789..."
}
```

**Réponse succès :**

```json
{
  "success": true,
  "data": {
    "club_data": {
      "id": 123,
      "nom": "ASPTT Tennis Club",
      "region": "UFSC Île-de-France",
      "adresse": "123 Avenue des Sports",
      "code_postal": "75001",
      "ville": "Paris",
      "email": "contact@asptt-tennis.fr",
      "telephone": "01 23 45 67 89",
      "statut": "Actif",
      "date_creation": "2024-01-15 10:30:00",
      
      "president_nom": "Dupont",
      "president_prenom": "Jean",
      "president_email": "jean.dupont@email.fr",
      "president_tel": "01 23 45 67 01",
      
      "secretaire_nom": "Martin",
      "secretaire_prenom": "Marie",
      "secretaire_email": "marie.martin@email.fr",
      "secretaire_tel": "01 23 45 67 02",
      
      "tresorier_nom": "Bernard",
      "tresorier_prenom": "Pierre",
      "tresorier_email": "pierre.bernard@email.fr",
      "tresorier_tel": "01 23 45 67 03",
      
      "entraineur_nom": "Moreau",
      "entraineur_prenom": "Sophie",
      "entraineur_email": "sophie.moreau@email.fr",
      "entraineur_tel": "01 23 45 67 04"
    },
    "timestamp": "2024-01-15 12:00:00"
  }
}
```

## Validation des données

### Règles de validation côté backend

1. **Champs obligatoires club :**
   - `nom`, `region`, `adresse`, `code_postal`, `ville`, `email`, `telephone`
   - `num_declaration`, `date_declaration`

2. **Champs obligatoires dirigeants :**
   - Pour `president`, `secretaire`, `tresorier` : `nom`, `prenom`, `email`, `tel`
   - Pour `entraineur` : tous les champs sont facultatifs

3. **Format des données :**
   - `email` : format email valide
   - `code_postal` : 5 chiffres
   - `tel` : numéro de téléphone français

### Réponse d'erreur de validation

```json
{
  "success": false,
  "data": {
    "message": "Données invalides.",
    "errors": [
      "Le prénom du président est obligatoire",
      "L'email du secrétaire n'est pas valide",
      "Le téléphone du trésorier est obligatoire"
    ],
    "error_code": "VALIDATION_FAILED"
  }
}
```

## Migration des données

### Structure existante vs nouvelle structure

**Ancienne structure (si applicable) :**
```json
{
  "president_name": "Jean Dupont",
  // nom et prénom combinés
}
```

**Nouvelle structure :**
```json
{
  "president_nom": "Dupont",
  "president_prenom": "Jean"
  // champs séparés
}
```

### Script de migration

Si des données existantes utilisent un format combiné, une migration automatique est appliquée lors de la mise à jour de la base de données via la méthode `apply_database_patches()` dans `UFSC_Club_Manager`.

## Exemples d'utilisation frontend

### JavaScript - Soumission de formulaire

```javascript
// Données du formulaire avec champs séparés
const formData = new FormData();
formData.append('action', 'ufsc_save_club');
formData.append('president_nom', 'Dupont');
formData.append('president_prenom', 'Jean');
formData.append('president_email', 'jean.dupont@email.fr');
formData.append('president_tel', '01 23 45 67 01');

// Soumission AJAX
$.ajax({
  url: ajaxurl,
  type: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(response) {
    if (response.success) {
      console.log('Club sauvegardé:', response.data.club_data);
    }
  }
});
```

### JavaScript - Validation en temps réel

```javascript
// Validation des champs dirigeants
$('input[name$="_nom"], input[name$="_prenom"]').on('input', function() {
  const value = $(this).val().trim();
  const isValid = value.length > 0;
  
  if (isValid) {
    $(this).addClass('valid').removeClass('invalid');
  } else {
    $(this).addClass('invalid').removeClass('valid');
  }
});
```

## Cohérence des données

### Garanties du système

1. **Synchronisation automatique :** Les données sont automatiquement synchronisées entre frontend et backend via AJAX
2. **Validation unifiée :** Les mêmes règles de validation s'appliquent côté client et serveur
3. **Structure cohérente :** Tous les endpoints utilisent la même structure de données
4. **Gestion d'erreurs :** Messages d'erreur cohérents et informatifs

### Points de contrôle qualité

- [ ] Validation JavaScript temps réel active
- [ ] Validation PHP backend robuste  
- [ ] Messages d'erreur clairs et spécifiques
- [ ] Structure de données uniforme dans tous les endpoints
- [ ] Documentation API à jour