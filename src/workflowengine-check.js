/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Register Markdown Metadata check with Nextcloud Flow
// This needs to run BEFORE the Vue app is mounted
(function() {
	const registerCheck = function() {
		if (!window.OCA || !window.OCA.WorkflowEngine) {
			console.warn('[CRM] WorkflowEngine not yet available, retrying...');
			setTimeout(registerCheck, 100);
			return;
		}

		console.log('[CRM] Registering Markdown Metadata check...');
		
		try {
			window.OCA.WorkflowEngine.registerCheck({
				class: 'OCA\\CRM\\Flow\\MarkdownMetadataCheck',
				name: t('crm', 'Markdown Metadata'),
				operators: [
					{ operator: 'matches', name: t('workflowengine', 'matches') },
					{ operator: '!matches', name: t('workflowengine', 'does not match') },
					{ operator: 'is', name: t('workflowengine', 'is') },
					{ operator: '!is', name: t('workflowengine', 'is not') },
				],
				placeholder: (check) => {
					if (check.operator === 'matches' || check.operator === '!matches') {
						return 'Classe:.*'
					}
					return 'Classe:Personne'
				},
				validate: (check) => {
					// Basic validation - check if value is not empty
					return check.value && check.value.trim().length > 0;
				},
			});
			
			console.log('[CRM] Markdown Metadata check registered successfully');
		} catch (e) {
			console.error('[CRM] Failed to register check:', e);
		}
	};

	// Try to register immediately, or wait for DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', registerCheck);
	} else {
		registerCheck();
	}
})();
