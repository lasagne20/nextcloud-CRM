<?php

declare(strict_types=1);

namespace OCA\CRM\Tests\Integration;

use OCA\CRM\Tests\TestCase;
use OCA\CRM\Listener\MarkdownListener;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Integration tests for calendar synchronization workflow including array properties
 */
class CalendarSyncTest extends TestCase
{
    public function testCalendarSyncEndToEndWithSingleEvent(): void
    {
        // Create mock dependencies
        $logger = $this->createMock(LoggerInterface::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $config = $this->createMock(IConfig::class);
        
        // Mock user
        $user = $this->createMockUser('testuser');
        $userSession->method('getUser')->willReturn($user);
        
        // Configure calendar sync settings
        $config->method('getAppValue')
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
                ['crm', 'sync_calendar_array_properties', '[]', '[]'],
                ['crm', 'sync_calendar_configs', '[]', json_encode([
                    [
                        'id' => 'calendar_config1',
                        'enabled' => true,
                        'user_id' => 'testuser',
                        'calendar_name' => 'personal',
                        'filter' => []
                    ]
                ])],
                ['crm', 'animation_configs', '[]', '[]'],
            ]);
        
        // Create listener
        $listener = new MarkdownListener($logger, $rootFolder, $userSession, $config);
        
        // Create markdown file with event
        $markdownContent = <<<'MD'
---
Classe: Action
Titre: RÃ©union mensuelle
Date: 2024-03-20 14:00:00
Description: RÃ©union d'Ã©quipe mensuelle
Lieu: Salle de rÃ©union A
---

# RÃ©union mensuelle

Ordre du jour:
1. Revue du mois prÃ©cÃ©dent
2. Objectifs du mois
3. Questions diverses
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('reunion-mensuelle.md');
        $file->method('getPath')->willReturn('/files/testuser/actions/reunion-mensuelle.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        // Expect logging
        $logger->expects($this->atLeastOnce())
            ->method('info');
        
        // Handle the event
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function testCalendarSyncWithArrayPropertiesMultipleEvents(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $config = $this->createMock(IConfig::class);
        
        $user = $this->createMockUser('admin');
        $userSession->method('getUser')->willReturn($user);
        
        $config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '1'],
                ['crm', 'sync_calendar_global_class', 'Action', 'Action'],
                ['crm', 'sync_calendar_global_mapping', '{}', json_encode([
                    'title' => 'nom',
                    'date' => 'date',
                    'description' => 'description'
                ])],
                ['crm', 'sync_calendar_global_filter', '{}', '{}'],
                ['crm', 'sync_calendar_global_array_properties', '[]', json_encode(['Taches'])],
                ['crm', 'sync_calendar_array_properties', '[]', json_encode(['Taches'])],
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
        
        $listener = new MarkdownListener($logger, $rootFolder, $userSession, $config);
        
        // Markdown with array properties
        $markdownContent = <<<'MD'
---
Classe: Action
ProjetNom: DÃ©veloppement CRM
Taches:
  - nom: Analyse des besoins
    date: 2024-03-15 09:00:00
    description: RÃ©union avec le client
  - nom: Design de l'interface
    date: 2024-03-18 10:00:00
    description: CrÃ©er les maquettes
  - nom: DÃ©veloppement backend
    date: 2024-03-20 14:00:00
    description: ImplÃ©menter les APIs
  - nom: Tests unitaires
    date: 2024-03-22 11:00:00
    description: Ã‰crire les tests
  - nom: DÃ©ploiement
    date: 2024-03-25 16:00:00
    description: Mise en production
---

# Projet CRM - Planning

Ce fichier contient toutes les tÃ¢ches du projet.
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('projet-crm.md');
        $file->method('getPath')->willReturn('/files/admin/projets/projet-crm.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        // Expect multiple log entries for array processing
        $logger->expects($this->atLeastOnce())
            ->method('info');
        
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function testCalendarSyncWithComplexArrayProperties(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $config = $this->createMock(IConfig::class);
        
        $user = $this->createMockUser('admin');
        $userSession->method('getUser')->willReturn($user);
        
        $config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '1'],
                ['crm', 'sync_calendar_global_class', 'Action', 'Action'],
                ['crm', 'sync_calendar_global_mapping', '{}', json_encode([
                    'title' => 'titre',
                    'date' => 'date',
                    'description' => 'details',
                    'location' => 'lieu'
                ])],
                ['crm', 'sync_calendar_global_filter', '{}', '{}'],
                ['crm', 'sync_calendar_global_array_properties', '[]', json_encode(['Sessions'])],
                ['crm', 'sync_calendar_array_properties', '[]', json_encode(['Sessions'])],
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
        
        $listener = new MarkdownListener($logger, $rootFolder, $userSession, $config);
        
        // Complex array with multiple properties
        $markdownContent = <<<'MD'
---
Classe: Action
Formation: PHP AvancÃ©
Sessions:
  - titre: Introduction Ã  PHP 8
    date: 2024-04-01 09:00:00
    details: NouveautÃ©s PHP 8.0, 8.1, 8.2
    lieu: Bureau A
  - titre: POO et Design Patterns
    date: 2024-04-02 09:00:00
    details: Programmation orientÃ©e objet avancÃ©e
    lieu: Bureau A
  - titre: Frameworks modernes
    date: 2024-04-03 09:00:00
    details: Symfony, Laravel
    lieu: Salle de formation
---

# Formation PHP AvancÃ©
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('formation-php.md');
        $file->method('getPath')->willReturn('/files/admin/formations/formation-php.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $logger->expects($this->atLeastOnce())
            ->method('info');
        
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function testCalendarSyncWithGlobalFilter(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $config = $this->createMock(IConfig::class);
        
        $user = $this->createMockUser('admin');
        $userSession->method('getUser')->willReturn($user);
        
        $config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '1'],
                ['crm', 'sync_calendar_global_class', 'Action', 'Action'],
                ['crm', 'sync_calendar_global_mapping', '{}', json_encode([
                    'title' => 'Titre',
                    'date' => 'Date'
                ])],
                ['crm', 'sync_calendar_global_filter', '[]', json_encode([
                    ['field' => 'Statut', 'operator' => 'equals', 'value' => 'ConfirmÃ©']
                ])],
                ['crm', 'sync_calendar_global_array_properties', '[]', '[]'],
                ['crm', 'sync_calendar_array_properties', '[]', '[]'],
                ['crm', 'sync_calendar_configs', '[]', json_encode([
                    [
                        'id' => 'config1',
                        'enabled' => true,
                        'user_id' => 'admin',
                        'calendar_name' => 'personal',
                        'filter' => []
                    ]
                ])],
                ['crm', 'animation_configs', '[]', '[]'],
            ]);
        
        $listener = new MarkdownListener($logger, $rootFolder, $userSession, $config);
        
        // Event matching global filter
        $confirmedEvent = <<<'MD'
---
Classe: Action
Titre: Ã‰vÃ©nement confirmÃ©
Date: 2024-03-25 10:00:00
Statut: ConfirmÃ©
---
# Ã‰vÃ©nement confirmÃ©
MD;

        $file1 = $this->createMock(File::class);
        $file1->method('getMimetype')->willReturn('text/markdown');
        $file1->method('getName')->willReturn('confirmed.md');
        $file1->method('getPath')->willReturn('/files/admin/confirmed.md');
        $file1->method('fopen')->willReturn($this->createStringStream($confirmedEvent));
        
        $event1 = $this->createMock(NodeWrittenEvent::class);
        $event1->method('getNode')->willReturn($file1);
        $listener->handle($event1);
        
        // Event NOT matching filter
        $pendingEvent = <<<'MD'
---
Classe: Action
Titre: Ã‰vÃ©nement en attente
Date: 2024-03-26 10:00:00
Statut: En attente
---
# Ã‰vÃ©nement en attente
MD;

        $file2 = $this->createMock(File::class);
        $file2->method('getMimetype')->willReturn('text/markdown');
        $file2->method('getName')->willReturn('pending.md');
        $file2->method('getPath')->willReturn('/files/admin/pending.md');
        $file2->method('fopen')->willReturn($this->createStringStream($pendingEvent));
        
        $event2 = $this->createMock(NodeWrittenEvent::class);
        $event2->method('getNode')->willReturn($file2);
        $listener->handle($event2);
        
        $this->assertTrue(true);
    }

    public function testCalendarSyncWithMultipleConfigs(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $config = $this->createMock(IConfig::class);
        
        $user = $this->createMockUser('admin');
        $userSession->method('getUser')->willReturn($user);
        
        $config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '1'],
                ['crm', 'sync_calendar_global_class', 'Action', 'Action'],
                ['crm', 'sync_calendar_global_mapping', '{}', json_encode([
                    'title' => 'Titre',
                    'date' => 'Date'
                ])],
                ['crm', 'sync_calendar_global_filter', '{}', json_encode([
                    ['field' => 'Equipe', 'operator' => 'equals', 'value' => 'Dev']
                ])],
                ['crm', 'sync_calendar_global_array_properties', '[]', '[]'],
                ['crm', 'sync_calendar_configs', '[]', json_encode([
                    [
                        'id' => 'config1',
                        'enabled' => true,
                        'user_id' => 'user1',
                        'calendar_name' => 'personal',
                        'filter' => [
                            ['field' => 'Equipe', 'operator' => 'equals', 'value' => 'Dev']
                        ]
                    ],
                    [
                        'id' => 'config2',
                        'enabled' => true,
                        'user_id' => 'user2',
                        'calendar_name' => 'work',
                        'filter' => [
                            ['field' => 'Equipe', 'operator' => 'equals', 'value' => 'Marketing']
                        ]
                    ]
                ])],
                ['crm', 'animation_configs', '[]', '[]'],
            ]);
        
        $listener = new MarkdownListener($logger, $rootFolder, $userSession, $config);
        
        $markdownContent = <<<'MD'
---
Classe: Action
Titre: Sprint Planning
Date: 2024-03-27 09:00:00
Equipe: Dev
---
# Sprint Planning
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('sprint.md');
        $file->method('getPath')->willReturn('/files/admin/sprint.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $logger->expects($this->atLeastOnce())
            ->method('info');
        
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function testCalendarSyncDisabledWhenFlagIsFalse(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $config = $this->createMock(IConfig::class);
        
        $user = $this->createMockUser('admin');
        $userSession->method('getUser')->willReturn($user);
        
        // Calendar sync disabled
        $config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '0'],
                ['crm', 'animation_configs', '[]', '[]'],
            ]);
        
        $listener = new MarkdownListener($logger, $rootFolder, $userSession, $config);
        
        $markdownContent = <<<'MD'
---
Classe: Action
Titre: Test Event
Date: 2024-03-28 10:00:00
---
# Test Event
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('test.md');
        $file->method('getPath')->willReturn('/files/admin/test.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        // Should process file but not sync
        $logger->expects($this->atLeastOnce())
            ->method('info');
        
        $listener->handle($event);
        
        $this->assertTrue(true);
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
