
export class MDManagementApp {
    private container: HTMLDivElement;
    private addBtn: HTMLButtonElement;
    private sectionCount = 0;


    constructor(containerId: string, addBtnId: string) {
        const containerEl = document.getElementById(containerId) as HTMLDivElement;
        const btnEl = document.getElementById(addBtnId) as HTMLButtonElement;

        if (!containerEl || !btnEl) {
            throw new Error("Conteneur ou bouton introuvable !");
        }

        this.container = containerEl;
        this.addBtn = btnEl;

    }

}

class GeneralSettingsManager {
    private saveBtn: HTMLButtonElement;
    private configPathInput: HTMLInputElement;
    private vaultPathInput: HTMLInputElement;
    private statusSpan: HTMLSpanElement;

    constructor() {
        this.saveBtn = document.getElementById('crm-save-settings') as HTMLButtonElement;
        this.configPathInput = document.getElementById('crm-config-path') as HTMLInputElement;
        this.vaultPathInput = document.getElementById('crm-vault-path') as HTMLInputElement;
        this.statusSpan = document.getElementById('crm-save-status') as HTMLSpanElement;

        if (this.saveBtn) {
            this.saveBtn.addEventListener('click', () => this.saveSettings());
        }
    }

    private async saveSettings() {
        const configPath = this.configPathInput.value.trim();
        const vaultPath = this.vaultPathInput.value.trim();

        if (!configPath || !vaultPath) {
            this.showStatus('Veuillez remplir tous les champs', 'error');
            return;
        }

        try {
            this.saveBtn.disabled = true;
            this.saveBtn.textContent = '⏳ Enregistrement...';

            const response = await fetch('/apps/crm/settings/general', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'requesttoken': (window as any).OC.requestToken
                },
                body: JSON.stringify({
                    config_path: configPath,
                    vault_path: vaultPath
                })
            });

            if (!response.ok) {
                throw new Error('Erreur lors de l\'enregistrement');
            }

            const data = await response.json();
            this.showStatus(data.message || 'Paramètres enregistrés !', 'success');
            
            // Informer l'utilisateur qu'un rechargement peut être nécessaire
            setTimeout(() => {
                this.showStatus('⚠️ Rechargez la page CRM pour appliquer les changements', 'error');
            }, 2000);

        } catch (error) {
            console.error('Erreur:', error);
            this.showStatus('Erreur lors de l\'enregistrement', 'error');
        } finally {
            this.saveBtn.disabled = false;
            this.saveBtn.textContent = '💾 Enregistrer les paramètres';
        }
    }

    private showStatus(message: string, type: 'success' | 'error') {
        this.statusSpan.textContent = message;
        this.statusSpan.className = type;
        
        setTimeout(() => {
            if (type === 'success') {
                this.statusSpan.textContent = '';
                this.statusSpan.className = '';
            }
        }, 5000);
    }
}

class SyncSettingsManager {
    private saveBtn: HTMLButtonElement;
    private contactsEnabledCheckbox: HTMLInputElement;
    private contactsUserSelect: HTMLSelectElement;
    private contactsAddressbookInput: HTMLInputElement;
    private contactsClassInput: HTMLInputElement;
    private contactsMappingTextarea: HTMLTextAreaElement;
    private calendarEnabledCheckbox: HTMLInputElement;
    private calendarUserSelect: HTMLSelectElement;
    private calendarNameInput: HTMLInputElement;
    private calendarClassInput: HTMLInputElement;
    private calendarMappingTextarea: HTMLTextAreaElement;
    private calendarArrayPropertiesTextarea: HTMLTextAreaElement;
    private metadataFilterTextarea: HTMLTextAreaElement;
    private userMappingTextarea: HTMLTextAreaElement;
    private statusSpan: HTMLSpanElement;

    constructor() {
        this.saveBtn = document.getElementById('crm-save-sync-settings') as HTMLButtonElement;
        this.contactsEnabledCheckbox = document.getElementById('crm-sync-contacts-enabled') as HTMLInputElement;
        this.contactsUserSelect = document.getElementById('crm-sync-contacts-user') as HTMLSelectElement;
        this.contactsAddressbookInput = document.getElementById('crm-sync-contacts-addressbook') as HTMLInputElement;
        this.contactsClassInput = document.getElementById('crm-sync-contacts-class') as HTMLInputElement;
        this.contactsMappingTextarea = document.getElementById('crm-sync-contacts-mapping') as HTMLTextAreaElement;
        this.calendarEnabledCheckbox = document.getElementById('crm-sync-calendar-enabled') as HTMLInputElement;
        this.calendarUserSelect = document.getElementById('crm-sync-calendar-user') as HTMLSelectElement;
        this.calendarNameInput = document.getElementById('crm-sync-calendar-name') as HTMLInputElement;
        this.calendarClassInput = document.getElementById('crm-sync-calendar-class') as HTMLInputElement;
        this.calendarMappingTextarea = document.getElementById('crm-sync-calendar-mapping') as HTMLTextAreaElement;
        this.calendarArrayPropertiesTextarea = document.getElementById('crm-sync-calendar-array-properties') as HTMLTextAreaElement;
        this.metadataFilterTextarea = document.getElementById('sync_metadata_filter') as HTMLTextAreaElement;
        this.userMappingTextarea = document.getElementById('sync_user_mapping') as HTMLTextAreaElement;
        this.statusSpan = document.getElementById('crm-sync-save-status') as HTMLSpanElement;

        if (this.saveBtn) {
            this.saveBtn.addEventListener('click', () => this.saveSyncSettings());
        }

        // Gérer l'activation/désactivation des sections
        if (this.contactsEnabledCheckbox) {
            this.contactsEnabledCheckbox.addEventListener('change', () => this.toggleContactsConfig());
            this.toggleContactsConfig();
        }

        if (this.calendarEnabledCheckbox) {
            this.calendarEnabledCheckbox.addEventListener('change', () => this.toggleCalendarConfig());
            this.toggleCalendarConfig();
        }
    }

    private toggleContactsConfig() {
        const configDiv = document.getElementById('crm-contacts-config') as HTMLDivElement;
        if (configDiv) {
            configDiv.style.opacity = this.contactsEnabledCheckbox.checked ? '1' : '0.5';
        }
    }

    private toggleCalendarConfig() {
        const configDiv = document.getElementById('crm-calendar-config') as HTMLDivElement;
        if (configDiv) {
            configDiv.style.opacity = this.calendarEnabledCheckbox.checked ? '1' : '0.5';
        }
    }

    private async saveSyncSettings() {
        try {
            this.saveBtn.disabled = true;
            this.saveBtn.textContent = '⏳ Enregistrement...';

            // Valider le JSON du mapping contacts
            let contactsMappingValue = this.contactsMappingTextarea.value.trim();
            if (!contactsMappingValue) {
                contactsMappingValue = '{}';
            }
            try {
                JSON.parse(contactsMappingValue);
            } catch (e) {
                this.showStatus('Erreur: Le mapping JSON des contacts est invalide', 'error');
                return;
            }

            // Valider le JSON du mapping calendrier
            let calendarMappingValue = this.calendarMappingTextarea.value.trim();
            if (!calendarMappingValue) {
                calendarMappingValue = '{}';
            }
            try {
                JSON.parse(calendarMappingValue);
            } catch (e) {
                this.showStatus('Erreur: Le mapping JSON du calendrier est invalide', 'error');
                return;
            }

            // Valider le JSON des propriétés tableau
            let calendarArrayPropertiesValue = this.calendarArrayPropertiesTextarea?.value.trim() || '{}';
            if (!calendarArrayPropertiesValue) {
                calendarArrayPropertiesValue = '{}';
            }
            try {
                JSON.parse(calendarArrayPropertiesValue);
            } catch (e) {
                this.showStatus('Erreur: Les propriétés tableau JSON sont invalides', 'error');
                return;
            }

            // Valider le JSON du filtre métadonnées
            let metadataFilterValue = this.metadataFilterTextarea.value.trim();
            if (!metadataFilterValue) {
                metadataFilterValue = '{}';
            }
            try {
                JSON.parse(metadataFilterValue);
            } catch (e) {
                this.showStatus('Erreur: Le filtre JSON métadonnées est invalide', 'error');
                return;
            }

            // Valider le JSON du mapping utilisateur
            let userMappingValue = this.userMappingTextarea.value.trim();
            if (!userMappingValue) {
                userMappingValue = '{}';
            }
            try {
                JSON.parse(userMappingValue);
            } catch (e) {
                this.showStatus('Erreur: Le mapping JSON utilisateur est invalide', 'error');
                return;
            }

            const response = await fetch('/apps/crm/settings/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'requesttoken': (window as any).OC.requestToken
                },
                body: JSON.stringify({
                    sync_contacts_enabled: this.contactsEnabledCheckbox.checked,
                    sync_contacts_user: this.contactsUserSelect.value,
                    sync_contacts_addressbook: this.contactsAddressbookInput.value.trim(),
                    sync_contacts_class: this.contactsClassInput.value.trim() || 'Personne',
                    sync_contacts_mapping: contactsMappingValue,
                    sync_calendar_enabled: this.calendarEnabledCheckbox.checked,
                    sync_calendar_user: this.calendarUserSelect.value,
                    sync_calendar_name: this.calendarNameInput.value.trim(),
                    sync_calendar_class: this.calendarClassInput.value.trim() || 'Action',
                    sync_calendar_mapping: calendarMappingValue,
                    sync_calendar_array_properties: calendarArrayPropertiesValue,
                    sync_metadata_filter: metadataFilterValue,
                    sync_user_mapping: userMappingValue
                })
            });

            if (!response.ok) {
                throw new Error('Erreur lors de l\'enregistrement');
            }

            const data = await response.json();
            this.showStatus(data.message || 'Paramètres de synchronisation enregistrés !', 'success');

        } catch (error) {
            console.error('Erreur:', error);
            this.showStatus('Erreur lors de l\'enregistrement', 'error');
        } finally {
            this.saveBtn.disabled = false;
            this.saveBtn.textContent = '💾 Enregistrer les paramètres de synchronisation';
        }
    }

    private showStatus(message: string, type: 'success' | 'error') {
        this.statusSpan.textContent = message;
        this.statusSpan.className = type;
        
        setTimeout(() => {
            if (type === 'success') {
                this.statusSpan.textContent = '';
                this.statusSpan.className = '';
            }
        }, 5000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("✅ CRM admin.ts chargé");
    new MDManagementApp('crm-md-sections', 'crm-add-md-section');
    new GeneralSettingsManager();
    new SyncSettingsManager();
});
