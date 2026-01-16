<?php

declare(strict_types=1);

namespace OCA\CRM\Tests\Unit\Listener;

use OCA\CRM\Tests\TestCase;
use OCA\CRM\Listener\MarkdownListener;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for MarkdownListener calendar synchronization
 */
class MarkdownListenerCalendarTest extends TestCase
{
    /** @var LoggerInterface|MockObject */
    private $logger;
    
    /** @var IRootFolder|MockObject */
    private $rootFolder;
    
    /** @var IUserSession|MockObject */
    private $userSession;
    
    /** @var IConfig|MockObject */
    private $config;
    
    /** @var MarkdownListener */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->rootFolder = $this->createMock(IRootFolder::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $this->config = $this->createMock(IConfig::class);
        
        $this->listener = new MarkdownListener(
            $this->logger,
            $this->rootFolder,
            $this->userSession,
            $this->config
        );
    }

    public function testHandleProcessesMarkdownFileWithCalendarMetadata(): void
    {
        $markdownContent = <<<'MD'
---
Classe: Action
Titre: RÃ©union importante
Date: 2024-03-15 10:00:00
Description: RÃ©union avec l'Ã©quipe
Lieu: Salle 101
---

# RÃ©union importante

Notes sur la rÃ©union.
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('reunion.md');
        $file->method('getPath')->willReturn('/files/admin/actions/reunion.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        // Mock config to enable calendar sync
        $this->config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '1'],
                ['crm', 'sync_calendar_global_class', 'Action', 'Action'],
                ['crm', 'sync_calendar_global_mapping', '{}', json_encode([
                    'title' => 'Titre',
                    'date' => 'Date',
                    'description' => 'Description',
                    'location' => 'Lieu'
                ])],
                ['crm', 'sync_calendar_global_filter', '{}', '{}'],
                ['crm', 'sync_calendar_global_array_properties', '[]', '[]'],
                ['crm', 'sync_calendar_configs', '[]', json_encode([
                    [
                        'id' => 'calendar_config1',
                        'enabled' => true,
                        'user_id' => 'admin',
                        'calendar_name' => 'personal',
                        'filter' => []
                    ]
                ])],
                ['crm', 'animation_configs', '[]', '[]'],
            ]);
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->listener->handle($event);
    }

    public function testCalendarSyncDisabledWhenGlobalFlagIsFalse(): void
    {
        $markdownContent = <<<'MD'
---
Classe: Action
Titre: Test
Date: 2024-03-15
---
Test event
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('test.md');
        $file->method('getPath')->willReturn('/files/admin/test.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        // Disable calendar sync
        $this->config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '0'],
                ['crm', 'animation_configs', '[]', '[]'],
            ]);
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->listener->handle($event);
    }

    public function testCalendarMetadataParsingWithArrayProperties(): void
    {
        $markdownContent = <<<'MD'
---
Classe: Action
Taches:
  - nom: TÃ¢che 1
    date: 2024-03-15 09:00:00
    description: PremiÃ¨re tÃ¢che
  - nom: TÃ¢che 2
    date: 2024-03-16 14:00:00
    description: DeuxiÃ¨me tÃ¢che
  - nom: TÃ¢che 3
    date: 2024-03-17 11:00:00
    description: TroisiÃ¨me tÃ¢che
---

# Actions du projet
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('projet-taches.md');
        $file->method('getPath')->willReturn('/files/admin/projet-taches.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $this->config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '1'],
                ['crm', 'sync_calendar_global_class', 'Action', 'Action'],
                ['crm', 'sync_calendar_global_mapping', '{}', json_encode([
                    'title' => 'Titre',
                    'date' => 'Date',
                    'description' => 'Description'
                ])],
                ['crm', 'sync_calendar_global_filter', '{}', '{}'],
                ['crm', 'sync_calendar_global_array_properties', '[]', '[]'],
                ['crm', 'sync_calendar_array_properties', '[]', json_encode([
                    'Taches'
                ])],
                ['crm', 'sync_calendar_configs', '[]', json_encode([
                    [
                        'id' => 'calendar_config1',
                        'enabled' => true,
                        'user_id' => 'admin',
                        'calendar_name' => 'personal',
                        'filter' => []
                    ]
                ])],
                ['crm', 'animation_configs', '[]', '[]'],
            ]);
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->listener->handle($event);
    }

    public function testCalendarSyncWithCustomMapping(): void
    {
        $markdownContent = <<<'MD'
---
Classe: Action
ActionName: Formation PHP
ActionDate: 2024-04-01 09:00:00
ActionDetails: Formation sur PHP 8.2
ActionPlace: Bureau
---

# Formation PHP
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('formation.md');
        $file->method('getPath')->willReturn('/files/admin/formation.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $this->config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '1'],
                ['crm', 'sync_calendar_global_class', 'Action', 'Action'],
                ['crm', 'sync_calendar_global_mapping', '{}', json_encode([
                    'title' => 'ActionName',
                    'date' => 'ActionDate',
                    'description' => 'ActionDetails',
                    'location' => 'ActionPlace'
                ])],
                ['crm', 'sync_calendar_global_filter', '{}', '{}'],
                ['crm', 'sync_calendar_global_array_properties', '[]', '[]'],
                ['crm', 'sync_calendar_configs', '[]', json_encode([
                    [
                        'id' => 'calendar_config1',
                        'enabled' => true,
                        'user_id' => 'admin',
                        'calendar_name' => 'personal',
                        'filter' => []
                    ]
                ])],
                ['crm', 'animation_configs', '[]', '[]'],
            ]);
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->listener->handle($event);
    }

    public function testCalendarSyncWithMetadataFilter(): void
    {
        $markdownContent = <<<'MD'
---
Classe: Action
Titre: Ã‰vÃ©nement filtrÃ©
Date: 2024-03-20 15:00:00
Statut: PlanifiÃ©
Priorite: Haute
---

# Ã‰vÃ©nement avec filtre
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('evenement-filtre.md');
        $file->method('getPath')->willReturn('/files/admin/evenement-filtre.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $this->config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '1'],
                ['crm', 'sync_calendar_global_class', 'Action', 'Action'],
                ['crm', 'sync_calendar_global_mapping', '{}', json_encode([
                    'title' => 'Titre',
                    'date' => 'Date'
                ])],
                ['crm', 'sync_calendar_global_filter', '{}', json_encode([
                    ['field' => 'Statut', 'operator' => 'equals', 'value' => 'PlanifiÃ©']
                ])],
                ['crm', 'sync_calendar_global_array_properties', '[]', '[]'],
                ['crm', 'sync_calendar_configs', '[]', json_encode([
                    [
                        'id' => 'calendar_config1',
                        'enabled' => true,
                        'user_id' => 'admin',
                        'calendar_name' => 'personal',
                        'filter' => [
                            ['field' => 'Priorite', 'operator' => 'equals', 'value' => 'Haute']
                        ]
                    ]
                ])],
                ['crm', 'animation_configs', '[]', '[]'],
            ]);
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->listener->handle($event);
    }

    public function testCalendarSyncWithWrongClass(): void
    {
        $markdownContent = <<<'MD'
---
Classe: Personne
Nom: Test
---
Wrong class
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('wrong-class.md');
        $file->method('getPath')->willReturn('/files/admin/wrong-class.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $this->config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '1'],
                ['crm', 'sync_calendar_global_class', 'Action', 'Action'],
                ['crm', 'sync_calendar_global_mapping', '{}', '{}'],
                ['crm', 'sync_calendar_global_filter', '{}', '{}'],
                ['crm', 'sync_calendar_global_array_properties', '[]', '[]'],
                ['crm', 'sync_calendar_configs', '[]', '[]'],
                ['crm', 'animation_configs', '[]', '[]'],
            ]);
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->listener->handle($event);
    }

    /**
     * Helper to create a stream from a string
     */
    private function createStringStream(string $content)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        return $stream;
    }
}
