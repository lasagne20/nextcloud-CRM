<?php
style('crm', 'properties-style');
?>

<div id="crm-admin-settings" class="section" 
     data-users="<?php echo htmlspecialchars(json_encode($_['users'] ?? [])); ?>"
     data-contacts-configs="<?php echo htmlspecialchars($_['sync_contacts_configs'] ?? '[]'); ?>"
     data-calendar-configs="<?php echo htmlspecialchars($_['sync_calendar_configs'] ?? '[]'); ?>"
     data-debug-users="<?php echo htmlspecialchars(json_encode($_['debug_users'] ?? [])); ?>">

<script>
// Initialisation des données via attributs data
const crmElement = document.getElementById('crm-admin-settings');
if (crmElement) {
    try {
        window.crmUsers = JSON.parse(crmElement.dataset.users || '[]');
        window.crmContactsConfigs = crmElement.dataset.contactsConfigs || '[]';
        window.crmCalendarConfigs = crmElement.dataset.calendarConfigs || '[]';  
        window.crmDebugUsers = JSON.parse(crmElement.dataset.debugUsers || '[]');
        
        console.log('=== Variables initialisées depuis data attributes ===');
        console.log('Users disponibles:', window.crmUsers);
        console.log('Configs contacts:', window.crmContactsConfigs);
        console.log('Debug utilisateurs:', window.crmDebugUsers);
    } catch (e) {
        console.error('Erreur parsing data attributes:', e);
        window.crmUsers = ['admin'];
        window.crmContactsConfigs = '[]';
        window.crmCalendarConfigs = '[]';
        window.crmDebugUsers = [];
    }
}
</script>

<?php script('crm', 'admin-settings'); ?>
<?php script('crm', 'multi-sync-settings'); ?>
    <h1>Paramètres CRM</h1>

    <div class="crm-settings-group">
        <h2>Configuration générale</h2>
        
        <div class="crm-setting-item">
            <label for="crm-config-path">Chemin des fichiers de configuration (YAML)</label>
            <input type="text" 
                   id="crm-config-path" 
                   name="config_path" 
                   value="<?php p($_['config_path'] ?? '/apps/crm/config'); ?>" 
                   placeholder="/apps/crm/config"
                   style="width: 400px;" />
            <p class="crm-setting-hint">
                Chemin absolu ou relatif vers le dossier contenant les fichiers YAML de définition des classes (Contact.yaml, Lieu.yaml, etc.)
            </p>
        </div>

        <div class="crm-setting-item">
            <label for="crm-vault-path">Chemin du vault (fichiers Markdown)</label>
            <input type="text" 
                   id="crm-vault-path" 
                   name="vault_path" 
                   value="<?php p($_['vault_path'] ?? 'vault'); ?>" 
                   placeholder="vault"
                   style="width: 400px;" />
            <p class="crm-setting-hint">
                Chemin relatif vers le dossier contenant les fichiers Markdown à traiter
            </p>
        </div>

        <button type="button" id="crm-save-settings" class="button primary">
            💾 Enregistrer la configuration générale
        </button>
        <span id="crm-save-status" style="margin-left: 10px;"></span>
    </div>

    <!-- SYNCHRONISATION CONTACTS -->
    <div class="crm-settings-group">
        <h2>📇 Synchronisation Contacts</h2>
        
        <div class="crm-setting-item">
            <label>
                <input type="checkbox" id="sync_contacts_global_enabled" 
                       <?php echo $_['sync_contacts_global_enabled'] ? 'checked' : ''; ?> />
                Activer la synchronisation des contacts
            </label>
        </div>

        <div class="crm-setting-item">
            <label for="sync_contacts_global_class">Classe à surveiller</label>
            <input type="text" id="sync_contacts_global_class" 
                   value="<?php p($_['sync_contacts_global_class'] ?? 'Personne'); ?>" 
                   placeholder="Personne" />
            <p class="crm-setting-hint">Nom de la classe dans les métadonnées Markdown à synchroniser</p>
        </div>

        <div class="crm-setting-item">
            <label for="sync_contacts_global_mapping">Mapping global des métadonnées (JSON)</label>
            <textarea id="sync_contacts_global_mapping" 
                      rows="6" 
                      style="width: 100%; max-width: 600px; font-family: monospace;"
                      placeholder='{"name": "FN", "email": "Email", "phone": "Téléphone"}'><?php p($_['sync_contacts_global_mapping'] ?? '{}'); ?></textarea>
            <p class="crm-setting-hint">
                Mapping JSON des métadonnées vers les champs vCard.<br>
                <strong>Exemples :</strong><br>
                • Champs de base : <code>{"name": "FN", "email": "Email", "phone": "Téléphone", "mobile": "Portable"}</code><br>
                • Avec organisation : <code>{"name": "FN", "email": "Email", "phone": "TEL", "organization": "Institution"}</code><br>
                • Complet : <code>{"name": "Nom", "email": "Email", "phone": "Téléphone", "mobile": "Portable", "additional": {"ORG": "Institution", "TITLE": "Poste"}}</code>
            </p>
        </div>

        <div class="crm-setting-item">
            <label for="sync_contacts_global_filter">Filtre global (JSON)</label>
            <textarea id="sync_contacts_global_filter" 
                      rows="3" 
                      style="width: 100%; max-width: 600px; font-family: monospace;"
                      placeholder='{"Statut": "Actif"}'><?php p($_['sync_contacts_global_filter'] ?? '{}'); ?></textarea>
            <p class="crm-setting-hint">
                Filtre global appliqué à tous les contacts.<br>
                <strong>Exemples :</strong><br>
                • Par statut : <code>{"Statut": "Actif"}</code><br>
                • Multi-critères : <code>{"Statut": "Actif", "Type": "Client"}</code><br>
                • Par département : <code>{"Département": "Commercial", "Priorité": "Haute"}</code>
            </p>
        </div>

        <h3>Configurations de synchronisation</h3>
        <div id="contacts-configs-container">
            <!-- Les configurations seront ajoutées ici par JavaScript -->
        </div>
        <button type="button" id="add-contact-config" class="button">➕ Ajouter une configuration</button>
    </div>

    <!-- SYNCHRONISATION CALENDRIER -->
    <div class="crm-settings-group">
        <h2>📅 Synchronisation Calendrier - Propriétés Tableau</h2>
        
        <div class="crm-setting-item">
            <label>
                <input type="checkbox" id="sync_calendar_global_enabled" 
                       <?php echo $_['sync_calendar_global_enabled'] ? 'checked' : ''; ?> />
                Activer la synchronisation du calendrier
            </label>
        </div>

        <div class="crm-setting-item">
            <label for="sync_calendar_global_class">Classe à surveiller</label>
            <input type="text" id="sync_calendar_global_class" 
                   value="<?php p($_['sync_calendar_global_class'] ?? 'Action'); ?>" 
                   placeholder="Action" />
            <p class="crm-setting-hint">Seuls les fichiers avec cette classe dans les métadonnées seront traités</p>
        </div>

        <h3>📋 Configurations des Propriétés Tableau</h3>
        <p class="crm-setting-hint" style="margin-bottom: 20px;">
            Créez des événements de calendrier à partir de propriétés tableau dans vos fichiers Markdown.<br>
            Chaque configuration gère : filtres, utilisateur cible, calendrier de destination et format des événements.
        </p>
        
        <!-- Container pour AnimationSettings.ts -->
        <div id="animation-settings-container"></div>
        
        <!-- Champs cachés pour stocker les données -->
        <input type="hidden" id="crm-animation-configs" name="animation_configs" value="<?php p($_['animation_configs'] ?? '[]'); ?>" />
        <input type="hidden" id="crm-users-list" value="<?php p(json_encode($_['users'] ?? ['admin'])); ?>" />
        
        <?php style('crm', 'animation-settings'); ?>
        <?php script('crm', 'animation-settings'); ?>

        <h3>Configurations de synchronisation</h3>
        <div id="calendar-configs-container">
            <!-- Les configurations seront ajoutées ici par JavaScript -->
        </div>
        <button type="button" id="add-calendar-config" class="button">➕ Ajouter une configuration</button>
    </div>

    <button type="button" id="crm-save-sync-settings" class="button primary" style="margin-top: 20px;">
        💾 Enregistrer toutes les configurations de synchronisation
    </button>
    <span id="crm-sync-save-status" style="margin-left: 10px;"></span>

    <!-- Debug direct des données PHP -->
    <div style="background: #f0f0f0; padding: 10px; margin: 20px 0; border-radius: 5px; font-family: monospace;">
        <h3>Debug - Données PHP reçues :</h3>
        <p><strong>Utilisateurs :</strong> <?php var_dump($_['users'] ?? 'NON DÉFINI'); ?></p>
        <p><strong>Configs contacts :</strong> <?php echo htmlspecialchars($_['sync_contacts_configs'] ?? 'NON DÉFINI'); ?></p>
        <p><strong>Configs calendrier :</strong> <?php echo htmlspecialchars($_['sync_calendar_configs'] ?? 'NON DÉFINI'); ?></p>
    </div>
</div>

<style>
.crm-settings-group {
    margin-bottom: 30px;
    padding: 20px;
    background: var(--color-background-hover);
    border-radius: var(--border-radius);
}

.crm-setting-item {
    margin-bottom: 15px;
}

.crm-setting-item label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

.crm-setting-hint {
    font-size: 0.9em;
    color: var(--color-text-maxcontrast);
    margin-top: 5px;
}

.config-item {
    border: 1px solid var(--color-border);
    padding: 15px;
    margin: 10px 0;
    border-radius: var(--border-radius);
    background: var(--color-main-background);
}

.config-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.config-title {
    font-weight: bold;
}

.success {
    color: var(--color-success);
}

.error {
    color: var(--color-error);
}

.info {
    color: var(--color-primary);
}
</style>
