<?php

namespace OCA\CRM\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class AdminSettings implements ISettings {

    private IConfig $config;
    private IGroupManager $groupManager;
    private IUserManager $userManager;
    private LoggerInterface $logger;

    public function __construct(IConfig $config, IGroupManager $groupManager, IUserManager $userManager, LoggerInterface $logger) {
        $this->config = $config;
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
        $this->logger = $logger;
    }

    public function getForm(): TemplateResponse {
        $groups = array_map(fn($g) => $g->getGID(), $this->groupManager->search(''));
        
        // Récupérer tous les utilisateurs avec plus de détails
        $allUsers = $this->userManager->search('');
        $users = [];
        $debugUsers = [];
        
        foreach ($allUsers as $user) {
            $uid = $user->getUID();
            $enabled = $user->isEnabled();
            $debugUsers[] = ['uid' => $uid, 'enabled' => $enabled];
            
            if ($enabled) {
                $users[] = $uid;
            }
        }
        
        // Debug dans les logs Nextcloud
        $this->logger->info('Debug utilisateurs - Total trouvés: ' . count($allUsers));
        $this->logger->info('Debug utilisateurs - Détails: ' . json_encode($debugUsers));
        $this->logger->info('Debug utilisateurs - Actifs: ' . json_encode($users));
        
        // S'assurer qu'il y a au moins l'utilisateur admin
        if (empty($users)) {
            $users = ['admin'];
        }
        
        // Ajouter admin s'il n'est pas déjà présent
        if (!in_array('admin', $users)) {
            array_unshift($users, 'admin');
        }
        
        // Trier les utilisateurs pour un affichage cohérent
        sort($users);

        return new TemplateResponse(
            'crm',
            'admin-settings',
            [
                'config' => $this->config->getAppValue('crm', 'config', '[]'),
                'config_path' => $this->config->getAppValue('crm', 'config_path', '/apps/crm/config'),
                'vault_path' => $this->config->getAppValue('crm', 'vault_path', 'vault'),
                'groups'   => $groups,
                'users'    => $users,
                // Paramètres de synchronisation globaux
                'sync_contacts_global_enabled' => $this->config->getAppValue('crm', 'sync_contacts_global_enabled', '0') === '1',
                'sync_contacts_global_class' => $this->config->getAppValue('crm', 'sync_contacts_global_class', 'Personne'),
                'sync_contacts_global_mapping' => $this->config->getAppValue('crm', 'sync_contacts_global_mapping', '{}'),
                'sync_contacts_global_filter' => $this->config->getAppValue('crm', 'sync_contacts_global_filter', '{}'),
                'sync_contacts_configs' => $this->config->getAppValue('crm', 'sync_contacts_configs', '[]'),
                
                'sync_calendar_global_enabled' => $this->config->getAppValue('crm', 'sync_calendar_global_enabled', '0') === '1',
                'sync_calendar_global_class' => $this->config->getAppValue('crm', 'sync_calendar_global_class', 'Action'),
                'sync_calendar_global_mapping' => $this->config->getAppValue('crm', 'sync_calendar_global_mapping', '{}'),
                'sync_calendar_array_properties' => $this->config->getAppValue('crm', 'sync_calendar_array_properties', '{}'),
                'sync_calendar_global_filter' => $this->config->getAppValue('crm', 'sync_calendar_global_filter', '{}'),
                'sync_calendar_configs' => $this->config->getAppValue('crm', 'sync_calendar_configs', '[]'),
                
                // Configurations animations
                'animation_configs' => $this->config->getAppValue('crm', 'animation_configs', '[]'),
                
                // Ajout des exemples et données de debug pour le frontend
                'debug_users' => $debugUsers,
                'metadata_mapping_example' => json_encode([
                    'category' => 'nextcloud_category',
                    'status' => 'nextcloud_status',
                    'priority' => 'nextcloud_priority'
                ], JSON_PRETTY_PRINT),
                'filter_example' => json_encode([
                    'field' => 'category',
                    'operator' => 'equals',
                    'value' => 'client'
                ], JSON_PRETTY_PRINT),
                'contact_config_example' => json_encode([
                    'id' => 'config_1',
                    'enabled' => true,
                    'user' => 'admin',
                    'addressbook' => 'contacts',
                    'filter' => [
                        'field' => 'category',
                        'operator' => 'equals',
                        'value' => 'client'
                    ]
                ], JSON_PRETTY_PRINT),
                'calendar_config_example' => json_encode([
                    'id' => 'config_1',
                    'enabled' => true,
                    'user' => 'admin',
                    'calendar' => 'personal',
                    'filter' => [
                        'field' => 'priority',
                        'operator' => 'equals',
                        'value' => 'high'
                    ]
                ], JSON_PRETTY_PRINT)
            ]
        );
    }

    public function getSection(): string {
        return 'additional';
    }

    public function getPriority(): int {
        return 50;
    }
}
