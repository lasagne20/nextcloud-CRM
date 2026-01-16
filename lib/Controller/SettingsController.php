<?php

namespace OCA\CRM\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller {
    private IConfig $config;

    public function __construct(string $appName, IRequest $request, IConfig $config) {
        parent::__construct($appName, $request);
        $this->config = $config;
    }

    /**
     * @AdminRequired
     * @CSRFCheck
     */
    public function save(array $config): DataResponse {
        $this->config->setAppValue($this->appName, 'config', json_encode($config));
        return new DataResponse(['status' => 'ok']);
    }

    /**
     * @AdminRequired
     * @CSRFCheck
     */
    public function saveGeneralSettings(string $config_path, string $vault_path): DataResponse {
        $this->config->setAppValue($this->appName, 'config_path', $config_path);
        $this->config->setAppValue($this->appName, 'vault_path', $vault_path);
        return new DataResponse(['status' => 'ok', 'message' => 'Paramètres enregistrés avec succès']);
    }

    /**
     * @AdminRequired
     * @NoCSRFRequired
     */
    public function getGeneralSettings(): DataResponse {
        return new DataResponse([
            'config_path' => $this->config->getAppValue($this->appName, 'config_path', '/apps/crm/config'),
            'vault_path' => $this->config->getAppValue($this->appName, 'vault_path', 'vault')
        ]);
    }

    /**
     * @AdminRequired
     * @CSRFCheck
     */
    public function saveSyncSettings(
        bool $sync_contacts_global_enabled = false,
        string $sync_contacts_global_class = 'Personne',
        string $sync_contacts_global_mapping = '{}',
        string $sync_contacts_global_filter = '{}',
        string $sync_contacts_configs = '[]',
        bool $sync_calendar_global_enabled = false,
        string $sync_calendar_global_class = 'Action',
        string $sync_calendar_global_mapping = '{}',
        string $sync_calendar_array_properties = '{}',
        string $sync_calendar_global_filter = '{}',
        string $sync_calendar_configs = '[]',
        string $animation_configs = '[]'
    ): DataResponse {
        $this->config->setAppValue($this->appName, 'sync_contacts_global_enabled', $sync_contacts_global_enabled ? '1' : '0');
        $this->config->setAppValue($this->appName, 'sync_contacts_global_class', $sync_contacts_global_class);
        $this->config->setAppValue($this->appName, 'sync_contacts_global_mapping', $sync_contacts_global_mapping);
        $this->config->setAppValue($this->appName, 'sync_contacts_global_filter', $sync_contacts_global_filter);
        $this->config->setAppValue($this->appName, 'sync_contacts_configs', $sync_contacts_configs);
        
        $this->config->setAppValue($this->appName, 'sync_calendar_global_enabled', $sync_calendar_global_enabled ? '1' : '0');
        $this->config->setAppValue($this->appName, 'sync_calendar_global_class', $sync_calendar_global_class);
        $this->config->setAppValue($this->appName, 'sync_calendar_global_mapping', $sync_calendar_global_mapping);
        $this->config->setAppValue($this->appName, 'sync_calendar_array_properties', $sync_calendar_array_properties);
        $this->config->setAppValue($this->appName, 'sync_calendar_global_filter', $sync_calendar_global_filter);
        $this->config->setAppValue($this->appName, 'sync_calendar_configs', $sync_calendar_configs);
        
        $this->config->setAppValue($this->appName, 'animation_configs', $animation_configs);
        
        return new DataResponse(['status' => 'ok', 'message' => 'Paramètres de synchronisation enregistrés avec succès']);
    }

    /**
     * @AdminRequired
     */
    public function listMdFiles(): DataResponse {
        $path = \OC::$SERVERROOT . '/data/md'; // adapte le chemin
        $files = [];

        if (is_dir($path)) {
            foreach (scandir($path) as $file) {
                if (substr($file, -3) === '.md') {
                    $files[] = $file;
                }
            }
        }

        return new DataResponse($files);
    }

    /**
     * @AdminRequired
     * @NoCSRFRequired
     */
    public function getUserCalendars(string $userId): DataResponse {
        try {
            /** @var \OCA\DAV\CalDAV\CalDavBackend $calDavBackend */
            $calDavBackend = \OC::$server->query(\OCA\DAV\CalDAV\CalDavBackend::class);
            
            // Récupérer tous les calendriers de l'utilisateur
            $calendars = $calDavBackend->getCalendarsForUser("principals/users/$userId");
            
            $calendarList = [];
            foreach ($calendars as $calendar) {
                $calendarList[] = [
                    'uri' => $calendar['uri'],
                    'name' => $calendar['{DAV:}displayname'] ?? $calendar['uri'],
                    'color' => $calendar['{http://apple.com/ns/ical/}calendar-color'] ?? '#0082c9'
                ];
            }
            
            return new DataResponse(['calendars' => $calendarList]);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @AdminRequired
     * @CSRFCheck
     */
    public function saveAnimationConfigs(string $animation_configs = '[]'): DataResponse {
        try {
            // Valider le JSON
            $configs = json_decode($animation_configs, true);
            if ($configs === null && $animation_configs !== '[]') {
                return new DataResponse(['error' => 'JSON invalide'], 400);
            }
            
            // Sauvegarder uniquement les animation_configs
            $this->config->setAppValue($this->appName, 'animation_configs', $animation_configs);
            
            return new DataResponse([
                'status' => 'ok', 
                'message' => 'Configurations enregistrées avec succès',
                'count' => count($configs)
            ]);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

}
