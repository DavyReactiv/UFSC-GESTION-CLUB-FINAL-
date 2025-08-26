# Correction du comportement des pages d'édition des clubs et des licences

## Problème résolu

Auparavant, lorsque l'on cliquait sur le bouton "Modifier" depuis la fiche d'un club, l'affichage basculait vers une page de gestion des documents au lieu d'afficher un formulaire d'édition des informations générales et légales du club.

## Solution implémentée

### Pour les clubs

1. **Modification du bouton "Modifier"**
   - Le bouton "Modifier" dans la liste des clubs (`includes/views/admin-club-list.php`) pointe maintenant vers `ufsc_view_club&id=X&edit=1`
   - Cela déclenche le mode édition sur la page de détails du club

2. **Page de détails du club avec mode édition**
   - La méthode `render_view_club_page()` dans `includes/admin/class-menu.php` a été améliorée
   - Détection du paramètre `&edit=1` pour basculer en mode édition
   - Affichage conditionnel : formulaire d'édition ou vue en lecture seule

3. **Fonctionnalités ajoutées**
   - Formulaire complet d'édition avec tous les champs (informations générales, légales, dirigeants)
   - Validation côté serveur et côté client
   - Messages de succès/erreur
   - Boutons de navigation entre les modes

### Pour les licences

Les licences fonctionnaient déjà correctement :
- Le bouton "Modifier" pointe vers `ufsc-modifier-licence&licence_id=X`
- Cette page charge directement le formulaire d'édition complet
- Aucune modification nécessaire

## Sécurité

- Vérification des permissions (`manage_options`)
- Protection CSRF avec nonces WordPress
- Sanitisation de toutes les données saisies
- Validation des formats (email, code postal, SIREN)

## Navigation

### Clubs
- **Mode lecture** : Voir → Modifier (edit=1) → Gestion des documents
- **Mode édition** : Retour lecture → Annuler/Sauvegarder

### Licences
- **Mode lecture** : Voir → Modifier (page dédiée)
- Les licences gardent leur système existant qui fonctionnait déjà

## Paramètres URL

- `&edit=1` : Active le mode édition pour les clubs
- Les permissions d'administration sont requises pour accéder aux modes d'édition

## Validation

### Côté serveur (PHP)
- Champs obligatoires
- Format du code postal (5 chiffres)
- Format du SIREN (9 chiffres)
- Validation des emails

### Côté client (JavaScript)
- Validation en temps réel
- Messages d'erreur visuels
- Prévention de soumission de formulaires invalides