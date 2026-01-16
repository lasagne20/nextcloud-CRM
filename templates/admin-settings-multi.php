<?php
script('core', 'share');     // pour OC.Share
script('crm', 'admin-settings');
style('crm', 'properties-style');
?>

<div id="crm-admin-settings" class="section">
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
                      rows="4" 
                      style="width: 100%; max-width: 600px; font-family: monospace;"
                      placeholder='{"name": "FN", "email": "Email", "phone": "Téléphone"}'><?php p($_['sync_contacts_global_mapping'] ?? '{}'); ?></textarea>
            <p class="crm-setting-hint">
                Mapping JSON des métadonnées vers les champs vCard
            </p>
        </div>

        <div class="crm-setting-item">
            <label for="sync_contacts_global_filter">Filtre global (JSON)</label>
            <textarea id="sync_contacts_global_filter" 
                      rows="2" 
                      style="width: 100%; max-width: 600px; font-family: monospace;"
                      placeholder='{"Statut": "Actif"}'><?php p($_['sync_contacts_global_filter'] ?? '{}'); ?></textarea>
            <p class="crm-setting-hint">Filtre global appliqué à tous les contacts</p>
        </div>

        <h3>Configurations de synchronisation</h3>
        <div id="contacts-configs-container">
            <!-- Les configurations seront ajoutées ici par JavaScript -->
        </div>
        <button type="button" id="add-contact-config" class="button">➕ Ajouter une configuration</button>
    </div>

    <!-- SYNCHRONISATION CALENDRIER -->
    <div class="crm-settings-group">
        <h2>📅 Synchronisation Calendrier</h2>
        
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
            <p class="crm-setting-hint">Nom de la classe dans les métadonnées Markdown à synchroniser</p>
        </div>

        <div class="crm-setting-item">
            <label for="sync_calendar_global_mapping">Mapping global des métadonnées (JSON)</label>
            <textarea id="sync_calendar_global_mapping" 
                      rows="4" 
                      style="width: 100%; max-width: 600px; font-family: monospace;"
                      placeholder='{"title": "Titre", "date": "Date", "description": "Description", "location": "Lieu"}'><?php p($_['sync_calendar_global_mapping'] ?? '{}'); ?></textarea>
            <p class="crm-setting-hint">
                Mapping JSON des métadonnées vers les champs calendrier
            </p>
        </div>

        <div class="crm-setting-item">
            <label for="sync_calendar_global_filter">Filtre global (JSON)</label>
            <textarea id="sync_calendar_global_filter" 
                      rows="2" 
                      style="width: 100%; max-width: 600px; font-family: monospace;"
                      placeholder='{"Statut": "Actif"}'><?php p($_['sync_calendar_global_filter'] ?? '{}'); ?></textarea>
            <p class="crm-setting-hint">Filtre global appliqué à toutes les actions</p>
        </div>

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

    <!-- Données utilisateurs pour JavaScript -->
    <script>
        window.crmUsers = <?php echo json_encode($_['users'] ?? []); ?>;
        window.crmContactsConfigs = <?php echo $_['sync_contacts_configs'] ?? '[]'; ?>;
        window.crmCalendarConfigs = <?php echo $_['sync_calendar_configs'] ?? '[]'; ?>;
    </script>
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
    justify-content: between;
    align-items: center;
    margin-bottom: 10px;
}

.config-title {
    font-weight: bold;
    margin-right: auto;
}

.success {
    color: var(--color-success);
}

.error {
    color: var(--color-error);
}
</style>