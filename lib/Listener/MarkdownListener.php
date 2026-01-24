<?php
declare(strict_types=1);

namespace OCA\CRM\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeWrittenEvent;
use Psr\Log\LoggerInterface;
use OCP\Files\IRootFolder;
use OCP\Files\File;
use OCP\Contacts\IManager as ContactsManager;
use OCP\IUserSession;
use OCP\IConfig;
use OCA\DAV\CardDAV\CardDavBackend;
use Sabre\VObject\Component\VCard;
use OCA\DAV\CalDAV\CalDavBackend;

class MarkdownListener implements IEventListener {
    private LoggerInterface $logger;
    private IRootFolder $rootFolder;
    private ContactsManager $contactsManager;
    private IUserSession $userSession;
    private IConfig $config;

    public function __construct(
        LoggerInterface $logger,
        IRootFolder $rootFolder,
        IUserSession $userSession,
        IConfig $config
    ) {
        $this->logger = $logger;
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->config = $config;
    }

    public function handle(Event $event): void {
        $this->logger->info('MarkdownListener déclenché.');

        $node = $event->getNode();
        if (!$node instanceof File) return;

        $this->logger->info("Fichier écrit : " . $node->getPath());

        if ($node->getMimetype() !== 'text/markdown') return;

        try {
            $stream = $node->fopen('r');
            $text = stream_get_contents($stream);
            fclose($stream);
            
            // S'assurer que le texte est en UTF-8
            if (!mb_check_encoding($text, 'UTF-8')) {
                $text = mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true));
            }

            $metadata = $this->parseMdMetadata($text);
            $this->logger->info("Metadata extraits: " . json_encode($metadata, JSON_UNESCAPED_UNICODE));

            // Traiter les contacts si activé
            $contactsGlobalEnabled = $this->config->getAppValue('crm', 'sync_contacts_global_enabled', '0') === '1';
            $this->logger->info("Contacts global enabled: " . ($contactsGlobalEnabled ? 'OUI' : 'NON'));
            if ($contactsGlobalEnabled) {
                $this->processContactConfigs($node->getName(), $metadata);
            }

            // Traiter les calendriers si activé
            $calendarGlobalEnabled = $this->config->getAppValue('crm', 'sync_calendar_global_enabled', '0') === '1';
            if ($calendarGlobalEnabled) {
                $this->processCalendarConfigs($node->getName(), $metadata, $text);
            }

            $this->logger->info("Markdown processed: " . $node->getName());
        } catch (\Exception $e) {
            $this->logger->error("Erreur traitement Markdown: " . $e->getMessage());
        }
    }

    /**
     * Extrait le contenu markdown sans le frontmatter YAML
     */
    private function extractContentWithoutMetadata(string $content): string {
        // Chercher le frontmatter YAML entre --- et ---
        if (preg_match('/^---\s*\n.*?\n---\s*\n(.*)$/s', $content, $matches)) {
            return trim($matches[1]);
        }
        return $content;
    }

    /**
     * Échappe le texte pour le format iCalendar
     */
    private function escapeICalendarText(string $text): string {
        // Échapper selon RFC 5545
        $text = str_replace('\\', '\\\\', $text);  // Backslash d'abord
        $text = str_replace(',', '\\,', $text);     // Virgules
        $text = str_replace(';', '\\;', $text);     // Points-virgules
        $text = str_replace("\n", '\\n', $text);    // Retours à la ligne
        $text = str_replace("\r", '', $text);       // Supprimer CR
        return $text;
    }

    private function parseMdMetadata(string $content): array {
        $this->logger->info("parseMdMetadata appelé, longueur contenu: " . strlen($content));
        $this->logger->info("Début du contenu (200 premiers caractères): " . substr($content, 0, 200));
        
        // Chercher le frontmatter YAML entre --- et --- (accepter aussi ----)
        if (preg_match('/^-{3,}\s*\n(.*?)\n-{3,}/s', $content, $matches)) {
            $yamlContent = trim($matches[1]);
            // Enlever les lignes qui sont juste des délimiteurs --- ou ----
            $yamlContent = preg_replace('/^-{3,}\s*$/m', '', $yamlContent);
            $yamlContent = trim($yamlContent);
            $this->logger->info("Frontmatter YAML trouvé, longueur: " . strlen($yamlContent));
            $this->logger->info("Contenu YAML: " . $yamlContent);
            
            // Utiliser la bibliothèque YAML de Symfony
            try {
                if (class_exists('Symfony\Component\Yaml\Yaml')) {
                    $this->logger->info("Utilisation de Symfony YAML parser");
                    $data = \Symfony\Component\Yaml\Yaml::parse($yamlContent);
                    $this->logger->info("Symfony YAML parser résultat: " . json_encode($data, JSON_UNESCAPED_UNICODE));
                    return is_array($data) ? $data : [];
                }
            } catch (\Exception $e) {
                $this->logger->warning("Erreur parsing YAML avec Symfony: " . $e->getMessage());
            }
            
            // Parser amélioré pour gérer les structures YAML complexes imbriquées
            $this->logger->info("Utilisation du parser YAML personnalisé");
            $lines = explode("\n", $yamlContent);
            $data = [];
            $stack = [&$data]; // Pile de contextes pour gérer l'imbrication
            $parentIndents = [0]; // Pile des indentations parentes
            $lastIndent = 0;
            $lastKey = null;
            $lastWasArrayItem = false;
            
            foreach ($lines as $lineNum => $line) {
                $line = rtrim($line);
                
                // Ignorer les lignes vides et commentaires
                if (empty($line) || $line[0] === '#') {
                    continue;
                }
                
                // Compter l'indentation
                preg_match('/^(\s*)/', $line, $indentMatch);
                $indent = strlen($indentMatch[1]);
                
                // Dépiler si on remonte dans l'indentation
                while (count($parentIndents) > 1 && $indent <= end($parentIndents)) {
                    array_pop($stack);
                    array_pop($parentIndents);
                }
                
                // Ligne avec élément de tableau (commence par -)
                if (preg_match('/^\s*-\s*(.*)$/', $line, $match)) {
                    $value = trim($match[1]);
                    $current = &$stack[count($stack) - 1];
                    
                    // Si la ligne après le - a une clé: valeur
                    if (preg_match('/^([a-zA-Z0-9_\-\pL]+):\s*(.*)$/u', $value, $kvMatch)) {
                        // Nouveau objet dans le tableau
                        $newObj = [$kvMatch[1] => $this->cleanYamlValue($kvMatch[2])];
                        $current[] = $newObj;
                        
                        // Mettre à jour le contexte pour pointer vers ce nouvel objet
                        $stack[] = &$current[count($current) - 1];
                        $parentIndents[] = $indent;
                        $lastWasArrayItem = true;
                    } 
                    // Sinon, c'est une simple valeur dans le tableau
                    else {
                        $current[] = $this->cleanYamlValue($value);
                        $lastWasArrayItem = false;
                    }
                }
                // Ligne avec clé: valeur
                elseif (preg_match('/^\s*([a-zA-Z0-9_\-\pL]+):\s*(.*)$/u', $line, $match)) {
                    $key = $match[1];
                    $value = $match[2];
                    $current = &$stack[count($stack) - 1];
                    
                    // Si la valeur est vide, c'est probablement un tableau ou objet
                    if (empty($value)) {
                        $current[$key] = [];
                        // Ajouter ce nouveau conteneur au contexte
                        $stack[] = &$current[$key];
                        $parentIndents[] = $indent;
                        $lastKey = $key;
                    } else {
                        $current[$key] = $this->cleanYamlValue($value);
                    }
                    $lastWasArrayItem = false;
                }
            }
            
            $this->logger->info("Parser personnalisé résultat: " . json_encode($data, JSON_UNESCAPED_UNICODE));
            return $data;
        }
        
        $this->logger->warning("Aucun frontmatter YAML trouvé dans le contenu");
        return [];
    }
    
    /**
     * Nettoie une valeur YAML (enlève les guillemets, trim)
     */
    private function cleanYamlValue(string $value): string {
        $value = trim($value);
        // Enlever les guillemets doubles ou simples qui encadrent la valeur
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        return $value;
    }

    /**
     * Récupère une valeur de métadonnée de manière insensible à la casse
     */
    private function getMetadataValue(array $metadata, string $key): ?string {
        // Vérifier la clé exacte d'abord
        if (isset($metadata[$key])) {
            return is_string($metadata[$key]) ? $metadata[$key] : null;
        }
        
        // Sinon, chercher en insensible à la casse
        foreach ($metadata as $metaKey => $metaValue) {
            // Ignorer les clés numériques et convertir en string pour strcasecmp
            if (is_numeric($metaKey)) {
                continue;
            }
            if (strcasecmp((string)$metaKey, $key) === 0) {
                if (is_string($metaValue)) {
                    return $metaValue;
                }
            }
        }
        
        return null;
    }

    /**
     * Résout un chemin complexe dans les métadonnées (ex: postes[0].institution)
     */
    private function resolveMetadataPath(array $metadata, string $path): ?string {
        $this->logger->info("resolveMetadataPath appelé avec path='$path'");
        
        // Si pas de notation tableau/objet, utiliser la méthode normale
        if (!preg_match('/[\[\].]/', $path)) {
            return $this->getMetadataValue($metadata, $path);
        }
        
        // Parser le chemin (ex: "postes[0].institution")
        $parts = [];
        $matches = [];
        
        // Diviser par les points
        $segments = explode('.', $path);
        
        foreach ($segments as $segment) {
            if (preg_match('/^([^[]+)\[(\d+)\]$/', $segment, $matches)) {
                // Segment avec index (ex: "postes[0]")
                $parts[] = ['type' => 'array', 'key' => $matches[1], 'index' => (int)$matches[2]];
            } else {
                // Segment simple (ex: "institution")
                $parts[] = ['type' => 'key', 'key' => $segment];
            }
        }
        
        $this->logger->info("Parties du chemin: " . json_encode($parts, JSON_UNESCAPED_UNICODE));
        
        // Naviguer dans les métadonnées
        $current = $metadata;
        
        foreach ($parts as $part) {
            if ($part['type'] === 'array') {
                // Accès à un tableau
                $key = $part['key'];
                $index = $part['index'];
                
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $this->logger->info("Clé '$key' n'existe pas ou n'est pas un tableau");
                    return null;
                }
                
                if (!isset($current[$key][$index])) {
                    $this->logger->info("Index $index n'existe pas dans le tableau '$key'");
                    return null;
                }
                
                $current = $current[$key][$index];
                $this->logger->info("Navigation vers $key[$index]: " . json_encode($current, JSON_UNESCAPED_UNICODE));
            } else {
                // Accès à une clé simple
                $key = $part['key'];
                
                if (!isset($current[$key])) {
                    $this->logger->info("Clé '$key' n'existe pas");
                    return null;
                }
                
                $current = $current[$key];
                $this->logger->info("Navigation vers $key: " . json_encode($current, JSON_UNESCAPED_UNICODE));
            }
        }
        
        $result = is_string($current) ? $current : json_encode($current, JSON_UNESCAPED_UNICODE);
        $this->logger->info("Résultat final pour '$path': $result");
        return $result;
    }

    private function matchesMetadataFilter(array $metadata): bool {
        // Récupérer le filtre depuis la config (format JSON: {"key":"value", "key2":"value2"})
        $filterJson = $this->config->getAppValue('crm', 'sync_metadata_filter', '{}');
        $filter = json_decode($filterJson, true);
        
        if (empty($filter)) {
            return true; // Pas de filtre = tout passe
        }
        
        // Vérifier que toutes les conditions du filtre sont remplies
        foreach ($filter as $key => $expectedValue) {
            if (!isset($metadata[$key]) || $metadata[$key] !== $expectedValue) {
                return false;
            }
        }
        
        return true;
    }

    private function resolveTargetUser(array $metadata, string $configKey): string {
        // 1. Essayer de résoudre depuis les métadonnées
        $userMappingJson = $this->config->getAppValue('crm', 'sync_user_mapping', '{}');
        $userMapping = json_decode($userMappingJson, true);
        
        if (!empty($userMapping)) {
            foreach ($userMapping as $metadataField => $mappingRules) {
                if (isset($metadata[$metadataField])) {
                    $metadataValue = $metadata[$metadataField];
                    if (isset($mappingRules[$metadataValue])) {
                        return $mappingRules[$metadataValue];
                    }
                }
            }
        }
        
        // 2. Utiliser l'utilisateur configuré par défaut
        $targetUserId = $this->config->getAppValue('crm', $configKey, '');
        if (!empty($targetUserId)) {
            return $targetUserId;
        }
        
        // 3. Fallback sur l'utilisateur connecté
        $user = $this->userSession->getUser();
        if ($user) {
            return $user->getUID();
        }
        
        throw new \Exception("Aucun utilisateur cible trouvé");
    }

    private function processContactConfigs(string $name, array $metadata): void {
        $globalClass = $this->config->getAppValue('crm', 'sync_contacts_global_class', 'Personne');
        $globalMappingJson = $this->config->getAppValue('crm', 'sync_contacts_global_mapping', '{}');
        $globalMapping = json_decode($globalMappingJson, true);
        $globalFilter = json_decode($this->config->getAppValue('crm', 'sync_contacts_global_filter', '{}'), true);
        
        $this->logger->info("=== DEBUG SETTINGS ===");
        $this->logger->info("Raw globalMappingJson: '$globalMappingJson'");
        
        // Tentative de correction du JSON malformé
        if ($globalMappingJson && $globalMappingJson !== '{}') {
            // Compter les accolades ouvrantes et fermantes
            $openBraces = substr_count($globalMappingJson, '{');
            $closeBraces = substr_count($globalMappingJson, '}');
            
            if ($openBraces > $closeBraces) {
                $missingBraces = $openBraces - $closeBraces;
                $globalMappingJson .= str_repeat(' }', $missingBraces);
                $this->logger->info("JSON corrigé: '$globalMappingJson'");
            }
        }
        
        $globalMapping = json_decode($globalMappingJson, true);
        $this->logger->info("Decoded globalMapping: " . var_export($globalMapping, true));
        $this->logger->info("json_last_error: " . json_last_error_msg());
        
        // Vérifier toutes les valeurs de config
        $allSettings = [
            'sync_contacts_global_enabled' => $this->config->getAppValue('crm', 'sync_contacts_global_enabled', 'NOT_SET'),
            'sync_contacts_global_class' => $this->config->getAppValue('crm', 'sync_contacts_global_class', 'NOT_SET'),
            'sync_contacts_global_mapping' => $this->config->getAppValue('crm', 'sync_contacts_global_mapping', 'NOT_SET'),
            'sync_contacts_global_filter' => $this->config->getAppValue('crm', 'sync_contacts_global_filter', 'NOT_SET'),
            'sync_contacts_configs' => $this->config->getAppValue('crm', 'sync_contacts_configs', 'NOT_SET')
        ];
        $this->logger->info("=== TOUTES LES SETTINGS ===");
        foreach ($allSettings as $key => $value) {
            $this->logger->info("$key = '$value'");
        }
        
        // Vérifier si c'est la bonne classe (insensible à la casse)
        $metadataClass = $this->getMetadataValue($metadata, 'Classe');
        
        if (empty($metadataClass) || $metadataClass !== $globalClass) {
            $this->logger->info("Contact ignoré - classe incorrecte. Attendu: " . $globalClass . ", Reçu: " . ($metadataClass ?? 'null'));
            return;
        }
        
        // Vérifier le filtre global
        if (!$this->matchesFilter($metadata, $globalFilter)) {
            $this->logger->info("Contact ignoré par le filtre global: " . $name);
            return;
        }
        
        // Récupérer toutes les configurations
        $configsJson = $this->config->getAppValue('crm', 'sync_contacts_configs', '[]');
        $configs = json_decode($configsJson, true) ?? [];
        
        $this->logger->info("=== MAPPING GLOBAL ===");
        $this->logger->info(json_encode($globalMapping, JSON_UNESCAPED_UNICODE));
        
        foreach ($configs as $config) {
            if (!$config['enabled']) {
                continue;
            }
            
            // Vérifier le filtre spécifique de cette config
            $specificFilter = $config['metadata_filter'] ?? [];
            
            if (!$this->matchesFilter($metadata, $specificFilter)) {
                continue;
            }
            
            $this->logger->info("TRAITEMENT du contact pour config {$config['id']} vers utilisateur {$config['user_id']}");
            // Traiter ce contact pour cette configuration
            $this->addContactForConfig($name, $metadata, $globalMapping, $config);
        }
    }

    private function processCalendarConfigs(string $name, array $metadata, string $text): void {
        $globalClass = $this->config->getAppValue('crm', 'sync_calendar_global_class', 'Action');
        
        // Debug: afficher toutes les clés disponibles
        $this->logger->info("Clés disponibles dans metadata: " . implode(', ', array_keys($metadata)));
        
        // Vérifier si c'est la bonne classe (insensible à la casse)
        $metadataClass = $this->getMetadataValue($metadata, 'Classe');
        $this->logger->info("getMetadataValue('Classe') retourne: " . var_export($metadataClass, true) . " (type: " . gettype($metadataClass) . ")");
        
        if (empty($metadataClass) || $metadataClass !== $globalClass) {
            $this->logger->info("Calendrier ignoré - classe incorrecte. Attendu: " . $globalClass . ", Reçu: " . ($metadataClass ?? 'null'));
            return;
        }
        
        // Récupérer les configurations d'animations
        $animationConfigsJson = $this->config->getAppValue('crm', 'animation_configs', '[]');
        $animationConfigs = json_decode($animationConfigsJson, true) ?? [];
        
        $this->logger->info("Traitement calendrier pour $name avec " . count($animationConfigs) . " configs animations");
        
        foreach ($animationConfigs as $config) {
            if (!$config['enabled']) {
                $this->logger->info("Config animation {$config['id']} désactivée");
                continue;
            }
            
            // Vérifier le filtre de cette config
            $configFilter = $config['metadata_filter'] ?? [];
            if (!$this->matchesFilter($metadata, $configFilter)) {
                $this->logger->info("Fichier ignoré par le filtre de la config {$config['id']}: " . $name);
                continue;
            }
            
            $this->logger->info("Traitement animation config {$config['id']} pour $name");
            
            // Traiter cette animation pour cette configuration
            $this->processAnimationConfig($name, $metadata, $text, $config);
        }
    }

    private function matchesFilter(array $metadata, array $filter): bool {
        if (empty($filter)) {
            return true; // Pas de filtre = tout passe
        }
        
        // Vérifier que toutes les conditions du filtre sont remplies
        foreach ($filter as $key => $expectedValue) {
            $actualValue = $this->getMetadataValue($metadata, $key);
            if ($actualValue === null || $actualValue !== $expectedValue) {
                $this->logger->info("Filtre non respecté pour '$key': attendu='$expectedValue', reçu='" . ($actualValue ?? 'null') . "'");
                return false;
            }
        }
        
        return true;
    }

    /**
     * Résout la valeur d'un champ en supportant _root. pour accéder aux métadonnées racine
     */
    private function resolveFieldValue(string $fieldName, array $item, array $metadata): string {
        if (strpos($fieldName, '_root.') === 0) {
            // Accès aux métadonnées racine
            $rootField = substr($fieldName, 6); // Enlever "_root."
            
            // Support pour les accès imbriqués comme "clients[0].client"
            if (preg_match('/^([^\[]+)\[(\d+)\]\.(.+)$/', $rootField, $matches)) {
                $arrayField = $matches[1];    // "clients"
                $index = (int)$matches[2];     // 0
                $nestedField = $matches[3];    // "client"
                
                if (isset($metadata[$arrayField]) && is_array($metadata[$arrayField])) {
                    $array = $metadata[$arrayField];
                    if (isset($array[$index]) && is_array($array[$index]) && isset($array[$index][$nestedField])) {
                        $value = $array[$index][$nestedField];
                        // Extraire le label si c'est un lien Obsidian
                        return is_string($value) ? $this->extractObsidianLabel($value) : (string)$value;
                    }
                }
                return '';
            }
            
            return isset($metadata[$rootField]) ? (string)$metadata[$rootField] : '';
        } else {
            // Accès aux données de l'élément du tableau
            return isset($item[$fieldName]) ? (string)$item[$fieldName] : '';
        }
    }

    /**
     * Parse et résout les expressions dynamiques dans les placeholders (ex: name.split("-")[-1])
     */
    private function resolveDynamicPlaceholder(string $expression, string $filename): string {
        // Enlever l'extension .md du nom de fichier
        $nameWithoutExt = preg_replace('/\.md$/', '', $filename);
        
        // Pattern pour name.split("X")[index] ou name.split("X")[-index]
        if (preg_match('/^name\.split\(["\']([^"\']*)["\']\\)\[(-?\d+)\]$/', $expression, $matches)) {
            $delimiter = $matches[1];
            $index = (int)$matches[2];
            
            $parts = explode($delimiter, $nameWithoutExt);
            
            // Support des index négatifs (à partir de la fin)
            if ($index < 0) {
                $index = count($parts) + $index;
            }
            
            return isset($parts[$index]) ? $parts[$index] : '';
        }
        
        // Si c'est juste "name", retourner le nom sans extension
        if ($expression === 'name') {
            return $nameWithoutExt;
        }
        
        return '';
    }

    /**
     * Extrait le label d'un lien Obsidian [[path/file.md|Label]] ou [[file]]
     */
    private function extractObsidianLabel(string $text): string {
        // Pattern pour [[path/file.md|Label]]
        if (preg_match('/\[\[([^\]|]+)\|([^\]]+)\]\]/', $text, $matches)) {
            return trim($matches[2]); // Retourne le label
        }
        
        // Pattern pour [[file]] sans label
        if (preg_match('/\[\[([^\]]+)\]\]/', $text, $matches)) {
            $path = trim($matches[1]);
            // Extraire le nom du fichier sans extension
            $filename = basename($path, '.md');
            return $filename;
        }
        
        // Pas de lien Obsidian, retourner le texte tel quel
        return $text;
    }

    /**
     * Formate une valeur pour l'affichage (string ou array)
     */
    private function formatValueForDisplay($value): string {
        if (is_string($value)) {
            return $this->extractObsidianLabel($value);
        } elseif (is_array($value)) {
            // Extraire les labels de chaque élément et joindre avec des virgules
            $labels = array_map(function($item) {
                return $this->extractObsidianLabel(is_string($item) ? $item : json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }, $value);
            return implode(', ', $labels);
        }
        return '';
    }

private function addAction(string $name, array $metadata, string $text): void {
    $logger = $this->logger;
    
    try {
        // Résoudre l'utilisateur cible basé sur les métadonnées
        $userId = $this->resolveTargetUser($metadata, 'sync_calendar_user');
    } catch (\Exception $e) {
        $logger->error("Erreur résolution utilisateur pour calendrier: " . $e->getMessage());
        return;
    }

    try {
        /** @var CalDavBackend $calDavBackend */
        $calDavBackend = \OC::$server->query(CalDavBackend::class);

        // Récupérer tous les calendriers de l’utilisateur
        $calendars = $calDavBackend->getCalendarsForUser("principals/users/$userId");
        if (empty($calendars)) {
            $logger->error("Aucun calendrier trouvé pour l’utilisateur $userId");
            return;
        }

        // Récupérer le nom du calendrier cible depuis la config
        $targetCalendarName = $this->config->getAppValue('crm', 'sync_calendar_name', 'personal');
        
        // Chercher le calendrier par son URI
        $calendar = null;
        foreach ($calendars as $cal) {
            if ($cal['uri'] === $targetCalendarName) {
                $calendar = $cal;
                break;
            }
        }
        
        // Si le calendrier cible n'est pas trouvé, utiliser le premier disponible
        if ($calendar === null) {
            $logger->warning("Calendrier '$targetCalendarName' non trouvé pour $userId, utilisation du premier calendrier disponible");
            $calendar = $calendars[0];
        }

        // Récupérer le mapping des métadonnées pour le calendrier
        $fieldMapping = json_decode($this->config->getAppValue('crm', 'sync_calendar_mapping', '{}'), true);
        $arrayProperties = json_decode($this->config->getAppValue('crm', 'sync_calendar_array_properties', '{}'), true);
        
        // Vérifier si on a des propriétés tableau à traiter
        $hasArrayProperties = !empty($arrayProperties);
        if ($hasArrayProperties) {
            $this->processArrayProperties($name, $metadata, $text, $arrayProperties, $fieldMapping, $calendar, $calDavBackend, $userId);
            return; // On a traité les tableaux, pas besoin de continuer
        }
        
        // Nettoyer / préparer les données
        $titleField = $fieldMapping['title'] ?? null;
        $dateField = $fieldMapping['date'] ?? 'Date';
        $descriptionField = $fieldMapping['description'] ?? 'Description';
        $locationField = $fieldMapping['location'] ?? 'Lieu';
        
        // Par défaut, utiliser le nom du fichier, sinon le champ titre si configuré
        if ($titleField && !empty($metadata[$titleField])) {
            $actionName = $metadata[$titleField];
        } else {
            $actionName = preg_replace('/\.md$/', '', $name ?? 'Sans nom');
        }
        $rawDate = trim($metadata[$dateField] ?? date('Y-m-d H:i:s'), "'");
        $start = new \DateTime($rawDate);
        $end = (clone $start)->modify('+1 day');
        
        $description = !empty($metadata[$descriptionField]) ? $metadata[$descriptionField] : '';
        $location = !empty($metadata[$locationField]) ? $metadata[$locationField] : '';

        // ID de l'événement - essayer minuscule puis majuscule
        $eventId = $metadata['id'] ?? $metadata['Id'] ?? uniqid();

        $vcal = "BEGIN:VCALENDAR\r\n";
        $vcal .= "VERSION:2.0\r\n";
        $vcal .= "PRODID:-//Nextcloud CRM Plugin//EN\r\n";
        $vcal .= "CALSCALE:GREGORIAN\r\n";
        $vcal .= "BEGIN:VEVENT\r\n";
        $vcal .= "UID:" . $eventId . "@nextcloud\r\n";

        // Date/heure actuelle pour DTSTAMP
        $vcal .= "DTSTAMP:" . (new \DateTime('now', new \DateTimeZone('UTC')))->format('Ymd\THis\Z') . "\r\n";

        $vcal .= "SUMMARY:" . $actionName . "\r\n";
        if ($description) {
            $vcal .= "DESCRIPTION:" . str_replace('\n', '\\n', $description) . "\r\n";
        }
        if ($location) {
            $vcal .= "LOCATION:" . $location . "\r\n";
        }
        $vcal .= "DTSTART;VALUE=DATE:" . $start->format('Ymd') . "\r\n";
        $vcal .= "DTEND;VALUE=DATE:" . $end->format('Ymd') . "\r\n";
        $vcal .= "END:VEVENT\r\n";
        $vcal .= "END:VCALENDAR\r\n";

        $logger->info("Détails de l'action :  $vcal");
        // Nom ICS unique - utiliser l'ID si disponible, sinon générer un uniqid
        $filename = $eventId . '.ics';

        // Vérifier si l'événement existe déjà
        $existingEvent = null;
        $calendarObjects = $calDavBackend->getCalendarObjects($calendar['id']);
        foreach ($calendarObjects as $obj) {
            if ($obj['uri'] === $filename) {
                $existingEvent = $obj;
                break;
            }
        }

        if ($existingEvent) {
            // Mettre à jour l'événement existant
            $calDavBackend->updateCalendarObject($calendar['id'], $filename, $vcal);
            $logger->info("Action mise à jour dans le calendrier {$calendar['uri']} pour $userId : $filename");
        } else {
            // Création via le backend → met à jour DB + fichier
            $calDavBackend->createCalendarObject($calendar['id'], $filename, $vcal);
            $logger->info("Nouvelle action ajoutée dans le calendrier {$calendar['uri']} pour $userId : $filename");
        }
        

    } catch (\Exception $e) {
        $logger->error("Erreur ajout action dans calendrier interne : " . $e->getMessage());
    }
}



private function addContact(string $name, array $metadata): void {
    $backend = \OC::$server->get(CardDavBackend::class);
    
    try {
        // Résoudre l'utilisateur cible basé sur les métadonnées
        $userId = $this->resolveTargetUser($metadata, 'sync_contacts_user');
    } catch (\Exception $e) {
        $this->logger->error("Erreur résolution utilisateur pour contacts: " . $e->getMessage());
        return;
    }

    // 1. Récupérer les carnets de l’utilisateur
    $addressBooks = $backend->getAddressBooksForUser("principals/users/$userId");

    if (empty($addressBooks)) {
        $this->logger->error("Aucun carnet trouvé pour $userId");
        return;
    }

    // 2. Récupérer le carnet d'adresses cible depuis la config
    $targetAddressbook = $this->config->getAppValue('crm', 'sync_contacts_addressbook', 'contacts');
    
    $addressBookId = null;
    foreach ($addressBooks as $ab) {
        if ($ab['uri'] === $targetAddressbook) {
            $addressBookId = $ab['id'];
            break;
        }
    }

    // Si le carnet cible n'est pas trouvé, chercher 'contacts' ou 'default'
    if ($addressBookId === null) {
        foreach ($addressBooks as $ab) {
            if ($ab['uri'] === 'contacts' || $ab['uri'] === 'default') {
                $addressBookId = $ab['id'];
                break;
            }
        }
    }

    // Si toujours rien trouvé, prendre le premier
    if ($addressBookId === null) {
        $this->logger->warning("Carnet '$targetAddressbook' non trouvé pour $userId, utilisation du premier carnet disponible");
        $addressBookId = $addressBooks[0]['id'];
    }

    // ID du contact - essayer minuscule puis majuscule
    $id = $metadata['id'] ?? $metadata['Id'] ?? null;
    $contactId = $id ? ($id . '.vcf') : (md5($name) . '.vcf');

    $vcard = new VCard();
    
    // Récupérer le mapping des métadonnées depuis la config
    $fieldMapping = json_decode($this->config->getAppValue('crm', 'sync_contacts_mapping', '{}'), true);
    
    // Nom du contact (utiliser le mapping ou le nom de fichier par défaut)
    $nameField = $fieldMapping['name'] ?? 'FN';
    $contactName = !empty($metadata[$nameField]) ? $metadata[$nameField] : preg_replace('/\.md$/', '', $name ?? 'Sans nom');
    $vcard->add('FN', $contactName);
    
    // Email
    $emailField = $fieldMapping['email'] ?? 'Email';
    if (!empty($metadata[$emailField])) {
        $vcard->add('EMAIL', $metadata[$emailField]);
    }
    
    // Téléphone
    $phoneField = $fieldMapping['phone'] ?? 'Téléphone';
    if (!empty($metadata[$phoneField])) {
        $vcard->add('TEL', $metadata[$phoneField]);
    }
    
    // Mobile
    $mobileField = $fieldMapping['mobile'] ?? 'Portable';
    if (!empty($metadata[$mobileField])) {
        $vcard->add('TEL', $metadata[$mobileField])->add('TYPE', 'cell');
    }
    
    // Champs additionnels configurables
    if (!empty($fieldMapping['additional'])) {
        foreach ($fieldMapping['additional'] as $vcardField => $metadataField) {
            if (!empty($metadata[$metadataField])) {
                $vcard->add($vcardField, $metadata[$metadataField]);
            }
        }
    }

    // Vérifier si le contact existe déjà
    $existingCard = null;
    foreach ($backend->getCards($addressBookId) as $card) {
        if ($card['uri'] === $contactId) {
            $existingCard = $card;
            break;
        }
    }

    if ($existingCard) {
        $backend->updateCard($addressBookId, $contactId, $vcard->serialize());
        $this->logger->info("Contact mis à jour dans le carnet de $userId");
    } else {
        $backend->createCard($addressBookId, $contactId, $vcard->serialize());
        $this->logger->info("Contact ajouté directement au carnet de $userId");
    }
}

    private function addContactForConfig(string $name, array $metadata, array $globalMapping, array $config): void {
        $backend = \OC::$server->get(CardDavBackend::class);
        
        $userId = $config['user_id'];
        $addressbookName = $config['addressbook'] ?? 'contacts';

        // 1. Récupérer les carnets de l'utilisateur
        $addressBooks = $backend->getAddressBooksForUser("principals/users/$userId");

        if (empty($addressBooks)) {
            $this->logger->error("Aucun carnet trouvé pour $userId");
            return;
        }

        // 2. Chercher le carnet d'adresses cible
        $addressBookId = null;
        foreach ($addressBooks as $ab) {
            if ($ab['uri'] === $addressbookName) {
                $addressBookId = $ab['id'];
                break;
            }
        }

        // Si le carnet cible n'est pas trouvé, chercher 'contacts' ou 'default'
        if ($addressBookId === null) {
            foreach ($addressBooks as $ab) {
                if ($ab['uri'] === 'contacts' || $ab['uri'] === 'default') {
                    $addressBookId = $ab['id'];
                    break;
                }
            }
        }

        // Si toujours rien trouvé, prendre le premier
        if ($addressBookId === null) {
            $this->logger->warning("Carnet '$addressbookName' non trouvé pour $userId, utilisation du premier carnet disponible");
            $addressBookId = $addressBooks[0]['id'];
        }

        // ID du contact
        $id = $metadata['id'] ?? $metadata['Id'] ?? null;
        $contactId = $id ? ($id . '.vcf') : (md5($name) . '.vcf');

        $vcard = new VCard();
        
        // Nom du contact
        $nameField = $globalMapping['name'] ?? 'FN';
        if ($nameField === 'name') {
            // Si le mapping indique "name", utiliser le nom du fichier
            $contactName = preg_replace('/\.md$/', '', $name);
        } else {
            // Sinon utiliser le champ de métadonnées configuré
            $contactName = !empty($metadata[$nameField]) ? $metadata[$nameField] : preg_replace('/\.md$/', '', $name ?? 'Sans nom');
        }
        $vcard->add('FN', $contactName);
        
        // Email
        $emailField = $globalMapping['email'] ?? 'Email';
        if (!empty($emailField)) {
            $emailValue = $this->resolveMetadataPath($metadata, $emailField);
            if ($emailValue) {
                $emailValue = $this->formatValueForDisplay($emailValue);
                $vcard->add('EMAIL', $emailValue);
                $this->logger->info("Email ajouté: $emailValue (depuis $emailField)");
            }
        }
        
        // Téléphone
        $phoneField = $globalMapping['phone'] ?? 'Téléphone';
        if (!empty($phoneField)) {
            $phoneValue = $this->resolveMetadataPath($metadata, $phoneField);
            if ($phoneValue) {
                $phoneValue = $this->formatValueForDisplay($phoneValue);
                $vcard->add('TEL', $phoneValue);
                $this->logger->info("Téléphone ajouté: $phoneValue (depuis $phoneField)");
            }
        }
        
        // Mobile
        $mobileField = $globalMapping['mobile'] ?? 'Portable';
        if (!empty($mobileField)) {
            $mobileValue = $this->resolveMetadataPath($metadata, $mobileField);
            if ($mobileValue) {
                $mobileValue = $this->formatValueForDisplay($mobileValue);
                $vcard->add('TEL', $mobileValue)->add('TYPE', 'cell');
                $this->logger->info("Mobile ajouté: $mobileValue (depuis $mobileField)");
            }
        }
        
        // Champs additionnels configurables
        if (!empty($globalMapping['additional'])) {
            foreach ($globalMapping['additional'] as $vcardField => $metadataField) {
                $additionalValue = $this->resolveMetadataPath($metadata, $metadataField);
                if ($additionalValue) {
                    $additionalValue = $this->formatValueForDisplay($additionalValue);
                    $vcard->add($vcardField, $additionalValue);
                    $this->logger->info("Champ $vcardField ajouté: $additionalValue (depuis $metadataField)");
                }
            }
        }

        // Vérifier si le contact existe déjà
        $existingCard = null;
        foreach ($backend->getCards($addressBookId) as $card) {
            if ($card['uri'] === $contactId) {
                $existingCard = $card;
                break;
            }
        }

        if ($existingCard) {
            $backend->updateCard($addressBookId, $contactId, $vcard->serialize());
            $this->logger->info("Contact mis à jour dans le carnet de $userId (config {$config['id']})");
        } else {
            $backend->createCard($addressBookId, $contactId, $vcard->serialize());
            $this->logger->info("Contact ajouté directement au carnet de $userId (config {$config['id']})");
        }
    }

    /**
     * Traite une configuration d'animation
     */
    private function processAnimationConfig(string $name, array $metadata, string $text, array $config): void {
        $logger = $this->logger;
        
        $userId = $config['user_id'];
        $calendarName = $config['calendar'] ?? 'personal';
        $arrayProperty = $config['array_property'];
        
        // Vérifier si la propriété tableau existe
        if (!isset($metadata[$arrayProperty]) || !is_array($metadata[$arrayProperty])) {
            $logger->info("Propriété '$arrayProperty' non trouvée ou n'est pas un tableau dans $name");
            return;
        }

        try {
            /** @var CalDavBackend $calDavBackend */
            $calDavBackend = \OC::$server->query(CalDavBackend::class);

            // Récupérer tous les calendriers de l'utilisateur
            $calendars = $calDavBackend->getCalendarsForUser("principals/users/$userId");
            if (empty($calendars)) {
                $logger->error("Aucun calendrier trouvé pour l'utilisateur $userId");
                return;
            }

            // Chercher le calendrier par son URI
            $calendar = null;
            foreach ($calendars as $cal) {
                if ($cal['uri'] === $calendarName) {
                    $calendar = $cal;
                    break;
                }
            }
            
            // Si le calendrier cible n'est pas trouvé, utiliser le premier disponible
            if ($calendar === null) {
                $logger->warning("Calendrier '$calendarName' non trouvé pour $userId, utilisation du premier calendrier disponible");
                $calendar = $calendars[0];
            }

            $dateField = $config['date_field'] ?? 'date';
            $titleFormat = $config['title_format'] ?? '{' . $dateField . '}';
            $idFormat = $config['id_format'] ?? 'event_{index}';
            $descriptionFields = $config['description_fields'] ?? [];
            $locationField = $config['location_field'] ?? 'lieu';
            $assignedField = $config['assigned_field'] ?? 'assignés';
            
            $logger->info("Traitement du tableau '$arrayProperty' avec " . count($metadata[$arrayProperty]) . " éléments");
            
            // Parcourir chaque élément du tableau
            foreach ($metadata[$arrayProperty] as $index => $item) {
                if (!is_array($item) || !isset($item[$dateField])) {
                    $logger->warning("Élément $index du tableau '$arrayProperty' n'a pas de champ '$dateField'");
                    continue;
                }
                
                try {
                    // Générer le titre en remplaçant les placeholders (support _root.)
                    $title = $titleFormat;
                    
                    // Remplacer les expressions dynamiques (ex: {name.split("-")[-1]})
                    if (preg_match_all('/\{([^}]+)\}/', $title, $allMatches)) {
                        foreach ($allMatches[1] as $expr) {
                            if (strpos($expr, 'name') === 0) {
                                $resolved = $this->resolveDynamicPlaceholder($expr, $name);
                                $title = str_replace('{' . $expr . '}', $resolved, $title);
                            }
                        }
                    }
                    
                    // Remplacer les placeholders depuis l'élément
                    foreach ($item as $key => $value) {
                        // Ignorer les valeurs qui ne sont pas des strings (tableaux, etc.)
                        if (is_string($value)) {
                            $title = str_replace('{' . $key . '}', $value, $title);
                        }
                    }
                    // Remplacer les placeholders _root depuis les métadonnées racine
                    if (preg_match_all('/\{_root\.([^}]+)\}/', $title, $matches)) {
                        foreach ($matches[1] as $rootField) {
                            $value = $this->resolveFieldValue('_root.' . $rootField, $item, $metadata);
                            $title = str_replace('{_root.' . $rootField . '}', $value, $title);
                        }
                    }
                    
                    // Générer l'ID unique
                    $eventId = str_replace('{index}', (string)$index, $idFormat);
                    $eventId = str_replace('{filename}', preg_replace('/\.md$/', '', $name), $eventId);
                    
                    // Construire la description avec les champs configurés
                    $description = '';
                    foreach ($descriptionFields as $field) {
                        if ($field === '_content') {
                            // Ajouter le contenu du fichier sans les métadonnées
                            $content = $this->extractContentWithoutMetadata($text);
                            if (!empty($content)) {
                                $description .= "Contenu:\n" . $content . "\n";
                            }
                        } elseif (strpos($field, '_root.') === 0) {
                            // Champ des métadonnées racine (ex: _root.Titre)
                            $rootField = substr($field, 6); // Enlever "_root."
                            if (isset($metadata[$rootField]) && !empty($metadata[$rootField])) {
                                $formattedValue = $this->formatValueForDisplay($metadata[$rootField]);
                                if (!empty($formattedValue)) {
                                    $description .= "$rootField: " . $formattedValue . "\n";
                                }
                            }
                        } elseif (isset($item[$field]) && !empty($item[$field])) {
                            $formattedValue = $this->formatValueForDisplay($item[$field]);
                            if (!empty($formattedValue)) {
                                $description .= "$field: " . $formattedValue . "\n";
                            }
                        }
                    }
                    
                    // Date de l'événement
                    $rawDate = trim($item[$dateField], "'");
                    $start = new \DateTime($rawDate);
                    $end = (clone $start)->modify('+1 day');
                    
                    // Récupérer location (support _root.)
                    $location = '';
                    if (!empty($locationField)) {
                        $location = $this->resolveFieldValue($locationField, $item, $metadata);
                    }
                    
                    // Récupérer assigned (support _root.)
                    $assigned = '';
                    if (!empty($assignedField)) {
                        $assigned = $this->resolveFieldValue($assignedField, $item, $metadata);
                        // Ajouter les assignés à la description
                        if (!empty($assigned)) {
                            $description .= "\nResponsables: " . $assigned . "\n";
                        }
                    }
                    
                    // Créer le VCALENDAR
                    $vcal = "BEGIN:VCALENDAR\r\n";
                    $vcal .= "VERSION:2.0\r\n";
                    $vcal .= "PRODID:-//Nextcloud CRM Plugin//EN\r\n";
                    $vcal .= "CALSCALE:GREGORIAN\r\n";
                    $vcal .= "BEGIN:VEVENT\r\n";
                    $vcal .= "UID:" . $eventId . "@nextcloud\r\n";
                    $vcal .= "DTSTAMP:" . (new \DateTime('now', new \DateTimeZone('UTC')))->format('Ymd\THis\Z') . "\r\n";
                    $vcal .= "SUMMARY:" . $this->escapeICalendarText($title) . "\r\n";
                    
                    if (!empty($description)) {
                        $vcal .= "DESCRIPTION:" . $this->escapeICalendarText($description) . "\r\n";
                    }
                    
                    if (!empty($location)) {
                        $vcal .= "LOCATION:" . $this->escapeICalendarText($location) . "\r\n";
                    }
                    
                    // Ajouter les attendees
                    if (!empty($assigned)) {
                        $attendees = array_map('trim', explode(',', $assigned));
                        foreach ($attendees as $attendee) {
                            if (!empty($attendee)) {
                                $vcal .= "ATTENDEE;CN=" . $this->escapeICalendarText($attendee) . ":mailto:noreply@nextcloud.local\r\n";
                            }
                        }
                    }
                    
                    $vcal .= "DTSTART;VALUE=DATE:" . $start->format('Ymd') . "\r\n";
                    $vcal .= "DTEND;VALUE=DATE:" . $end->format('Ymd') . "\r\n";
                    $vcal .= "END:VEVENT\r\n";
                    $vcal .= "END:VCALENDAR\r\n";
                    
                    $filename = $eventId . '.ics';
                    
                    // Vérifier si l'événement existe déjà
                    $existingEvent = null;
                    $calendarObjects = $calDavBackend->getCalendarObjects($calendar['id']);
                    foreach ($calendarObjects as $obj) {
                        if ($obj['uri'] === $filename) {
                            $existingEvent = $obj;
                            break;
                        }
                    }
                    
                    if ($existingEvent) {
                        $calDavBackend->updateCalendarObject($calendar['id'], $filename, $vcal);
                        $logger->info("Animation mise à jour: $filename - $title (config {$config['id']})");
                    } else {
                        $calDavBackend->createCalendarObject($calendar['id'], $filename, $vcal);
                        $logger->info("Animation créée: $filename - $title (config {$config['id']})");
                    }
                    
                } catch (\Exception $e) {
                    $logger->error("Erreur traitement élément $index de '$arrayProperty': " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            $logger->error("Erreur traitement animation config {$config['id']}: " . $e->getMessage());
        }
    }

    private function addActionForConfig(string $name, array $metadata, string $text, array $globalMapping, array $config): void {
        $logger = $this->logger;
        
        $userId = $config['user_id'];
        $calendarName = $config['calendar'] ?? 'personal';

        try {
            /** @var CalDavBackend $calDavBackend */
            $calDavBackend = \OC::$server->query(CalDavBackend::class);

            // Récupérer tous les calendriers de l'utilisateur
            $calendars = $calDavBackend->getCalendarsForUser("principals/users/$userId");
            if (empty($calendars)) {
                $logger->error("Aucun calendrier trouvé pour l'utilisateur $userId");
                return;
            }

            // Chercher le calendrier par son URI
            $calendar = null;
            foreach ($calendars as $cal) {
                if ($cal['uri'] === $calendarName) {
                    $calendar = $cal;
                    break;
                }
            }
            
            // Si le calendrier cible n'est pas trouvé, utiliser le premier disponible
            if ($calendar === null) {
                $logger->warning("Calendrier '$calendarName' non trouvé pour $userId, utilisation du premier calendrier disponible");
                $calendar = $calendars[0];
            }

            // Récupérer la configuration des propriétés tableau
            $arrayProperties = json_decode($this->config->getAppValue('crm', 'sync_calendar_array_properties', '{}'), true) ?? [];
            
            $logger->info("Array properties config: " . json_encode($arrayProperties));
            
            // Vérifier si on a des propriétés tableau à traiter
            if (!empty($arrayProperties)) {
                $logger->info("Traitement des propriétés tableau pour $name");
                $this->processArrayProperties($name, $metadata, $text, $arrayProperties, $globalMapping, $calendar, $calDavBackend, $userId);
                return; // On a traité les tableaux, pas besoin de continuer
            }

            // Nettoyer / préparer les données
            $titleField = $globalMapping['title'] ?? null;
            $dateField = $globalMapping['date'] ?? 'Date';
            $descriptionField = $globalMapping['description'] ?? 'Description';
            $locationField = $globalMapping['location'] ?? 'Lieu';
            
            // Par défaut, utiliser le nom du fichier, sinon le champ titre si configuré
            if ($titleField && !empty($metadata[$titleField])) {
                $actionName = $metadata[$titleField];
            } else {
                $actionName = preg_replace('/\.md$/', '', $name ?? 'Sans nom');
            }
            $rawDate = trim($metadata[$dateField] ?? date('Y-m-d H:i:s'), "'");
            $start = new \DateTime($rawDate);
            $end = (clone $start)->modify('+1 day');
            
            $description = !empty($metadata[$descriptionField]) ? $metadata[$descriptionField] : '';
            $location = !empty($metadata[$locationField]) ? $metadata[$locationField] : '';

            // ID de l'événement
            $eventId = $metadata['id'] ?? $metadata['Id'] ?? uniqid();

            $vcal = "BEGIN:VCALENDAR\r\n";
            $vcal .= "VERSION:2.0\r\n";
            $vcal .= "PRODID:-//Nextcloud CRM Plugin//EN\r\n";
            $vcal .= "CALSCALE:GREGORIAN\r\n";
            $vcal .= "BEGIN:VEVENT\r\n";
            $vcal .= "UID:" . $eventId . "@nextcloud\r\n";

            // Date/heure actuelle pour DTSTAMP
            $vcal .= "DTSTAMP:" . (new \DateTime('now', new \DateTimeZone('UTC')))->format('Ymd\THis\Z') . "\r\n";

            $vcal .= "SUMMARY:" . $actionName . "\r\n";
            if ($description) {
                $vcal .= "DESCRIPTION:" . str_replace('\n', '\\n', $description) . "\r\n";
            }
            if ($location) {
                $vcal .= "LOCATION:" . $location . "\r\n";
            }
            $vcal .= "DTSTART;VALUE=DATE:" . $start->format('Ymd') . "\r\n";
            $vcal .= "DTEND;VALUE=DATE:" . $end->format('Ymd') . "\r\n";
            $vcal .= "END:VEVENT\r\n";
            $vcal .= "END:VCALENDAR\r\n";

            $logger->info("Détails de l'action : $vcal");
            $filename = $eventId . '.ics';

            // Vérifier si l'événement existe déjà
            $existingEvent = null;
            $calendarObjects = $calDavBackend->getCalendarObjects($calendar['id']);
            foreach ($calendarObjects as $obj) {
                if ($obj['uri'] === $filename) {
                    $existingEvent = $obj;
                    break;
                }
            }

            if ($existingEvent) {
                $calDavBackend->updateCalendarObject($calendar['id'], $filename, $vcal);
                $logger->info("Action mise à jour dans le calendrier {$calendar['uri']} pour $userId (config {$config['id']})");
            } else {
                $calDavBackend->createCalendarObject($calendar['id'], $filename, $vcal);
                $logger->info("Nouvelle action ajoutée dans le calendrier {$calendar['uri']} pour $userId (config {$config['id']})");
            }

        } catch (\Exception $e) {
            $logger->error("Erreur ajout action dans calendrier pour config {$config['id']}: " . $e->getMessage());
        }
    }

    /**
     * Traite les propriétés tableau avec plusieurs dates
     */
    private function processArrayProperties(string $name, array $metadata, string $text, array $arrayProperties, array $fieldMapping, array $calendar, $calDavBackend, string $userId): void {
        $logger = $this->logger;
        
        foreach ($arrayProperties as $propertyName => $config) {
            // Vérifier si la propriété existe dans les métadonnées
            if (!isset($metadata[$propertyName]) || !is_array($metadata[$propertyName])) {
                continue;
            }
            
            $dateField = $config['dateField'] ?? 'date';
            $titleFormat = $config['titleFormat'] ?? '{' . $dateField . '}';
            $idFormat = $config['idFormat'] ?? 'event_{index}';
            $descriptionFields = $config['descriptionFields'] ?? [];
            $locationField = $config['location_field'] ?? 'lieu';
            $assignedField = $config['assigned_field'] ?? 'assignés';
            
            $logger->info("Traitement du tableau '$propertyName' avec " . count($metadata[$propertyName]) . " éléments");
            
            // Parcourir chaque élément du tableau
            foreach ($metadata[$propertyName] as $index => $item) {
                if (!is_array($item) || !isset($item[$dateField])) {
                    $logger->warning("Élément $index du tableau '$propertyName' n'a pas de champ '$dateField'");
                    continue;
                }
                
                try {
                    // Générer le titre en remplaçant les placeholders
                    $title = $titleFormat;
                    
                    // Remplacer les expressions dynamiques (ex: {name.split("-")[-1]})
                    if (preg_match_all('/\{([^}]+)\}/', $title, $allMatches)) {
                        foreach ($allMatches[1] as $expr) {
                            if (strpos($expr, 'name') === 0) {
                                $resolved = $this->resolveDynamicPlaceholder($expr, $name);
                                $title = str_replace('{' . $expr . '}', $resolved, $title);
                            }
                        }
                    }
                    
                    // Remplacer les placeholders _root depuis les métadonnées racine
                    if (preg_match_all('/\{_root\.([^}]+)\}/', $title, $matches)) {
                        foreach ($matches[1] as $rootField) {
                            $value = $this->resolveFieldValue('_root.' . $rootField, $item, $metadata);
                            $title = str_replace('{_root.' . $rootField . '}', $value, $title);
                        }
                    }
                    
                    // Remplacer les placeholders normaux
                    foreach ($item as $key => $value) {
                        // Ignorer les valeurs qui ne sont pas des strings (tableaux, etc.)
                        if (is_string($value)) {
                            $title = str_replace('{' . $key . '}', $value, $title);
                        }
                    }
                    
                    // Générer l'ID unique
                    $eventId = str_replace('{index}', (string)$index, $idFormat);
                    $eventId = str_replace('{filename}', preg_replace('/\.md$/', '', $name), $eventId);
                    
                    // Construire la description avec les champs configurés
                    $description = '';
                    foreach ($descriptionFields as $field) {
                        if ($field === '_content') {
                            // Ajouter le contenu du fichier sans les métadonnées
                            $content = $this->extractContentWithoutMetadata($text);
                            if (!empty($content)) {
                                $description .= "Contenu:\n" . $content . "\n";
                            }
                        } elseif (strpos($field, '_root.') === 0) {
                            // Champ des métadonnées racine (ex: _root.Titre)
                            $rootField = substr($field, 6); // Enlever "_root."
                            if (isset($metadata[$rootField]) && !empty($metadata[$rootField])) {
                                $formattedValue = $this->formatValueForDisplay($metadata[$rootField]);
                                if (!empty($formattedValue)) {
                                    $description .= "$rootField: " . $formattedValue . "\n";
                                }
                            }
                        } elseif (isset($item[$field]) && !empty($item[$field])) {
                            $formattedValue = $this->formatValueForDisplay($item[$field]);
                            if (!empty($formattedValue)) {
                                $description .= "$field: " . $formattedValue . "\n";
                            }
                        }
                    }
                    
                    // Date de l'événement
                    $rawDate = trim($item[$dateField], "'");
                    $start = new \DateTime($rawDate);
                    $end = (clone $start)->modify('+1 day');
                    
                    // Récupérer location (support _root.)
                    $location = '';
                    if (!empty($locationField)) {
                        $location = $this->resolveFieldValue($locationField, $item, $metadata);
                    }
                    
                    // Récupérer assigned (support _root.)
                    $assigned = '';
                    if (!empty($assignedField)) {
                        $assigned = $this->resolveFieldValue($assignedField, $item, $metadata);
                        // Ajouter les assignés à la description
                        if (!empty($assigned)) {
                            $description .= "\nResponsables: " . $assigned . "\n";
                        }
                    }
                    
                    // Créer le VCALENDAR
                    $vcal = "BEGIN:VCALENDAR\r\n";
                    $vcal .= "VERSION:2.0\r\n";
                    $vcal .= "PRODID:-//Nextcloud CRM Plugin//EN\r\n";
                    $vcal .= "CALSCALE:GREGORIAN\r\n";
                    $vcal .= "BEGIN:VEVENT\r\n";
                    $vcal .= "UID:" . $eventId . "@nextcloud\r\n";
                    $vcal .= "DTSTAMP:" . (new \DateTime('now', new \DateTimeZone('UTC')))->format('Ymd\THis\Z') . "\r\n";
                    $vcal .= "SUMMARY:" . $this->escapeICalendarText($title) . "\r\n";
                    
                    if (!empty($description)) {
                        $vcal .= "DESCRIPTION:" . $this->escapeICalendarText($description) . "\r\n";
                    }
                    
                    if (!empty($location)) {
                        $vcal .= "LOCATION:" . $this->escapeICalendarText($location) . "\r\n";
                    }
                    
                    // Ajouter les attendees
                    if (!empty($assigned)) {
                        $attendees = array_map('trim', explode(',', $assigned));
                        foreach ($attendees as $attendee) {
                            if (!empty($attendee)) {
                                $vcal .= "ATTENDEE;CN=" . $this->escapeICalendarText($attendee) . ":mailto:noreply@nextcloud.local\r\n";
                            }
                        }
                    }
                    
                    $vcal .= "DTSTART;VALUE=DATE:" . $start->format('Ymd') . "\r\n";
                    $vcal .= "DTEND;VALUE=DATE:" . $end->format('Ymd') . "\r\n";
                    $vcal .= "END:VEVENT\r\n";
                    $vcal .= "END:VCALENDAR\r\n";
                    
                    $filename = $eventId . '.ics';
                    
                    // Vérifier si l'événement existe déjà
                    $existingEvent = null;
                    $calendarObjects = $calDavBackend->getCalendarObjects($calendar['id']);
                    foreach ($calendarObjects as $obj) {
                        if ($obj['uri'] === $filename) {
                            $existingEvent = $obj;
                            break;
                        }
                    }
                    
                    if ($existingEvent) {
                        $calDavBackend->updateCalendarObject($calendar['id'], $filename, $vcal);
                        $logger->info("Événement tableau mis à jour: $filename - $title");
                    } else {
                        $calDavBackend->createCalendarObject($calendar['id'], $filename, $vcal);
                        $logger->info("Événement tableau créé: $filename - $title");
                    }
                    
                } catch (\Exception $e) {
                    $logger->error("Erreur traitement élément $index de '$propertyName': " . $e->getMessage());
                }
            }
        }
    }
}

