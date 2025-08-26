# WooCommerce E-commerce Features Documentation

## Vue d'ensemble

Cette mise à jour ajoute deux nouvelles fonctionnalités e-commerce au plugin UFSC Gestion Club pour améliorer la traçabilité comptable et automatiser les processus d'achat.

## Fonctionnalités implémentées

### 1. Auto-ajout du Pack 10 licences avec Affiliation

**Objectif**: Garantir automatiquement le total de 500€ (Affiliation 150€ + Pack 10 licences 350€)

**Comportement**:
- Lorsqu'un utilisateur ajoute le produit "Affiliation" au panier, le produit "Pack 10 licences" est automatiquement ajouté
- Si l'Affiliation est retirée du panier, le Pack 10 licences lié est également retiré
- Filet de sécurité: avant calcul des totaux, vérification et réinjection du Pack 10 si manquant
- Fonctionnalité activable/désactivable via réglage admin (par défaut: activé)

**Fichier**: `includes/woocommerce/auto-pack-affiliation.php`

**Hooks WooCommerce utilisés**:
- `woocommerce_add_to_cart`: Ajouter le Pack quand Affiliation ajoutée
- `woocommerce_cart_item_removed`: Retirer le Pack quand Affiliation retirée  
- `woocommerce_before_calculate_totals`: Filet de sécurité pour réinjecter le Pack

### 2. Commande automatique pour licences créées en admin

**Objectif**: Assurer une traçabilité comptable complète pour toutes les licences payantes

**Comportement**:
- Lorsqu'une licence est créée côté admin avec `is_included=0` (non incluse dans le quota)
- Création automatique d'une commande WooCommerce contenant le produit "Licence individuelle" (35€)
- Client = responsable du club si disponible, sinon commande invité
- Métadonnées de liaison: `licence_id`, `club_id`, `ufsc_auto_created`
- Commande laissée en statut `pending` pour paiement standard
- Fonctionnalité activable/désactivable via réglage admin (par défaut: activé)

**Fichier**: `includes/woocommerce/auto-order-admin-licences.php`

**Action hook**: `ufsc_licence_created` (ajouté dans `class-licence-manager.php`)

## Configuration admin

### Nouveaux réglages WooCommerce

Accessibles via `Réglages > UFSC > Intégration WooCommerce`:

1. **ID Produit Pack 10 Licences** (`ufsc_wc_pack_10_product_id`)
   - ID du produit WooCommerce Pack 10 licences (350€)
   - Requis pour l'auto-ajout avec affiliation

2. **ID Produit Licence Individuelle** (`ufsc_wc_individual_licence_product_id`)
   - ID du produit WooCommerce Licence individuelle (35€)
   - Requis pour les commandes automatiques

3. **Auto-ajout Pack 10 avec Affiliation** (`ufsc_auto_pack_enabled`)
   - Checkbox pour activer/désactiver l'auto-ajout du Pack 10
   - Par défaut: activé

4. **Commande auto pour licences admin** (`ufsc_auto_order_for_admin_licences`)
   - Checkbox pour activer/désactiver les commandes automatiques
   - Par défaut: activé

### Réglages existants conservés

- **ID Produit Affiliation** (`ufsc_wc_affiliation_product_id`): 2933
- **IDs Produits Licences** (`ufsc_wc_license_product_ids`): 2934

## Structure des prix

- **Affiliation**: 150€
- **Pack 10 licences**: 350€
- **Total affiliation + pack**: 500€
- **Licence individuelle**: 35€

## Tests automatiques

**Fichier**: `includes/tests/woocommerce-ecommerce-test.php`

**Tests inclus**:
- Vérification de l'enregistrement des nouveaux réglages
- Existence et initialisation des classes auto-pack et auto-order
- Fonctionnement du hook d'action `ufsc_licence_created`
- Présence des nouvelles méthodes de rendu dans les réglages admin

**Exécution**: Automatique en mode debug (`WP_DEBUG = true`)

## Points d'intégration

### Métadonnées de liaison

**Panier/Commande affiliation + pack**:
- `ufsc_linked_to_affiliation`: Clé du panier affiliation
- `ufsc_auto_added_pack`: Marqueur pack auto-ajouté

**Commande automatique licence**:
- `ufsc_licence_id`: ID de la licence
- `ufsc_club_id`: ID du club
- `ufsc_auto_created`: Marqueur commande auto-créée
- `ufsc_licence_name`: Nom complet du licencié

### Sécurité et validation

- Vérification que WooCommerce est actif avant initialisation
- Validation des IDs produits avant traitement
- Évitement des boucles infinies dans le filet de sécurité
- Gestion des erreurs avec logging

## Installation et activation

Les nouvelles fonctionnalités sont automatiquement actives lors de la mise à jour du plugin, sous réserve que:

1. WooCommerce soit installé et activé
2. Les IDs produits soient configurés dans les réglages
3. Les fonctionnalités soient activées (par défaut: oui)

## Compatibilité

- **WordPress**: 5.5+
- **WooCommerce**: 3.0+
- **PHP**: 7.4+

## Dépannage

### Vérifications préalables

1. WooCommerce est-il installé et activé ?
2. Les IDs produits sont-ils correctement configurés ?
3. Les produits existent-ils dans WooCommerce ?
4. Les fonctionnalités sont-elles activées dans les réglages ?

### Debug

Activer `WP_DEBUG = true` pour:
- Exécution automatique des tests
- Logging des créations de commandes automatiques
- Vérification de l'intégrité des classes et hooks

### Logs

Les événements suivants sont loggés:
- Erreurs de création de commandes automatiques
- Création réussie de commandes automatiques
- Résultats des tests automatiques (en mode debug)