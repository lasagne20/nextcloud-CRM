interface SyncConfig {
    id: string;
    enabled: boolean;
    user_id: string;
    addressbook?: string;
    calendar?: string;
    metadata_filter: Record<string, string>;
}

class MultiSyncSettingsManager {
    private contactConfigs: SyncConfig[] = [];
    private calendarConfigs: SyncConfig[] = [];
    private users: string[] = [];
    private autoSaveTimeout: NodeJS.Timeout | null = null;
    private isAutoSaving = false;

    constructor() {
        console.log('=== Initialisation MultiSyncSettingsManager ===');
        
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeWithData());
        } else {
            this.initializeWithData();
        }
    }

    private initializeWithData(): void {
        console.log('=== Initialisation avec données ===');
        
        // Récupérer les données depuis les attributs data-
        const crmElement = document.getElementById('crm-admin-settings');
        
        if (!crmElement) {
            console.error('Élément crm-admin-settings non trouvé !');
            this.users = ['admin'];
            return;
        }
        
        try {
            // Récupérer les utilisateurs depuis les data attributes
            const usersData = crmElement.dataset.users;
            const debugUsersData = crmElement.dataset.debugUsers;
            
            console.log('Data users brut:', usersData);
            console.log('Data debug users brut:', debugUsersData);
            
            this.users = usersData ? JSON.parse(usersData) : [];
            
            console.log('Utilisateurs parsés:', this.users);
            
            // Récupérer les configs existantes
            const contactConfigsData = crmElement.dataset.contactConfigs;
            const calendarConfigsData = crmElement.dataset.calendarConfigs;
            
            this.contactConfigs = contactConfigsData ? JSON.parse(contactConfigsData) : [];
            this.calendarConfigs = calendarConfigsData ? JSON.parse(calendarConfigsData) : [];
            
            console.log('Configs contact:', this.contactConfigs);
            console.log('Configs calendrier:', this.calendarConfigs);

        } catch (e) {
            console.error('Erreur parsing données initiales:', e);
            this.users = ['admin'];
        }
        
        // Initialiser l'interface
        this.initializeInterface();
        this.setupAutoSave();
    }
    
    private initializeInterface(): void {
        this.renderContactConfigs();
        this.renderCalendarConfigs();
        this.setupEventListeners();
    }

    private setupEventListeners(): void {
        // Boutons d'ajout de configuration
        document.getElementById('add-contact-config')?.addEventListener('click', () => this.addContactConfig());
        document.getElementById('add-calendar-config')?.addEventListener('click', () => this.addCalendarConfig());
        
        // Bouton de sauvegarde manuel
        document.getElementById('crm-save-sync-settings')?.addEventListener('click', () => this.saveSyncSettings());
    }
    
    private setupAutoSave(): void {
        const globalFields = [
            'sync_contact_global_enabled',
            'sync_contact_global_source', 
            'sync_contact_global_mapping',
            'sync_contact_global_filter',
            'sync_calendar_global_enabled',
            'sync_calendar_global_source',
            'sync_calendar_global_mapping', 
            'sync_calendar_global_filter'
        ];

        globalFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', () => this.autoSaveAfterDelay());
                field.addEventListener('change', () => this.autoSaveAfterDelay());
            }
        });
    }
    
    private autoSaveAfterDelay(): void {
        if (this.autoSaveTimeout) {
            clearTimeout(this.autoSaveTimeout);
        }
        
        this.autoSaveTimeout = setTimeout(() => {
            this.saveSyncSettings(true);
        }, 1000);
    }

    private addContactConfig(): void {
        const newConfig: SyncConfig = {
            id: 'contact_' + Date.now(),
            enabled: true,
            user_id: this.users[0] || 'admin',
            addressbook: 'contacts',
            metadata_filter: {}
        };
        
        this.contactConfigs.push(newConfig);
        this.renderContactConfigs();
    }

    private addCalendarConfig(): void {
        const newConfig: SyncConfig = {
            id: 'calendar_' + Date.now(),
            enabled: true,
            user_id: this.users[0] || 'admin',
            calendar: 'personal',
            metadata_filter: {}
        };
        
        this.calendarConfigs.push(newConfig);
        this.renderCalendarConfigs();
    }

    private renderContactConfigs(): void {
        const container = document.getElementById('contacts-configs-container');
        if (!container) return;

        container.innerHTML = '';

        this.contactConfigs.forEach((config, index) => {
            const configEl = this.createConfigElement(config, index, 'contact');
            container.appendChild(configEl);
        });
    }

    private renderCalendarConfigs(): void {
        const container = document.getElementById('calendar-configs-container');
        if (!container) return;

        container.innerHTML = '';

        this.calendarConfigs.forEach((config, index) => {
            const configEl = this.createConfigElement(config, index, 'calendar');
            container.appendChild(configEl);
        });
    }

    private createConfigElement(config: SyncConfig, index: number, type: 'contact' | 'calendar'): HTMLElement {
        const div = document.createElement('div');
        div.className = 'config-item';
        
        // Options utilisateur
        let userOptions = this.users.length > 0 
            ? this.users.map(user => `<option value="${user}" ${config.user_id === user ? 'selected' : ''}>${user}</option>`).join('')
            : `<option value="admin">admin</option>`;
        
        const targetField = type === 'contact' ? 'addressbook' : 'calendar';
        const targetValue = type === 'contact' ? (config.addressbook || 'contacts') : (config.calendar || 'personal');
        
        div.innerHTML = `
            <div class="config-header">
                <label>
                    <input type="checkbox" ${config.enabled ? 'checked' : ''} 
                           onchange="window.multiSyncApp.updateConfigEnabled('${config.id}', '${type}', this.checked)">
                    Configuration ${type} #${index + 1}
                </label>
                <button type="button" onclick="window.multiSyncApp.removeConfig('${config.id}', '${type}')" 
                        class="icon-delete">Supprimer</button>
            </div>
            <div class="config-fields">
                <div class="field-group">
                    <label>Utilisateur :</label>
                    <select onchange="window.multiSyncApp.updateConfigUser('${config.id}', '${type}', this.value)">
                        ${userOptions}
                    </select>
                </div>
                <div class="field-group">
                    <label>${type === 'contact' ? 'Carnet d\'adresses' : 'Calendrier'} :</label>
                    <input type="text" value="${targetValue}" 
                           onchange="window.multiSyncApp.updateConfigTarget('${config.id}', '${type}', this.value)">
                </div>
                <div class="field-group">
                    <label>Filtre métadonnées (JSON) :</label>
                    <textarea onchange="window.multiSyncApp.updateConfigFilter('${config.id}', '${type}', this.value)" 
                              placeholder='{"champ": "valeur"}'>${JSON.stringify(config.metadata_filter, null, 2)}</textarea>
                </div>
            </div>
        `;
        return div;
    }

    // Méthodes publiques pour les callbacks
    removeConfig(id: string, type: 'contact' | 'calendar'): void {
        if (type === 'contact') {
            this.contactConfigs = this.contactConfigs.filter(c => c.id !== id);
            this.renderContactConfigs();
        } else {
            this.calendarConfigs = this.calendarConfigs.filter(c => c.id !== id);
            this.renderCalendarConfigs();
        }
        this.autoSaveAfterDelay();
    }

    updateConfigEnabled(id: string, type: 'contact' | 'calendar', enabled: boolean): void {
        const configs = type === 'contact' ? this.contactConfigs : this.calendarConfigs;
        const config = configs.find(c => c.id === id);
        if (config) {
            config.enabled = enabled;
            this.autoSaveAfterDelay();
        }
    }

    updateConfigUser(id: string, type: 'contact' | 'calendar', userId: string): void {
        const configs = type === 'contact' ? this.contactConfigs : this.calendarConfigs;
        const config = configs.find(c => c.id === id);
        if (config) {
            config.user_id = userId;
            this.autoSaveAfterDelay();
        }
    }

    updateConfigTarget(id: string, type: 'contact' | 'calendar', target: string): void {
        const configs = type === 'contact' ? this.contactConfigs : this.calendarConfigs;
        const config = configs.find(c => c.id === id);
        if (config) {
            if (type === 'contact') {
                config.addressbook = target;
            } else {
                config.calendar = target;
            }
            this.autoSaveAfterDelay();
        }
    }

    updateConfigFilter(id: string, type: 'contact' | 'calendar', filterJson: string): void {
        try {
            const filter = JSON.parse(filterJson || '{}');
            const configs = type === 'contact' ? this.contactConfigs : this.calendarConfigs;
            const config = configs.find(c => c.id === id);
            if (config) {
                config.metadata_filter = filter;
                this.autoSaveAfterDelay();
            }
        } catch (e) {
            console.error('Erreur parsing filter:', e);
        }
    }

    private async saveSyncSettings(isAutoSave: boolean = false): Promise<void> {
        try {
            const saveBtn = document.getElementById('crm-save-sync-settings') as HTMLButtonElement;
            const statusSpan = document.getElementById('crm-sync-save-status') as HTMLSpanElement;
            
            if (!isAutoSave && saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = '⏳ Enregistrement...';
            } else if (isAutoSave && statusSpan) {
                this.showStatus('💾 Sauvegarde automatique...', 'info', statusSpan);
            }

            // Récupérer toutes les valeurs des champs globaux
            const globalSettings = {
                sync_contact_global_enabled: (document.getElementById('sync_contact_global_enabled') as HTMLInputElement)?.checked || false,
                sync_contact_global_source: (document.getElementById('sync_contact_global_source') as HTMLInputElement)?.value || '',
                sync_contact_global_mapping: (document.getElementById('sync_contact_global_mapping') as HTMLTextAreaElement)?.value || '',
                sync_contact_global_filter: (document.getElementById('sync_contact_global_filter') as HTMLTextAreaElement)?.value || '',
                
                sync_calendar_global_enabled: (document.getElementById('sync_calendar_global_enabled') as HTMLInputElement)?.checked || false,
                sync_calendar_global_source: (document.getElementById('sync_calendar_global_source') as HTMLInputElement)?.value || '',
                sync_calendar_global_mapping: (document.getElementById('sync_calendar_global_mapping') as HTMLTextAreaElement)?.value || '',
                sync_calendar_global_filter: (document.getElementById('sync_calendar_global_filter') as HTMLTextAreaElement)?.value || '',
                
                contact_configs: this.contactConfigs,
                calendar_configs: this.calendarConfigs
            };

            // Requête vers l'API
            const response = await fetch('/index.php/apps/crm/admin/save-sync-settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(globalSettings)
            });

            if (response.ok) {
                const result = await response.json();
                if (statusSpan) {
                    const message = isAutoSave ? '✅ Sauvé automatiquement' : '✅ Configuration sauvegardée !';
                    this.showStatus(message, 'success', statusSpan);
                }
            } else {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

        } catch (error) {
            console.error('Erreur sauvegarde:', error);
            const statusSpan = document.getElementById('crm-sync-save-status') as HTMLSpanElement;
            if (statusSpan) {
                this.showStatus('❌ Erreur lors de la sauvegarde', 'error', statusSpan);
            }
        } finally {
            const saveBtn = document.getElementById('crm-save-sync-settings') as HTMLButtonElement;
            if (!isAutoSave && saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = '💾 Sauvegarder';
            }
        }
    }

    private showStatus(message: string, type: 'success' | 'error' | 'info', statusElement: HTMLElement): void {
        statusElement.textContent = message;
        statusElement.className = type;
        
        setTimeout(() => {
            if (type === 'success' || type === 'info') {
                statusElement.textContent = '';
                statusElement.className = '';
            }
        }, type === 'info' ? 3000 : 5000);
    }
}

// Rendre accessible globalement
(window as any).multiSyncApp = new MultiSyncSettingsManager();

document.addEventListener('DOMContentLoaded', () => {
    console.log("✅ Multi-sync admin.ts chargé");
});