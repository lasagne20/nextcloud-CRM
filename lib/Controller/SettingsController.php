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

}
