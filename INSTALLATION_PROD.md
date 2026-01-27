# Guide d'installation en production

## 📦 Archive créée

L'archive de production est prête : **`build/crm-v0.1.1.tar.gz`** (0.79 MB)

## 🚀 Installation sur votre serveur Nextcloud

### Méthode 1 : Via SCP (recommandé)

#### 1. Transférer l'archive sur le serveur

```bash
scp build/crm-v0.1.1.tar.gz utilisateur@votre-serveur.com:/tmp/
```

#### 2. Se connecter au serveur

```bash
ssh utilisateur@votre-serveur.com
```

#### 3. Extraire l'archive dans le dossier apps

```bash
# Aller dans le dossier des apps custom
cd /var/www/nextcloud/custom_apps

# Extraire l'archive
sudo tar -xzf /tmp/crm-v0.1.1.tar.gz

# Définir les bonnes permissions
sudo chown -R www-data:www-data crm
sudo chmod -R 755 crm
```

#### 4. Activer l'application

```bash
# Via occ (ligne de commande - recommandé)
sudo -u www-data php /var/www/nextcloud/occ app:enable crm

# Ou via l'interface web : Apps > "Non activées" > CRM > Activer
```

#### 5. Vérifier l'installation

```bash
sudo -u www-data php /var/www/nextcloud/occ app:list | grep crm
```

Vous devriez voir :
```
  - crm: 0.1.1
```

---

### Méthode 2 : Via SFTP/FTP

1. **Uploadez** `build/crm-v0.1.1.tar.gz` dans `/tmp/` sur votre serveur
2. **Connectez-vous en SSH** et suivez les étapes 3-5 ci-dessus

---

### Méthode 3 : Upload direct (si vous avez accès aux fichiers)

1. **Extrayez localement** l'archive :
   ```powershell
   tar -xzf build/crm-v0.1.1.tar.gz -C build/
   ```

2. **Uploadez** le dossier `build/crm` directement dans `/var/www/nextcloud/custom_apps/` via SFTP

3. **Définissez les permissions** (via SSH) :
   ```bash
   sudo chown -R www-data:www-data /var/www/nextcloud/custom_apps/crm
   sudo chmod -R 755 /var/www/nextcloud/custom_apps/crm
   ```

4. **Activez l'app** via occ ou l'interface web

---

## ⚙️ Configuration post-installation

### 1. Accéder aux paramètres

- Interface web : **Paramètres** > **Administration** > **CRM**

### 2. Configurer les chemins

- **Config Path** : Chemin vers vos fichiers de configuration YAML
- **Vault Path** : Chemin vers votre vault Markdown

### 3. Configurer la synchronisation (optionnel)

- Activez la synchronisation automatique avec Contacts/Calendar
- Configurez les métadonnées à synchroniser

---

## 🔧 Dépannage

### L'app n'apparaît pas dans la liste

```bash
# Vérifier les permissions
ls -la /var/www/nextcloud/custom_apps/crm

# Doit afficher : drwxr-xr-x www-data www-data
```

### Erreur de permissions

```bash
sudo chown -R www-data:www-data /var/www/nextcloud/custom_apps/crm
sudo chmod -R 755 /var/www/nextcloud/custom_apps/crm
```

### Logs d'erreur

```bash
# Voir les logs Nextcloud
sudo tail -f /var/www/nextcloud/data/nextcloud.log

# Ou via occ
sudo -u www-data php /var/www/nextcloud/occ log:tail
```

---

## 🔄 Mise à jour vers une nouvelle version

1. **Désactivez** l'app :
   ```bash
   sudo -u www-data php /var/www/nextcloud/occ app:disable crm
   ```

2. **Sauvegardez** l'ancienne version (optionnel) :
   ```bash
   sudo mv /var/www/nextcloud/custom_apps/crm /var/www/nextcloud/custom_apps/crm.backup
   ```

3. **Suivez les étapes d'installation** avec la nouvelle archive

4. **Réactivez** l'app :
   ```bash
   sudo -u www-data php /var/www/nextcloud/occ app:enable crm
   ```

---

## 📋 Checklist d'installation

- [ ] Archive transférée sur le serveur
- [ ] Archive extraite dans `/var/www/nextcloud/custom_apps/`
- [ ] Permissions définies (www-data:www-data, 755)
- [ ] App activée via occ ou interface web
- [ ] App visible dans la liste : `occ app:list | grep crm`
- [ ] Configuration effectuée dans les paramètres admin
- [ ] Tests de fonctionnement réussis

---

## 🆘 Support

- Documentation : https://github.com/lasagne20/nextcloud-CRM
- Issues : https://github.com/lasagne20/nextcloud-CRM/issues
