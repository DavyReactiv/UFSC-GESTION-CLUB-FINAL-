# Guide de déploiement UFSC Plugin

Ce guide détaille les étapes pour déployer le plugin UFSC Gestion Club en production de manière sécurisée et efficace.

## 📋 Checklist de pré-déploiement

### ✅ Préparation
- [ ] Vérifier que tous les tests passent en local
- [ ] S'assurer que la version est correctement incrémentée dans `Plugin_UFSC_GESTION_CLUB_13072025.php`
- [ ] Valider que tous les fichiers nécessaires sont présents
- [ ] Vérifier la compatibilité WordPress et PHP

### ✅ Sauvegarde
- [ ] Effectuer une sauvegarde complète de la base de données
- [ ] Sauvegarder les fichiers WordPress (wp-content/plugins/)
- [ ] Documenter la version actuelle du plugin
- [ ] Tester la procédure de restauration

### ✅ Tests en staging
- [ ] Déployer sur environnement de staging
- [ ] Tester toutes les fonctionnalités principales
- [ ] Vérifier les formulaires frontend
- [ ] Valider l'intégration WooCommerce
- [ ] Contrôler les exports CSV
- [ ] Tester les rôles et permissions

## 🚀 Procédure de déploiement

### Méthode 1 : Déploiement via ZIP (recommandé)

```bash
# 1. Préparer l'archive
cd /path/to/plugin
git archive --format=zip --prefix=plugin-ufsc-gestion-club/ HEAD > ufsc-plugin-v1.2.1.zip

# 2. Télécharger via l'admin WordPress
# - Aller dans Extensions > Ajouter
# - Téléverser le fichier ZIP
# - Activer le plugin
```

### Méthode 2 : Déploiement SSH/SFTP

```bash
# 1. Se connecter au serveur
ssh user@votre-serveur.com

# 2. Naviguer vers le répertoire des plugins
cd /var/www/html/wp-content/plugins/

# 3. Sauvegarder l'ancienne version
sudo mv Plugin_UFSC_GESTION_CLUB_13072025 Plugin_UFSC_GESTION_CLUB_13072025.backup.$(date +%Y%m%d)

# 4. Cloner ou copier la nouvelle version
git clone https://github.com/DavyReactiv/Plugin_UFSC_GESTION_CLUB_13072025.git
# OU
scp -r ./Plugin_UFSC_GESTION_CLUB_13072025 user@server:/var/www/html/wp-content/plugins/

# 5. Définir les bonnes permissions
sudo chown -R www-data:www-data Plugin_UFSC_GESTION_CLUB_13072025
sudo chmod -R 755 Plugin_UFSC_GESTION_CLUB_13072025
sudo chmod -R 644 Plugin_UFSC_GESTION_CLUB_13072025/*.php
```

### Méthode 3 : Déploiement Git

```bash
# 1. Se positionner dans le répertoire du plugin sur le serveur
cd /var/www/html/wp-content/plugins/Plugin_UFSC_GESTION_CLUB_13072025

# 2. Récupérer les dernières modifications
git fetch origin

# 3. Sauvegarder les modifications locales éventuelles
git stash

# 4. Mettre à jour vers la nouvelle version
git checkout main
git pull origin main

# 5. Réappliquer les modifications locales si nécessaire
git stash pop
```

## 🧪 Tests post-déploiement (Smoke Tests)

### Tests critiques à effectuer immédiatement

```bash
# 1. Vérifier que le plugin est actif
wp plugin list --status=active | grep ufsc

# 2. Tester la base de données
wp eval "var_dump(class_exists('UFSC_Club_Manager'));"

# 3. Vérifier les tables de base de données
wp db query "SHOW TABLES LIKE '%ufsc%';"
```

### Tests frontend
- [ ] Page d'inscription club accessible
- [ ] Formulaire de licence fonctionnel
- [ ] Dashboard club s'affiche correctement
- [ ] Shortcodes répondent correctement
- [ ] CSS et JavaScript chargés

### Tests admin
- [ ] Menu UFSC accessible
- [ ] Liste des clubs se charge
- [ ] Export CSV fonctionne
- [ ] Paramètres sauvegardables
- [ ] Nouveaux champs WooCommerce présents

### Tests WooCommerce
- [ ] Produits de licence configurés
- [ ] Traitement des commandes
- [ ] Création automatique d'utilisateurs (si activée)
- [ ] Webhook de statut commande

## 🔄 Procédure de rollback

En cas de problème, voici la procédure de retour en arrière :

### Rollback rapide (méthode ZIP)
```bash
# 1. Désactiver le plugin via WP-CLI ou admin
wp plugin deactivate plugin-ufsc-gestion-club-13072025

# 2. Supprimer la version problématique
rm -rf /var/www/html/wp-content/plugins/Plugin_UFSC_GESTION_CLUB_13072025

# 3. Restaurer depuis la sauvegarde
cp -r Plugin_UFSC_GESTION_CLUB_13072025.backup.YYYYMMDD Plugin_UFSC_GESTION_CLUB_13072025

# 4. Réactiver
wp plugin activate plugin-ufsc-gestion-club-13072025
```

### Rollback Git
```bash
# 1. Identifier le commit précédent stable
git log --oneline -10

# 2. Revenir au commit stable
git checkout <commit-hash-stable>

# 3. Créer une branche de hotfix si nécessaire
git checkout -b hotfix/rollback-v1.2.0
```

### Rollback base de données
```bash
# 1. Restaurer la base de données
mysql -u user -p database_name < backup_before_deploy.sql

# 2. Vérifier l'intégrité
wp db check
```

## 📊 Monitoring post-déploiement

### Logs à surveiller
```bash
# Logs PHP
tail -f /var/log/apache2/error.log | grep -i ufsc

# Logs WordPress debug
tail -f /var/www/html/wp-content/debug.log | grep -i ufsc

# Logs serveur web
tail -f /var/log/nginx/access.log | grep -E "(ufsc|club)"
```

### Métriques importantes
- [ ] Temps de chargement des pages (-2s recommandé)
- [ ] Taux d'erreur PHP (0% souhaité)
- [ ] Utilisation mémoire PHP
- [ ] Nombre de requêtes SQL par page

## 🔧 Configuration post-déploiement

### Paramètres à vérifier/configurer

1. **WooCommerce Integration**
   ```
   Admin → UFSC → Paramètres → Intégration WooCommerce
   - ID Produit Affiliation : [configurer]
   - IDs Produits Licences : [configurer]
   - Création auto utilisateur : [selon besoins]
   ```

2. **Pages WordPress**
   ```
   - Importer tools/wxr/ufsc-pages-import.xml
   - Configurer les pages dans Paramètres → UFSC
   ```

3. **Permissions et rôles**
   ```
   - Vérifier les capacités des rôles
   - Tester l'accès aux fonctionnalités admin
   ```

## 📞 Contacts d'urgence

**En cas de problème critique :**
- Développeur : Studio Reactiv
- Responsable technique : [À définir]
- Hébergeur : [Coordonnées hébergeur]

**Escalade :**
1. Désactiver le plugin immédiatement
2. Restaurer la version précédente
3. Contacter l'équipe technique
4. Documenter l'incident

## 📝 Documentation post-déploiement

- [ ] Mettre à jour la documentation utilisateur
- [ ] Former les administrateurs aux nouvelles fonctionnalités
- [ ] Créer/mettre à jour les FAQ
- [ ] Publier les notes de version

---

## Commandes utiles

```bash
# Vérification de l'état du plugin
wp plugin status plugin-ufsc-gestion-club-13072025

# Debug mode temporaire
wp config set WP_DEBUG true
wp config set WP_DEBUG_LOG true

# Flush rewrite rules
wp rewrite flush

# Optimisation base de données
wp db optimize

# Nettoyage cache (si plugin de cache présent)
wp cache flush
```

**Version du guide :** 1.0  
**Dernière mise à jour :** 13 janvier 2025  
**Auteur :** Studio Reactiv