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
                Chemin relatif depuis le dossier des fichiers utilisateur (ex: vault, Documents/CRM, etc.)
            </p>
        </div>

        <button type="button" id="crm-save-settings" class="button primary">
            💾 Enregistrer les paramètres
        </button>
        <span id="crm-save-status" style="margin-left: 10px;"></span>
    </div>

    <div class="crm-settings-group" style="margin-top: 30px;">
        <h2>Gestion des fichiers Markdown</h2>
        <button type="button" id="crm-add-md-section" class="button primary">
            ➕ Ajouter MD management
        </button>
        <div id="crm-md-sections"></div>
    </div>
</div>

<style>
.crm-settings-group {
    margin-bottom: 30px;
    padding: 20px;
    background: var(--color-background-hover);
    border-radius: 8px;
}

.crm-setting-item {
    margin-bottom: 20px;
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

#crm-save-status {
    font-weight: bold;
}

#crm-save-status.success {
    color: var(--color-success);
}

#crm-save-status.error {
    color: var(--color-error);
}
</style>
