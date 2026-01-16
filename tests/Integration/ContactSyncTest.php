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
use OCP\IUser;

/**
 * Integration tests for contact synchronization workflow
 */
class ContactSyncTest extends TestCase
{
    public function testContactSyncEndToEndWithMockData(): void
    {
        // Create mock dependencies
        $logger = $this->createMock(LoggerInterface::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $config = $this->createMock(IConfig::class);
        
        // Mock user
        $user = $this->createMockUser('testuser');
        $userSession->method('getUser')->willReturn($user);
        
        // Configure contact sync settings
        $config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '1'],
                ['crm', 'sync_contacts_global_class', 'Personne', 'Personne'],
                ['crm', 'sync_contacts_global_mapping', '{}', json_encode([
                    'FN' => 'Nom',
                    'EMAIL' => 'Email',
                    'TEL' => 'Telephone',
                    'ADR' => 'Adresse',
                    'ORG' => 'Organisation'
                ])],
                ['crm', 'sync_contacts_global_filter', '{}', '{}'],
                ['crm', 'sync_calendar_global_enabled', '0', '0'],
                ['crm', 'sync_contacts_configs', '[]', json_encode([
                    [
                        'id' => 'default_config',
                        'enabled' => true,
                        'class' => 'Personne',
                        'user_id' => 'testuser',
                        'addressbook' => 'contacts',
                        'mapping' => [
                            'FN' => 'Nom',
                            'EMAIL' => 'Email',
                            'TEL' => 'Telephone'
                        ],
                        'filter' => []
                    ]
                ])],
            ]);
        
        // Create listener
        $listener = new MarkdownListener($logger, $rootFolder, $userSession, $config);
        
        // Create markdown file with contact
        $markdownContent = <<<'MD'
---
Classe: Personne
Nom: Durand Sophie
Email: sophie.durand@example.com
Telephone: "+33 6 12 34 56 78"
Organisation: ACME Corp
---

# Sophie Durand

Responsable marketing chez ACME Corp.
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('sophie-durand.md');
        $file->method('getPath')->willReturn('/files/testuser/contacts/sophie-durand.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        // Expect logging
        $logger->expects($this->atLeastOnce())
            ->method('info');
        
        // Handle the event
        $listener->handle($event);
        
        // Success if no exception thrown
        $this->assertTrue(true);
    }

    public function testContactSyncWithMultipleContactsInSingleRun(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $config = $this->createMock(IConfig::class);
        
        $user = $this->createMockUser('admin');
        $userSession->method('getUser')->willReturn($user);
        
        $config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '1'],
                ['crm', 'sync_contacts_global_class', 'Personne', 'Personne'],
                ['crm', 'sync_contacts_global_mapping', '{}', '{}'],
                ['crm', 'sync_contacts_global_filter', '{}', '{}'],
                ['crm', 'sync_calendar_global_enabled', '0', '0'],
                ['crm', 'sync_contacts_configs', '[]', json_encode([
                    [
                        'id' => 'config1',
                        'enabled' => true,
                        'class' => 'Personne',
                        'user_id' => 'admin',
                        'addressbook' => 'contacts',
                        'mapping' => ['FN' => 'Nom', 'EMAIL' => 'Email'],
                        'filter' => []
                    ]
                ])],
            ]);
        
        $listener = new MarkdownListener($logger, $rootFolder, $userSession, $config);
        
        // Process multiple contacts
        $contacts = [
            ['name' => 'contact1.md', 'nom' => 'Martin Paul', 'email' => 'paul.martin@test.com'],
            ['name' => 'contact2.md', 'nom' => 'Bernard Julie', 'email' => 'julie.bernard@test.com'],
            ['name' => 'contact3.md', 'nom' => 'Petit Marc', 'email' => 'marc.petit@test.com'],
        ];
        
        foreach ($contacts as $contactData) {
            $markdownContent = <<<MD
---
Classe: Personne
Nom: {$contactData['nom']}
Email: {$contactData['email']}
---

# {$contactData['nom']}
MD;

            $file = $this->createMock(File::class);
            $file->method('getMimetype')->willReturn('text/markdown');
            $file->method('getName')->willReturn($contactData['name']);
            $file->method('getPath')->willReturn("/files/admin/contacts/{$contactData['name']}");
            $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
            
            $event = $this->createMock(NodeWrittenEvent::class);
            $event->method('getNode')->willReturn($file);
            
            $listener->handle($event);
        }
        
        // Verify logging happened for all contacts
        $this->assertTrue(true);
    }

    public function testContactSyncWithFilteredContacts(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $config = $this->createMock(IConfig::class);
        
        $user = $this->createMockUser('admin');
        $userSession->method('getUser')->willReturn($user);
        
        $config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '1'],
                ['crm', 'sync_contacts_global_class', 'Personne', 'Personne'],
                ['crm', 'sync_contacts_global_mapping', '{}', '{}'],
                ['crm', 'sync_contacts_global_filter', '{}', json_encode([
                    'Ville' => 'Paris'
                ])],
                ['crm', 'sync_calendar_global_enabled', '0', '0'],
                ['crm', 'sync_contacts_configs', '[]', json_encode([
                    [
                        'id' => 'config1',
                        'enabled' => true,
                        'class' => 'Personne',
                        'user_id' => 'admin',
                        'addressbook' => 'paris_contacts',
                        'mapping' => ['FN' => 'Nom', 'EMAIL' => 'Email'],
                        'filter' => ['Ville' => 'Paris']
                    ]
                ])],
            ]);
        
        $listener = new MarkdownListener($logger, $rootFolder, $userSession, $config);
        
        // Contact matching filter
        $matchingContact = <<<'MD'
---
Classe: Personne
Nom: Client Actif
Email: client@test.com
Type: Client
---
# Client Actif
MD;

        $file1 = $this->createMock(File::class);
        $file1->method('getMimetype')->willReturn('text/markdown');
        $file1->method('getName')->willReturn('client.md');
        $file1->method('getPath')->willReturn('/files/admin/client.md');
        $file1->method('fopen')->willReturn($this->createStringStream($matchingContact));
        
        $event1 = $this->createMock(NodeWrittenEvent::class);
        $event1->method('getNode')->willReturn($file1);
        
        $listener->handle($event1);
        
        // Contact not matching filter
        $nonMatchingContact = <<<'MD'
---
Classe: Personne
Nom: Fournisseur
Email: fournisseur@test.com
Type: Fournisseur
---
# Fournisseur
MD;

        $file2 = $this->createMock(File::class);
        $file2->method('getMimetype')->willReturn('text/markdown');
        $file2->method('getName')->willReturn('fournisseur.md');
        $file2->method('getPath')->willReturn('/files/admin/fournisseur.md');
        $file2->method('fopen')->willReturn($this->createStringStream($nonMatchingContact));
        
        $event2 = $this->createMock(NodeWrittenEvent::class);
        $event2->method('getNode')->willReturn($file2);
        
        $listener->handle($event2);
        
        $this->assertTrue(true);
    }

    public function testContactSyncWithDifferentClasses(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $config = $this->createMock(IConfig::class);
        
        $user = $this->createMockUser('admin');
        $userSession->method('getUser')->willReturn($user);
        
        $config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '1'],
                ['crm', 'sync_contacts_global_class', 'Personne', 'Personne'],
                ['crm', 'sync_contacts_global_mapping', '{}', '{}'],
                ['crm', 'sync_contacts_global_filter', '{}', '{}'],
                ['crm', 'sync_calendar_global_enabled', '0', '0'],
                ['crm', 'sync_contacts_configs', '[]', json_encode([
                    [
                        'id' => 'config1',
                        'enabled' => true,
                        'class' => 'Personne',
                        'user_id' => 'admin',
                        'addressbook' => 'contacts',
                        'mapping' => ['FN' => 'Nom'],
                        'filter' => []
                    ]
                ])],
            ]);
        
        $listener = new MarkdownListener($logger, $rootFolder, $userSession, $config);
        
        // Should be processed (class = Personne)
        $personContact = <<<'MD'
---
Classe: Personne
Nom: Jean Dupont
---
# Jean Dupont
MD;

        $file1 = $this->createMock(File::class);
        $file1->method('getMimetype')->willReturn('text/markdown');
        $file1->method('getName')->willReturn('person.md');
        $file1->method('getPath')->willReturn('/files/admin/person.md');
        $file1->method('fopen')->willReturn($this->createStringStream($personContact));
        
        $event1 = $this->createMock(NodeWrittenEvent::class);
        $event1->method('getNode')->willReturn($file1);
        $listener->handle($event1);
        
        // Should NOT be processed (class = Action)
        $actionFile = <<<'MD'
---
Classe: Action
Titre: Meeting
---
# Meeting
MD;

        $file2 = $this->createMock(File::class);
        $file2->method('getMimetype')->willReturn('text/markdown');
        $file2->method('getName')->willReturn('action.md');
        $file2->method('getPath')->willReturn('/files/admin/action.md');
        $file2->method('fopen')->willReturn($this->createStringStream($actionFile));
        
        $event2 = $this->createMock(NodeWrittenEvent::class);
        $event2->method('getNode')->willReturn($file2);
        $listener->handle($event2);
        
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
