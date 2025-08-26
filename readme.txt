=== UFSC Gestion Club ===
Contributors: studioreactiv
Tags: club, management, affiliation, license, ufsc
Requires at least: 5.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin WordPress professionnel pour la gestion des clubs affiliés à l'UFSC.

== Description ==

**Plugin WordPress professionnel pour la gestion des clubs affiliés à l'UFSC**

Gestion complète des documents, licences, affiliations et expérience utilisateur optimisée pour les clubs & administrateurs.

= Fonctionnalités principales =

**Administration (Back-office)**
* Création/édition de clubs avec upload et validation de 6 documents obligatoires (PDF/JPEG/PNG)
* Gestion sécurisée des fichiers (noms anonymisés, stockage protégé, validation taille/type, contrôle d'accès)
* Suivi d'affiliation : Statut visuel (complet/incomplet), validation rapide, génération automatique d'attestation
* Tableau de bord club enrichi : liste, filtres, statuts colorés, actions rapides
* Gestion des licences : Ajout, modification, exportation, gestion des quotas, suivi des paiements
* Tableaux dynamiques (tri/recherche/pagination)

**Front-office (Clubs)**
* Espace club dédié : visualisation du statut, progression des documents (X/6), téléchargements sécurisés
* Alertes visuelles et messages d'aide pour compléter le dossier
* Téléchargement d'attestation après validation
* Actions rapides sur licences et documents
* Interface responsive et design professionnel
* Notifications toast (succès/erreur/action)
* Loader animé pour feedback utilisateur
* Aide contextuelle (tooltips) et navigation fluide

**Accessibilité & Qualité**
* Palette de couleurs accessible (contraste WCAG AA)
* Navigation clavier, focus visible, attributs ARIA
* Sécurité WordPress (nonces, sanitization, validation d'accès)
* Chargement conditionnel des ressources

= Shortcodes disponibles =

**Tableaux de bord et espaces club**
* `[ufsc_club_dashboard]` - Affiche le tableau de bord personnalisé pour chaque club connecté (version classique)
* `[espace_club]` - Affiche l'espace club moderne avec navigation modulaire (version recommandée)

**Formulaires d'affiliation et création**
* `[ufsc_formulaire_affiliation]` - Formulaire d'affiliation club avec intégration WooCommerce et upload de documents
  - Redirige automatiquement vers le produit WooCommerce d'affiliation (ID 4823)
  - Ajoute les métadonnées du club au panier (nom, ID, type de produit)
  - Traite automatiquement les commandes terminées pour mettre à jour le statut du club
* `[ufsc_affiliation_club_form]` - Formulaire d'affiliation club avec upload des 6 documents obligatoires
* `[ufsc_formulaire_club]` - Formulaire de création/édition de club pour le front-end

**Gestion des licences**
* `[ufsc_ajouter_licence]` - Formulaire d'ajout de licence pour les clubs affiliés
* `[ufsc_bouton_licence]` - Bouton d'achat de licence avec informations de quota
  - Redirige vers le produit WooCommerce de licence (ID 2934)
  - Affiche le quota actuel et le nombre de licences restantes
  - Disponible uniquement pour les clubs avec statut "Actif"
  - Applique automatiquement une remise de 100% si le club a encore du quota inclus

**Services et informations**
* `[ufsc_club_attestation]` - Permet aux clubs de télécharger leur attestation d'affiliation
* `[ufsc_liste_clubs]` - Affiche la liste publique des clubs affiliés UFSC

== Installation ==

1. Télécharger le plugin et le placer dans le dossier `wp-content/plugins/`
2. Activer le plugin via l'administration WordPress
3. Configurer les clubs et les documents requis dans la section "UFSC Clubs" du back-office
4. Configurer les produits WooCommerce (voir section Configuration WooCommerce ci-dessous)
5. Créer les pages front-end nécessaires (voir guide ci-dessous)

== Configuration WooCommerce ==

Le plugin UFSC Gestion Club s'intègre avec WooCommerce pour gérer les achats d'affiliations et de licences. Deux produits WooCommerce spécifiques doivent être configurés avec des IDs précis.

= IDs de produits requis =

**Produit d'affiliation UFSC**
* ID requis : `4823`
* Constante : `UFSC_AFFILIATION_PRODUCT_ID`
* Option alternative : `ufsc_affiliation_product_id`
* Utilisation : Pack d'affiliation club (1 an + 10 licences incluses)

**Produit des licences UFSC**
* ID requis : `2934`
* Constante : `UFSC_LICENCE_PRODUCT_ID`
* Utilisation : Achat de licences supplémentaires

= Configuration dans l'administration WordPress =

**Méthode 1 : Création directe des produits**
1. Aller dans `WooCommerce > Produits > Ajouter`
2. Créer le produit d'affiliation avec l'ID 4823 :
   - Titre : "Pack Affiliation Club UFSC"
   - Description : "Affiliation du club pour 1 an + 10 licences incluses"
   - Type : Produit simple
   - Prix : Définir selon votre tarification
3. Créer le produit de licence avec l'ID 2934 :
   - Titre : "Licence UFSC"
   - Description : "Licence individuelle UFSC"
   - Type : Produit simple
   - Prix : Définir selon votre tarification

**Méthode 2 : Configuration via base de données**
Si vous devez modifier les IDs de produits existants :

```sql
-- Modifier l'ID d'un produit existant pour l'affiliation
UPDATE wp_posts SET ID = 4823 WHERE post_type = 'product' AND post_title = 'Votre Produit Affiliation';

-- Modifier l'ID d'un produit existant pour les licences  
UPDATE wp_posts SET ID = 2934 WHERE post_type = 'product' AND post_title = 'Votre Produit Licence';

-- Mettre à jour les métadonnées associées
UPDATE wp_postmeta SET post_id = 4823 WHERE post_id = 'ancien_id_affiliation';
UPDATE wp_postmeta SET post_id = 2934 WHERE post_id = 'ancien_id_licence';
```

**Méthode 3 : Option WordPress (pour l'affiliation uniquement)**
Vous pouvez définir l'ID du produit d'affiliation via une option WordPress :
```php
// Dans votre functions.php ou via un plugin
update_option('ufsc_affiliation_product_id', 4823);
```

= Test de la configuration =

**Vérifier les liens d'achat d'affiliation**
1. Accéder à une page contenant le shortcode `[ufsc_formulaire_affiliation]`
2. Remplir le formulaire d'affiliation
3. Vérifier que le bouton d'achat redirige vers `/produit/pack-affiliation-club-ufsc/` (ou l'URL de votre produit ID 4823)
4. Vérifier que le produit se trouve bien dans le panier avec les métadonnées du club

**Vérifier les liens d'achat de licence**
1. Se connecter en tant qu'utilisateur d'un club actif
2. Accéder à une page contenant le shortcode `[ufsc_bouton_licence]`
3. Vérifier que le bouton "Demander une licence" redirige vers `/produit/licence-ufsc/` (ou l'URL de votre produit ID 2934)
4. Vérifier l'affichage correct du quota de licences restantes

**Tests de fonctionnement complet**
1. Effectuer un achat d'affiliation et vérifier que le statut du club passe à "En attente de validation"
2. Effectuer un achat de licence et vérifier que la licence est ajoutée au club
3. Vérifier les métadonnées de commande WooCommerce (ufsc_club_id, ufsc_club_nom, ufsc_product_type)

= Dépannage =

**Erreur : Produit non trouvé**
- Vérifier que les produits avec les IDs 4823 et 2934 existent dans WooCommerce
- Vérifier que les produits sont publiés et non en brouillon

**Les boutons ne redirigent pas correctement**
- Vider le cache des permaliens : `Réglages > Permaliens > Enregistrer`
- Vérifier la configuration des URLs de WooCommerce

**Les métadonnées de commande ne s'enregistrent pas**
- Vérifier que les hooks WooCommerce sont bien chargés
- Contrôler les logs d'erreur PHP pour d'éventuels conflits

== Guide de création des pages front-end ==

Ce guide détaille comment créer et configurer les pages WordPress pour offrir une interface complète aux clubs affiliés.

= Étapes de création des pages =

**1. Créer les pages dans WordPress**
* Aller dans `Pages > Ajouter`
* Créer une page pour chaque fonctionnalité souhaitée
* Insérer le shortcode correspondant dans le contenu de la page
* Publier la page

**2. Configurer les menus**
* Aller dans `Apparence > Menus`
* Créer un menu "Espace Club" ou ajouter aux menus existants
* Ajouter les pages créées au menu
* Assigner le menu à l'emplacement souhaité

= Pages recommandées et shortcodes =

**Page principale : Espace Club**
* Nom suggéré : "Mon Espace Club"
* Shortcode : `[espace_club]` (recommandé) ou `[ufsc_club_dashboard]`
* Description : Tableau de bord principal avec navigation modulaire
* Accès : Utilisateurs connectés uniquement

**Page d'affiliation**
* Nom suggéré : "Affiliation Club"
* Shortcode : `[ufsc_formulaire_affiliation]`
* Description : Formulaire complet d'affiliation avec upload de documents et intégration WooCommerce
* Accès : Utilisateurs connectés sans club existant
* Comportement : Après soumission, redirige automatiquement vers le panier WooCommerce avec le produit d'affiliation (ID 4823)

**Page de création de club**
* Nom suggéré : "Créer un Club"
* Shortcode : `[ufsc_formulaire_club]`
* Description : Formulaire de création/modification de club
* Accès : Utilisateurs connectés

**Page d'ajout de licencié**
* Nom suggéré : "Ajouter un Licencié"
* Shortcode : `[ufsc_ajouter_licencie]`
* Description : Formulaire intégré d'ajout de licencié avec gestion de l'achat si quota épuisé
* Accès : Membres de clubs affiliés uniquement

**Page d'achat de licence (optionnelle)**
* Nom suggéré : "Acheter des Licences"
* Shortcode : `[ufsc_bouton_licence]`
copilot/fix-94b81ebb-63aa-4a16-a8cb-b6b0fb6e5e1e
* Description : Bouton d'achat avec informations de quota (peut être utilisé séparément si nécessaire)
=======
* Description : Bouton d'achat avec informations de quota et intégration WooCommerce
 main
* Accès : Clubs affiliés actifs
* Comportement : Redirige vers le produit WooCommerce de licence (ID 2934), applique automatiquement les remises selon le quota disponible

**Page des attestations**
* Nom suggéré : "Mes Attestations"
* Shortcode : `[ufsc_club_attestation]`
* Description : Téléchargement d'attestation d'affiliation
* Accès : Clubs affiliés validés

**Page publique des clubs**
* Nom suggéré : "Nos Clubs Affiliés"
* Shortcode : `[ufsc_liste_clubs]`
* Description : Liste publique des clubs affiliés
* Accès : Public (tous les visiteurs)

= Exemple de structure de menu =

```
Espace Club
├── Mon Espace Club [espace_club]
├── Ajouter un Licencié [ufsc_ajouter_licencie]
└── Mes Attestations [ufsc_club_attestation]

Pages d'inscription/affiliation
├── Créer un Club [ufsc_formulaire_club]
└── Affiliation Club [ufsc_formulaire_affiliation]

Pages publiques
└── Nos Clubs Affiliés [ufsc_liste_clubs]

Pages optionnelles (si besoin d'un achat séparé)
└── Acheter des Licences [ufsc_bouton_licence]
```

= Restrictions d'accès recommandées =

**Pour les pages de club (espace membre)**
* Utiliser un plugin de restriction comme "Members" ou "Restrict Content Pro"
* Ou ajouter du code dans functions.php pour rediriger les non-connectés
* Exemple : `if (!is_user_logged_in()) { wp_redirect(wp_login_url()); exit; }`

**Pour les pages d'administration de club**
* Restreindre aux utilisateurs ayant un club affilié
* Les shortcodes gèrent automatiquement ces restrictions

**Pour les pages publiques**
* Laisser accessible à tous (comme la liste des clubs)
* Aucune restriction nécessaire

= Conseils de configuration =

* **URLs conviviales** : Utiliser des slugs clairs (ex: `/espace-club/`, `/affiliation/`)
* **Navigation cohérente** : Créer un menu dédié "Espace Club" pour les pages membre
* **Page d'accueil club** : Faire de `[espace_club]` la page principale d'atterrissage
* **Messages d'aide** : Ajouter des textes explicatifs au-dessus des shortcodes si nécessaire
* **Responsive** : Les shortcodes sont optimisés pour mobile, vérifier l'affichage sur tous les appareils

== Frequently Asked Questions ==

= Comment configurer les clubs ? =

Rendez-vous dans la section "UFSC Clubs" de votre administration WordPress et suivez les instructions de configuration.

= Comment configurer WooCommerce pour le plugin ? =

Le plugin nécessite deux produits WooCommerce spécifiques avec les IDs 4823 (affiliation) et 2934 (licences). Consultez la section "Configuration WooCommerce" pour les instructions détaillées de configuration et de test.

= Les achats ne fonctionnent pas, que faire ? =

Vérifiez que :
1. Les produits WooCommerce avec les IDs 4823 et 2934 existent et sont publiés
2. Les permaliens sont à jour (Réglages > Permaliens > Enregistrer)
3. WooCommerce est correctement configuré avec les pages panier et commande

= Les données sont-elles sécurisées ? =

Oui, le plugin utilise les standards de sécurité WordPress avec validation stricte des fichiers, nonces et capacités.

== Screenshots ==

1. Tableau de bord d'administration des clubs
2. Interface front-office pour les clubs
3. Gestion des licences et affiliations

== Changelog ==

= 1.2.0 =
* Nouvelle page de réglages graphique pour lier les pages clés (espace club, licences, affiliation, attestations) et les produits WooCommerce
* Sélection graphique des IDs produits WooCommerce (Affiliation, Licence)
* Workflow de gestion des licences amélioré : brouillon > paiement WooCommerce > validation admin
* Harmonisation du design, accessibilité, responsive et sécurité accrue sur l'admin et le front
* Gestion des documents clubs via la médiathèque
* Meilleure documentation interne pour les administrateurs
* Expérience admin et utilisateur professionnelle, graphique, sécurisée et pratique

= 1.0.2 =
* Améliorations UX/UI continues
* Conformité aux standards WordPress.org
* Suppression des dépendances CDN externes
* Amélioration de la sécurité et validation

= 1.0.1 =
* Corrections mineures et optimisations

= 1.0.0 =
* Version initiale du plugin

== Upgrade Notice ==

= 1.2.0 =
Version majeure avec nouvelle interface d'administration graphique, workflow de licences amélioré, et intégration WooCommerce renforcée. Mise à jour fortement recommandée.

= 1.0.2 =
Cette version améliore la conformité aux standards WordPress.org et la sécurité. Mise à jour recommandée.