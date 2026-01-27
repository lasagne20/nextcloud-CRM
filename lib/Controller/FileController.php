<?php

declare(strict_types=1);

namespace OCA\CRM\Controller;

use OCA\CRM\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class FileController extends Controller {
    private IRootFolder $rootFolder;
    private string $userId;
    private LoggerInterface $logger;
    private IConfig $config;

    public function __construct(
        string $appName,
        \OCP\IRequest $request,
        IRootFolder $rootFolder,
        IUserSession $userSession,
        LoggerInterface $logger,
        IConfig $config
    ) {
        parent::__construct($appName, $request);
        $this->rootFolder = $rootFolder;
        $this->userId = $userSession->getUser()->getUID();
        $this->logger = $logger;
        $this->config = $config;
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getMarkdownFile(string $name): DataResponse {
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            $files = $userFolder->searchByMime('text/markdown');

            $file = null;
            foreach ($files as $f) {
                if ($f->getName() === $name) {
                    $file = $f;
                    break;
                }
            }

            if ($file === null) {
                return new DataResponse(['error' => 'Fichier introuvable'], 404);
            }

            $content = stream_get_contents($file->fopen('r'));
            return new DataResponse(['content' => $content]);
        } catch (\Exception $e) {
            $this->logger->error('Error reading markdown file: ' . $e->getMessage());
            return new DataResponse(['error' => 'Erreur lors de la lecture du fichier: ' . $e->getMessage()], 500);
        }
    }



    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listMarkdownFiles(): DataResponse {
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            $this->logger->info('Listing markdown files for user: ' . $this->userId);
            
            $files = $userFolder->searchByMime('text/markdown');

            $result = [];
            foreach ($files as $file) {
                $result[] = [
                    'name' => $file->getName(),
                    'size' => $file->getSize(),
                    'path' => $file->getPath(),
                    'mtime' => $file->getMTime(),
                ];
            }

            $this->logger->info('Found ' . count($result) . ' markdown files');
            return new DataResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('Error listing markdown files: ' . $e->getMessage());
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function saveMarkdownFile(string $path, string $content): DataResponse {
        try {
            $this->logger->info('Attempting to save file: ' . $path);
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            
            // Extraire le nom du fichier depuis le path
            $fileName = basename($path);
            $this->logger->info('Extracted filename: ' . $fileName);
            
            // Chercher le fichier par nom
            $files = $userFolder->searchByMime('text/markdown');
            
            $file = null;
            foreach ($files as $f) {
                if ($f->getName() === $fileName) {
                    $file = $f;
                    $this->logger->info('Found file: ' . $f->getPath());
                    break;
                }
            }

            if ($file === null) {
                $this->logger->error('File not found: ' . $fileName);
                return new DataResponse(['error' => 'Fichier introuvable: ' . $fileName], 404);
            }

            // Ouvrir en écriture et sauvegarder
            $file->putContent($content);

            $this->logger->info('File saved successfully: ' . $file->getPath());
            return new DataResponse(['success' => true, 'path' => $file->getPath()]);
        } catch (\Exception $e) {
            $this->logger->error('Error saving markdown file: ' . $e->getMessage());
            return new DataResponse(['error' => 'Impossible de sauvegarder le fichier: ' . $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getConfig(string $name): DataResponse {
        try {
            // Récupérer le chemin de configuration depuis les paramètres
            $configPathSetting = $this->config->getAppValue('crm', 'config_path', '/apps/crm/config');
            $this->logger->info('Reading config file: ' . $name . ' from path: ' . $configPathSetting);
            
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            
            // Retirer le slash initial si présent
            if (strpos($configPathSetting, '/') === 0) {
                $configPathSetting = ltrim($configPathSetting, '/');
            }
            
            try {
                $configFolder = $userFolder->get($configPathSetting);
            } catch (\Exception $e) {
                $this->logger->error('Config folder not found: ' . $configPathSetting);
                return new DataResponse(['error' => 'Dossier de configuration introuvable'], 404);
            }
            
            // Chercher le fichier YAML
            $fileName = $name . '.yaml';
            try {
                $file = $configFolder->get($fileName);
                if (!($file instanceof \OCP\Files\File)) {
                    return new DataResponse(['error' => 'Le chemin ne pointe pas vers un fichier'], 400);
                }
                
                $content = $file->getContent();
                return new DataResponse(['content' => $content]);
            } catch (\Exception $e) {
                $this->logger->error('Config file not found: ' . $fileName);
                return new DataResponse(['error' => 'Configuration introuvable: ' . $fileName], 404);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error reading config file: ' . $e->getMessage());
            return new DataResponse(['error' => 'Erreur lors de la lecture de la configuration: ' . $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listConfigs(): DataResponse {
        try {
            // Récupérer le chemin de configuration depuis les paramètres
            $configPathSetting = $this->config->getAppValue('crm', 'config_path', '/apps/crm/config');
            $this->logger->info('Config path from settings: ' . $configPathSetting);
            
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            
            // Si le chemin commence par /, c'est un chemin absolu depuis la racine utilisateur
            // Sinon c'est un chemin relatif
            if (strpos($configPathSetting, '/') === 0) {
                $configPathSetting = ltrim($configPathSetting, '/');
            }
            
            try {
                $configFolder = $userFolder->get($configPathSetting);
            } catch (\Exception $e) {
                $this->logger->error('Config folder not found: ' . $configPathSetting);
                return new DataResponse(['error' => 'Dossier de configuration introuvable: ' . $configPathSetting], 404);
            }
            
            if (!($configFolder instanceof \OCP\Files\Folder)) {
                return new DataResponse(['error' => 'Le chemin ne pointe pas vers un dossier'], 400);
            }

            $configs = [];
            $files = $configFolder->getDirectoryListing();
            
            foreach ($files as $file) {
                // Check if it's a file (not a folder) and has .yaml extension
                if ($file instanceof \OCP\Files\File && $file->getExtension() === 'yaml') {
                    $fullPath = $file->getPath();
                    // Remove the /username/files/ prefix to get the relative path
                    $relativePath = preg_replace('#^/' . preg_quote($this->userId, '#') . '/files/#', '', $fullPath);
                    
                    $configs[] = [
                        'name' => pathinfo($file->getName(), PATHINFO_FILENAME),
                        'file' => $file->getName(),
                        'path' => $relativePath,  // Use relative path without /username/files/ prefix
                        'size' => $file->getSize(),
                        'mtime' => $file->getMTime()
                    ];
                }
            }

            $this->logger->info('Found ' . count($configs) . ' YAML config files');
            return new DataResponse($configs);
        } catch (\Exception $e) {
            $this->logger->error('Error listing config files: ' . $e->getMessage());
            return new DataResponse(['error' => 'Erreur lors du listage des configurations: ' . $e->getMessage()], 500);
        }
    }
}
