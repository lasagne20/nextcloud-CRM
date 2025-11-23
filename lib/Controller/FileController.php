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
use Psr\Log\LoggerInterface;

class FileController extends Controller {
    private IRootFolder $rootFolder;
    private string $userId;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        \OCP\IRequest $request,
        IRootFolder $rootFolder,
        IUserSession $userSession,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->rootFolder = $rootFolder;
        $this->userId = $userSession->getUser()->getUID();
        $this->logger = $logger;
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
            $configPath = __DIR__ . '/../../config/' . $name . '.yaml';
            
            if (!file_exists($configPath)) {
                return new DataResponse(['error' => 'Configuration introuvable'], 404);
            }

            $content = file_get_contents($configPath);
            return new DataResponse(['content' => $content]);
        } catch (\Exception $e) {
            $this->logger->error('Error reading config file: ' . $e->getMessage());
            return new DataResponse(['error' => 'Erreur lors de la lecture de la configuration: ' . $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listConfigs(): DataResponse {
        try {
            $configPath = __DIR__ . '/../../config/';
            
            if (!is_dir($configPath)) {
                return new DataResponse(['error' => 'Dossier de configuration introuvable'], 404);
            }

            $configs = [];
            $files = scandir($configPath);
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'yaml') {
                    $configs[] = [
                        'name' => pathinfo($file, PATHINFO_FILENAME),
                        'file' => $file
                    ];
                }
            }

            return new DataResponse($configs);
        } catch (\Exception $e) {
            $this->logger->error('Error listing config files: ' . $e->getMessage());
            return new DataResponse(['error' => 'Erreur lors du listage des configurations'], 500);
        }
    }
}
