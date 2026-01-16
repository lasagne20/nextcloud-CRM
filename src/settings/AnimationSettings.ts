/**
 * Gestion des configurations pour les propriétés tableau multiples
 */

interface ArrayPropertyConfig {
	id: string;
	enabled: boolean;
	label: string;
	user_id: string;
	calendar: string;
	metadata_filter: Record<string, string>;
	array_property: string;
	date_field: string;
	title_format: string;
	id_format: string;
	description_fields: string[];
	location_field?: string;
	assigned_field?: string;
}

interface ArrayPropertySettingsData {
	configs: ArrayPropertyConfig[];
}

class AnimationSettings {
	private container: HTMLElement;
	private data: ArrayPropertySettingsData = { configs: [] };
	private configCounter: number = 0;
	private users: string[] = [];
	private userCalendars: Map<string, any[]> = new Map();

	constructor(container: HTMLElement) {
		this.container = container;
		this.loadUsers();
		this.loadConfigs();
		this.render();
		this.attachEventListeners();
	}

	private loadUsers(): void {
		// Récupérer la liste des utilisateurs depuis le template
		const usersInput = document.getElementById('crm-users-list') as HTMLInputElement;
		if (usersInput && usersInput.value) {
			try {
				this.users = JSON.parse(usersInput.value);
			} catch (e) {
				console.error('Erreur parsing users:', e);
				this.users = ['admin'];
			}
		} else {
			this.users = ['admin'];
		}
	}

	private loadConfigs(): void {
		const configsInput = document.getElementById('crm-animation-configs') as HTMLInputElement;
		if (configsInput && configsInput.value) {
			try {
				this.data.configs = JSON.parse(configsInput.value);
				this.configCounter = Math.max(0, ...this.data.configs.map(c => parseInt(c.id.replace('anim-', '')) || 0));
			} catch (e) {
				console.error('Erreur parsing configs animations:', e);
				this.data.configs = [];
			}
		}
	}

	private async saveConfigs(): Promise<void> {
		const configsInput = document.getElementById('crm-animation-configs') as HTMLInputElement;
		if (configsInput) {
			configsInput.value = JSON.stringify(this.data.configs);
			
			// Sauvegarder via AJAX
			try {
				const formData = new FormData();
				formData.append('animation_configs', configsInput.value);
				
				const response = await fetch('/apps/crm/settings/saveAnimationConfigs', {
					method: 'POST',
					headers: {
						'requesttoken': (window as any).OC.requestToken
					},
					body: formData
				});

				if (!response.ok) {
					console.error('Erreur sauvegarde configs:', response.statusText);
					alert('Erreur lors de la sauvegarde');
					return;
				}

				const data = await response.json();
				console.log('✓ Configs sauvegardées:', data.count, 'configurations');
			} catch (error) {
				console.error('Erreur AJAX saveConfigs:', error);
				alert('Erreur lors de la sauvegarde: ' + error);
			}
		}
	}

	private async loadUserCalendars(userId: string): Promise<void> {
		try {
			const response = await fetch(`/apps/crm/settings/getUserCalendars/${userId}`);
			if (!response.ok) {
				console.error('Erreur chargement calendriers:', response.statusText);
				return;
			}
			const data = await response.json();
			if (data.calendars && Array.isArray(data.calendars)) {
				this.userCalendars.set(userId, data.calendars);
			}
		} catch (error) {
			console.error('Erreur fetch calendriers:', error);
		}
	}

	private render(): void {
		this.container.innerHTML = `
			<div class="animation-configs-wrapper">
				<div class="animation-configs-header">
					<h3>📋 Configurations des Propriétés Tableau</h3>
					<button type="button" class="button" id="add-animation-config">
						➕ Ajouter une configuration
					</button>
				</div>
				<div class="animation-configs-list" id="animation-configs-list"></div>
				<div class="animation-help">
					<p><strong>💡 Aide :</strong></p>
					<ul>
						<li><code>_content</code> : Contenu complet du fichier markdown (sans métadonnées YAML)</li>
						<li><code>_root.NomChamp</code> : Accède aux métadonnées racine du fichier (ex: _root.Titre, _root.Statut)</li>
						<li>Champs simples : Proviennent directement de chaque élément du tableau</li>
					</ul>
					<p><strong>Format du titre :</strong> Utilisez {nomChamp} pour insérer des valeurs (ex: {nom} - {date})</p>
					<p><strong>Format de l'ID :</strong> Utilisez {index} et {filename} (ex: event_{index})</p>
				</div>
			</div>
		`;

		this.renderConfigsList();
	}

	private renderConfigsList(): void {
		const list = document.getElementById('animation-configs-list');
		if (!list) return;

		if (this.data.configs.length === 0) {
			list.innerHTML = '<p class="empty-state">Aucune configuration. Cliquez sur "Ajouter une configuration" pour commencer.</p>';
			return;
		}

		list.innerHTML = this.data.configs.map(config => this.renderConfig(config)).join('');
	}

	private renderConfig(config: ArrayPropertyConfig): string {
		const filterDisplay = Object.keys(config.metadata_filter).length > 0
			? Object.entries(config.metadata_filter).map(([k, v]) => `${k}=${v}`).join(', ')
			: 'Aucun filtre';

		const userOptions = this.users.map(user => 
			`<option value="${this.escapeHtml(user)}" ${user === config.user_id ? 'selected' : ''}>${this.escapeHtml(user)}</option>`
		).join('');

		// Récupérer les calendriers depuis le cache ou utiliser des valeurs par défaut
		const userCalendars = this.userCalendars.get(config.user_id) || [
			{ uri: 'personal', name: 'Personnel', color: '#0082c9' },
			{ uri: 'contact_birthdays', name: 'Anniversaires', color: '#f4a733' },
			{ uri: 'work', name: 'Travail', color: '#4caf50' },
			{ uri: 'private', name: 'Privé', color: '#9c27b0' }
		];
		
		const calendarOptions = userCalendars.map(cal => 
			`<option value="${this.escapeHtml(cal.uri)}" ${cal.uri === config.calendar ? 'selected' : ''}>${this.escapeHtml(cal.name)}</option>`
		).join('');

		return `
			<div class="animation-config-item ${config.enabled ? 'enabled' : 'disabled'}" data-config-id="${config.id}">
				<div class="config-header">
					<div class="config-title">
						<input type="checkbox" 
							class="config-enabled" 
							${config.enabled ? 'checked' : ''} 
							data-config-id="${config.id}">
						<strong>${this.escapeHtml(config.label)}</strong>
						<span class="config-badge">${this.escapeHtml(config.array_property)}</span>
					</div>
					<div class="config-actions">
						<button type="button" class="button-icon button-toggle" data-config-id="${config.id}" title="Afficher/Masquer">
							▼
						</button>
						<button type="button" class="button-icon button-delete" data-config-id="${config.id}" title="Supprimer">
							🗑️
						</button>
					</div>
				</div>
				<div class="config-summary">
					<span class="summary-item">👤 ${this.escapeHtml(config.user_id)}</span>
					<span class="summary-item">📅 ${this.escapeHtml(config.calendar)}</span>
					<span class="summary-item">🔍 ${filterDisplay}</span>
				</div>
				<div class="config-details" style="display: none;">
					<div class="config-form">
						<div class="form-section">
						<h4>ℹ️ Informations générales</h4>
						<div class="form-row">
							<div class="form-group form-group-wide">
								<label>📝 Nom de la configuration</label>
								<input type="text" class="config-label" value="${this.escapeHtml(config.label)}" data-config-id="${config.id}" placeholder="Ex: Événements publics">
							</div>
							<div class="form-group form-group-wide">
								<label>📋 Propriété tableau (YAML)</label>
								<input type="text" class="config-array-property" value="${this.escapeHtml(config.array_property)}" data-config-id="${config.id}" placeholder="Ex: evenements, animations, taches">
								<small>Nom du champ tableau dans les métadonnées de vos fichiers Markdown</small>
							</div>
						</div>

						<div class="form-section">
							<h4>Destination</h4>
							<div class="form-row">
								<div class="form-group">
									<label>👤 Utilisateur cible</label>
									<select class="config-user" data-config-id="${config.id}">
										${userOptions}
									</select>
								</div>
								<div class="form-group">
									<label>📅 Calendrier</label>
								<input type="text" class="config-calendar" value="${this.escapeHtml(config.calendar)}" data-config-id="${config.id}" placeholder="Ex: personal, work, contact_birthdays">
								<small>URI du calendrier de l'utilisateur</small>
								</div>
							</div>
						</div>

						<div class="form-section">
						<h4>🔍 Filtrage</h4>
						<div class="form-group form-group-full">
							<label>🎯 Filtre métadonnées (JSON)</label>
							<textarea class="config-filter" data-config-id="${config.id}" rows="3" placeholder='{"Statut": "Actif", "Type": "Public", "Priorité": "Haute"}'>${this.escapeHtml(JSON.stringify(config.metadata_filter, null, 2))}</textarea>
							<small>Seuls les fichiers dont les métadonnées correspondent exactement à ce filtre seront traités</small>
						</div>

						<div class="form-section">
						<h4>📅 Configuration des événements</h4>
						<div class="form-row">
							<div class="form-group">
								<label>📆 Champ date</label>
								<input type="text" class="config-date-field" value="${this.escapeHtml(config.date_field)}" data-config-id="${config.id}" placeholder="date">
								<small>Nom du champ contenant la date dans chaque élément</small>
							</div>
							<div class="form-group">
								<label>🏷️ Format du titre</label>
								<input type="text" class="config-title-format" value="${this.escapeHtml(config.title_format)}" data-config-id="${config.id}" placeholder="{nom} - {date}">
								<small>Variables: {nomChamp} pour insérer les valeurs</small>
							</div>
						</div>
						<div class="form-row">
							<div class="form-group">
								<label>🆔 Format de l'ID</label>
								<input type="text" class="config-id-format" value="${this.escapeHtml(config.id_format)}" data-config-id="${config.id}" placeholder="event_{index}">
								<small>Variables: {index} (numéro), {filename} (nom fichier)</small>
							</div>
						</div>
						<div class="form-row">
							<div class="form-group">
								<label>📍 Champ adresse/lieu</label>
								<input type="text" class="config-location-field" value="${this.escapeHtml(config.location_field || '')}" data-config-id="${config.id}" placeholder="lieu, adresse, location">
								<small>Nom du champ contenant l'adresse ou le lieu</small>
							</div>
						</div>
						<div class="form-row">
							<div class="form-group">
								<label>👥 Champ assignés/responsables</label>
								<input type="text" class="config-assigned-field" value="${this.escapeHtml(config.assigned_field || '')}" data-config-id="${config.id}" placeholder="assignés, responsable, animateurs">
								<small>Nom du champ contenant les personnes assignées</small>
							</div>
						</div>
						<div class="form-row">
							<div class="form-group">
								<label>📄 Champs de description (séparés par virgules)</label>
								<input type="text" class="config-desc-fields" value="${this.escapeHtml(config.description_fields.join(', '))}" data-config-id="${config.id}" placeholder="statut, responsable, _root.Titre, _content">
								<small>_content = contenu complet | _root.X = métadonnée racine</small>
							</div>
						</div>

						<div class="form-actions">
							<button type="button" class="button primary config-save" data-config-id="${config.id}">
								💾 Enregistrer les modifications
		`;
	}

	private attachEventListeners(): void {
		// Bouton ajouter
		const addBtn = document.getElementById('add-animation-config');
		addBtn?.addEventListener('click', () => this.addConfig());

		// Délégation d'événements pour les boutons dynamiques
		this.container.addEventListener('click', (e) => {
			const target = e.target as HTMLElement;
			const button = target.closest('button') as HTMLButtonElement;
			if (!button) return;

			const configId = button.dataset.configId;
			if (!configId) return;

			if (button.classList.contains('button-toggle')) {
				this.toggleConfig(configId);
			} else if (button.classList.contains('button-delete')) {
				this.deleteConfig(configId);
			} else if (button.classList.contains('config-save')) {
				this.updateConfig(configId);
			}
		});

		// Checkbox enabled
		this.container.addEventListener('change', (e) => {
			const target = e.target as HTMLInputElement;
			if (target.classList.contains('config-enabled')) {
				const configId = target.dataset.configId;
				if (configId) {
					this.toggleEnabled(configId, target.checked);
				}
			}
			

		});
	}

	private addConfig(): void {
		this.configCounter++;
		const newConfig: ArrayPropertyConfig = {
			id: `config-${this.configCounter}`,
			enabled: true,
			label: `Configuration ${this.configCounter}`,
			user_id: this.users[0] || 'admin',
			calendar: 'personal',
			metadata_filter: {},
			array_property: 'items',
			date_field: 'date',
			title_format: '{nom} - {date}',
			id_format: 'event_{index}',
			description_fields: ['statut', 'description'],
			location_field: 'lieu',
			assigned_field: 'assignés'
		};

		this.data.configs.push(newConfig);
		this.renderConfigsList();
		this.saveConfigs();

		// Ouvrir automatiquement la nouvelle config
		setTimeout(() => this.toggleConfig(newConfig.id), 100);
	}

	private deleteConfig(configId: string): void {
		if (!confirm('Supprimer cette configuration ?')) return;

		this.data.configs = this.data.configs.filter(c => c.id !== configId);
		this.renderConfigsList();
		this.saveConfigs();
	}

	private toggleConfig(configId: string): void {
		const item = this.container.querySelector(`[data-config-id="${configId}"]`) as HTMLElement;
		if (!item) return;

		const details = item.querySelector('.config-details') as HTMLElement;
		const toggleBtn = item.querySelector('.button-toggle') as HTMLButtonElement;
		
		if (details.style.display === 'none') {
			details.style.display = 'block';
			toggleBtn.textContent = '▲';
			toggleBtn.title = 'Masquer';
		} else {
			details.style.display = 'none';
			toggleBtn.textContent = '▼';
			toggleBtn.title = 'Afficher';
		}
	}

	private toggleEnabled(configId: string, enabled: boolean): void {
		const config = this.data.configs.find(c => c.id === configId);
		if (!config) return;

		config.enabled = enabled;
		
		const item = this.container.querySelector(`[data-config-id="${configId}"]`);
		if (item) {
			if (enabled) {
				item.classList.remove('disabled');
				item.classList.add('enabled');
			} else {
				item.classList.remove('enabled');
				item.classList.add('disabled');
			}
		}

		this.saveConfigs();
	}

	private updateConfig(configId: string): void {
		const config = this.data.configs.find(c => c.id === configId);
		if (!config) return;

		const getVal = (selector: string) => {
			const el = this.container.querySelector(`${selector}[data-config-id="${configId}"]`) as HTMLInputElement | HTMLTextAreaElement;
			return el?.value || '';
		};

		config.label = getVal('.config-label');
		config.array_property = getVal('.config-array-property');
		config.user_id = getVal('.config-user');
		config.calendar = getVal('.config-calendar');
		config.date_field = getVal('.config-date-field');
		config.title_format = getVal('.config-title-format');
		config.id_format = getVal('.config-id-format');
		config.location_field = getVal('.config-location-field');
		config.assigned_field = getVal('.config-assigned-field');

		// Parse filter JSON
		try {
			const filterStr = getVal('.config-filter');
			config.metadata_filter = filterStr ? JSON.parse(filterStr) : {};
		} catch (e) {
			alert('Erreur dans le JSON du filtre');
			return;
		}

		// Parse description fields
		const descFieldsStr = getVal('.config-desc-fields');
		config.description_fields = descFieldsStr
			.split(',')
			.map(f => f.trim())
			.filter(f => f.length > 0);

		this.renderConfigsList();
		this.saveConfigs();
		
		// Succès message
		const saveBtn = this.container.querySelector(`.config-save[data-config-id="${configId}"]`);
		if (saveBtn) {
			const originalText = saveBtn.textContent;
			saveBtn.textContent = '✓ Enregistré';
			setTimeout(() => {
				saveBtn.textContent = originalText;
			}, 2000);
		}
	}

	private escapeHtml(text: string): string {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('animation-settings-container');
	if (container) {
		new AnimationSettings(container);
	}
});

export default AnimationSettings;
