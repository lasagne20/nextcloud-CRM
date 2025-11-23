
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

document.addEventListener('DOMContentLoaded', () => {
    console.log("✅ CRM admin.ts chargé");
    new MDManagementApp('crm-md-sections', 'crm-add-md-section');
    new GeneralSettingsManager();
});
