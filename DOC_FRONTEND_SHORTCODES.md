# Documentation des Shortcodes Frontend UFSC

Ce document présente les shortcodes disponibles pour le frontend du plugin UFSC Gestion Club, après la refonte v1.3.0.

## Shortcodes Principaux

### [ufsc_licence_form]

Affiche le formulaire de nouvelle licence. Si utilisé hors de la page produit, redirige vers le produit WooCommerce.

**Attributs :**
- `redirect_to_product` (yes/no) - Par défaut: yes - Redirige vers la page produit WooCommerce
- `show_title` (yes/no) - Par défaut: yes - Affiche le titre du formulaire
- `button_text` (string) - Texte personnalisé pour le bouton de soumission

**Exemples :**
```
[ufsc_licence_form]
[ufsc_licence_form redirect_to_product="no" show_title="no"]
[ufsc_licence_form button_text="Ajouter cette licence"]
```

### [ufsc_affiliation_form]

Affiche le formulaire d'affiliation club. Détecte automatiquement s'il s'agit d'une nouvelle affiliation ou d'un renouvellement.

**Attributs :**
- `redirect_to_product` (yes/no) - Par défaut: yes - Redirige vers la page produit WooCommerce
- `show_title` (yes/no) - Par défaut: yes - Affiche le titre du formulaire
- `button_text` (string) - Texte personnalisé pour le bouton de soumission

**Exemples :**
```
[ufsc_affiliation_form]
[ufsc_affiliation_form show_title="no" button_text="Procéder au paiement"]
```

### [ufsc_club_quota]

Affiche les informations de quota de licences du club de l'utilisateur connecté.

**Attributs :**
- `show_title` (yes/no) - Par défaut: yes - Affiche le titre
- `format` (card/inline/progress) - Par défaut: card - Format d'affichage

**Exemples :**
```
[ufsc_club_quota]
[ufsc_club_quota format="progress" show_title="no"]
[ufsc_club_quota format="inline"]
```

### [ufsc_club_stats]

Affiche les statistiques et KPIs du club de l'utilisateur connecté.

**Attributs :**
- `show_title` (yes/no) - Par défaut: yes - Affiche le titre
- `layout` (cards/list/inline) - Par défaut: cards - Disposition des statistiques
- `include` (string) - Par défaut: "licenses,affiliation,status" - Statistiques à inclure (séparées par des virgules)

**Exemples :**
```
[ufsc_club_stats]
[ufsc_club_stats layout="list" include="licenses,status"]
[ufsc_club_stats layout="inline" show_title="no"]
```

### [ufsc_license_list]

Affiche une liste filtrée et paginée des licences du club de l'utilisateur connecté.

**Attributs :**
- `per_page` (number) - Par défaut: 25 - Nombre de licences par page
- `status` (string) - Filtrer par statut spécifique (pending, validated, refused)
- `search` (string) - Terme de recherche pré-rempli
- `show_filters` (yes/no) - Par défaut: yes - Affiche les filtres de recherche
- `show_pagination` (yes/no) - Par défaut: yes - Affiche la pagination
- `show_actions` (yes/no) - Par défaut: yes - Affiche les boutons d'action

**Exemples :**
```
[ufsc_license_list]
[ufsc_license_list per_page="10" status="validated"]
[ufsc_license_list show_filters="no" show_actions="no"]
```

## Shortcodes Existants (Maintenus)

### [ufsc_club_dashboard] / [espace_club]
Affiche le tableau de bord complet du club (modernisé dans v1.3.0).

### [ufsc_licence_button] / [ufsc_bouton_licence]
Bouton pour accéder au formulaire de licence (intégré WooCommerce).

### [ufsc_club_menu]
Menu de navigation du club avec détection de page active.

### [ufsc_club_attestation] / [ufsc_attestation_form]
Formulaires et boutons de téléchargement d'attestations.

### [ufsc_liste_clubs]
Liste publique des clubs affiliés.

### [ufsc_affiliation_club_form] / [ufsc_formulaire_club]
Formulaire d'affiliation club (version standalone).

## Fonctionnalités Techniques

### Intégration WooCommerce
- Les formulaires `ufsc_licence_form` et `ufsc_affiliation_form` s'intègrent automatiquement aux produits WooCommerce (IDs 2933 et 2934)
- Gestion automatique du panier avec métadonnées
- Synchronisation des statuts commande ↔ licence
- Prévention des doublons et gestion des quotas

### Gestion des Statuts
- **Draft** : Licence en brouillon
- **Pending** : En attente de validation
- **Validated** : Licence validée
- **Refused** : Licence refusée
- **Revoked** : Licence révoquée

### Sécurité
- Vérification des nonces sur tous les formulaires AJAX
- Contrôle d'accès basé sur l'association club/utilisateur
- Validation côté client et serveur
- Sanitisation de toutes les données

### Responsive Design
- Tous les composants sont adaptatifs (mobile-first)
- CSS isolé avec préfixes `.ufsc-`
- Chargement conditionnel des assets

## Configuration

### Options Plugin
- `ufsc_licence_product_id` : ID du produit WooCommerce pour les licences (défaut: 2934)
- `ufsc_affiliation_product_id` : ID du produit WooCommerce pour les affiliations (défaut: 2933)
- `ufsc_manual_validation` : Active la validation manuelle des licences (défaut: false)

### Pages Requises
Pour un fonctionnement optimal, configurez ces pages dans les réglages UFSC :
- Page Espace Club (Dashboard)
- Page Affiliation
- Page Licences
- Page Attestations

## Exemples d'Utilisation

### Page d'Accueil Club
```
[ufsc_club_dashboard]
```

### Page Statistiques
```
[ufsc_club_stats layout="cards"]
[ufsc_club_quota format="progress"]
```

### Page Licences Complète
```
<h2>Gestion des Licences</h2>
[ufsc_club_quota]
[ufsc_licence_form redirect_to_product="yes"]
[ufsc_license_list per_page="20"]
```

### Widget Quota (Sidebar)
```
[ufsc_club_quota format="inline" show_title="no"]
```

## Support et Maintenance

### Rétrocompatibilité
Tous les shortcodes existants continuent de fonctionner. Les alias sont maintenus :
- `[ufsc_licence_button]` ↔ `[ufsc_bouton_licence]`
- `[ufsc_club_licences]` ↔ `[ufsc_club_licenses]`
- `[ufsc_attestation_form]` ↔ `[ufsc_club_attestation]`

### Performance
- Chargement conditionnel CSS/JS basé sur la détection de shortcodes
- Assets minifiés en production
- Optimisation AJAX avec cache côté client

### Debug
En mode WP_DEBUG, des logs détaillés sont disponibles pour le débogage des formulaires et intégrations WooCommerce.