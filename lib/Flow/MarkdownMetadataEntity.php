<?php

declare(strict_types=1);

namespace OCA\CRM\Flow;

use OCP\EventDispatcher\Event;
use OCP\Files\Node;
use OCP\Files\File;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IRuleMatcher;
use OCP\IL10N;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;

class MarkdownMetadataEntity implements IEntity {

    private IL10N $l;
    private ?Node $node = null;
    private array $metadata = [];

    public function __construct(IL10N $l) {
        $this->l = $l;
    }

    public function getName(): string {
        return $this->l->t('Markdown Metadata');
    }

    public function getIcon(): string {
        return 'icon-file';
    }

    public function getEvents(): array {
        return [
            NodeWrittenEvent::class,
            NodeCreatedEvent::class,
        ];
    }

    public function prepareRuleMatcher(IRuleMatcher $ruleMatcher, string $eventName, Event $event): void {
        if ($event instanceof NodeWrittenEvent || $event instanceof NodeCreatedEvent) {
            $node = $event->getNode();
            
            // Vérifier que c'est un fichier Markdown
            if ($node instanceof File && $node->getMimetype() === 'text/markdown') {
                $this->node = $node;
                
                // Extraire les métadonnées
                try {
                    $content = $node->getContent();
                    $this->metadata = $this->parseFrontmatter($content);
                    
                    // Exposer les métadonnées au RuleMatcher sous forme de hash
                    // Le hash est utilisé par les checks pour vérifier les conditions
                    $metadataHash = json_encode($this->metadata);
                    
                    $ruleMatcher->setEntitySubject($this, $node);
                    $ruleMatcher->setFileInfo($node->getStorage(), $node->getInternalPath());
                } catch (\Exception $e) {
                    // En cas d'erreur, on ne fait rien
                }
            }
        }
    }

    /**
     * Parse le frontmatter YAML d'un fichier Markdown
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
     * Retourne les métadonnées extraites
     */
    public function getMetadata(): array {
        return $this->metadata;
    }

    /**
     * Retourne une métadonnée spécifique
     */
    public function getMetadataValue(string $key): ?string {
        if (isset($this->metadata[$key])) {
            $value = $this->metadata[$key];
            if (is_array($value)) {
                return implode(', ', $value);
            }
            return (string)$value;
        }
        return null;
    }

    /**
     * Génère un hash unique pour l'entité basé sur les métadonnées
     */
    public function generateHash(): string {
        return json_encode($this->metadata);
    }

    /**
     * Vérifie si l'utilisateur est autorisé à utiliser cette entité
     */
    public function isLegitimatedForUserId(string $userId): bool {
        // Tous les utilisateurs peuvent utiliser cette entité pour les fichiers Markdown
        return true;
    }
}
