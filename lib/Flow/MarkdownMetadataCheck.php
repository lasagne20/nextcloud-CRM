<?php

declare(strict_types=1);

namespace OCA\CRM\Flow;

use OCP\IL10N;
use OCP\WorkflowEngine\IFileCheck;
use OCA\WorkflowEngine\Entity\File;
use OCA\WorkflowEngine\Check\TFileCheck;
use OCA\WorkflowEngine\Check\AbstractStringCheck;

class MarkdownMetadataCheck extends AbstractStringCheck implements IFileCheck {
    use TFileCheck;

    public function __construct(IL10N $l) {
        parent::__construct($l);
    }

    /**
     * Required by AbstractStringCheck - returns the value to check against
     */
    protected function getActualValue(): string {
        if (!$this->storage || !$this->path) {
            return '';
        }

        try {
            // file_get_contents can return false on error
            $content = $this->storage->file_get_contents($this->path);
            
            // Check if content is valid
            if ($content === false || !is_string($content)) {
                return '';
            }
            
            // Only process markdown files
            if (!$this->isMarkdownFile($this->path)) {
                return '';
            }
            
            $metadata = $this->parseFrontmatter($content);
            
            // Return all metadata as key:value pairs joined by newlines
            $values = [];
            foreach ($metadata as $key => $val) {
                if (is_array($val)) {
                    $val = implode(', ', $val);
                }
                $values[] = "$key:$val";
            }
            return implode("\n", $values);
        } catch (\Exception $e) {
            // Log the error for debugging
            $logger = \OC::$server->get(\Psr\Log\LoggerInterface::class);
            $logger->warning('[CRM] Error getting markdown metadata for ' . $this->path . ': ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Check if file is a markdown file
     */
    private function isMarkdownFile(string $path): bool {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, ['md', 'markdown']);
    }

    /**
     * @return string Classe de l'entité supportée
     */
    public function supportedEntities(): array {
        return [File::class];
    }

    /**
     * @param int $scope
     * @return bool
     */
    public function isAvailableForScope(int $scope): bool {
        $logger = \OC::$server->get(\Psr\Log\LoggerInterface::class);
        $logger->info('[CRM Check] isAvailableForScope(' . $scope . ') called - returning true');
        
        // Available for all scopes (user and admin)
        return true;
    }

    /**
     * Opérateurs supportés - override pour personnaliser
     */
    public function validateCheck($operator, $value): void {
        if (!in_array($operator, ['is', '!is', 'matches', '!matches'])) {
            throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
        }
    }

    /**
     * Parse le frontmatter YAML
     */
    private function parseFrontmatter(string $content): array {
        if (!preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
            return [];
        }

        $yamlContent = $matches[1];
        $metadata = [];

        $lines = explode("\n", $yamlContent);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, ':') === false) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Gérer les tableaux [val1, val2]
            if (preg_match('/^\[(.*)\]$/', $value, $arrayMatch)) {
                $values = array_map('trim', explode(',', $arrayMatch[1]));
                $metadata[$key] = $values;
            } else {
                // Retirer les guillemets si présents
                $value = trim($value, "'\"");
                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }

    /**
     * Obtenir des valeurs suggérées pour l'autocomplétion
     */
    public function getValidValues(): array {
        return [
            'Classe:Personne',
            'Classe:Action',
            'Classe:Institution',
            'Classe:Lieu',
            'Type:Réunion',
            'Type:Appel',
            'Statut:En cours',
            'Statut:Terminé',
        ];
    }
}
