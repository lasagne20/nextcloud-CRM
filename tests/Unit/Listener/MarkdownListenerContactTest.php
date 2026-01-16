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
use OCA\DAV\CardDAV\CardDavBackend;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for MarkdownListener contact synchronization
 */
class MarkdownListenerContactTest extends TestCase
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

    public function testHandleIgnoresNonMarkdownFiles(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/plain');
        $file->method('getName')->willReturn('test.txt');
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->listener->handle($event);
    }

    public function testHandleProcessesMarkdownFileWithContactMetadata(): void
    {
        $markdownContent = <<<'MD'
---
Classe: Personne
Nom: Dupont
Prenom: Jean
Email: jean.dupont@example.com
Telephone: 0123456789
---

# Contact Jean Dupont

Notes sur le contact.
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('jean-dupont.md');
        $file->method('getPath')->willReturn('/files/admin/contacts/jean-dupont.md');
        $file->method('getContent')->willReturn($markdownContent);
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        // Mock config to enable contact sync
        $this->config->method('getAppValue')
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
                        'mapping' => ['FN' => 'Nom', 'EMAIL' => 'Email', 'TEL' => 'Telephone']
                    ]
                ])],
            ]);
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->listener->handle($event);
    }

    public function testContactSyncDisabledWhenGlobalFlagIsFalse(): void
    {
        $markdownContent = <<<'MD'
---
Classe: Personne
Nom: Test
---
Contact test
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('test.md');
        $file->method('getPath')->willReturn('/files/admin/test.md');        $file->method('getContent')->willReturn($markdownContent);        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        // Disable contact sync
        $this->config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '0'],
                ['crm', 'sync_calendar_global_enabled', '0', '0'],
            ]);
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->listener->handle($event);
    }

    public function testContactMetadataParsing(): void
    {
        $markdownContent = <<<'MD'
---
Classe: Personne
Nom: Martin
Prenom: Sophie
Email: sophie.martin@example.com
Telephone: "+33 6 12 34 56 78"
Ville: Paris
---

# Sophie Martin
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('sophie-martin.md');
        $file->method('getPath')->willReturn('/files/admin/sophie-martin.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $this->config->method('getAppValue')
            ->willReturnMap([
                ['crm', 'sync_contacts_global_enabled', '0', '1'],
                ['crm', 'sync_contacts_global_class', 'Personne', 'Personne'],
                ['crm', 'sync_contacts_global_mapping', '{}', '{}'],
                ['crm', 'sync_contacts_global_filter', '{}', '{}'],
                ['crm', 'sync_calendar_global_enabled', '0', '0'],
                ['crm', 'sync_contacts_configs', '[]', '[]'],
            ]);
        
        $event = $this->createMock(NodeWrittenEvent::class);
        $event->method('getNode')->willReturn($file);
        
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->listener->handle($event);
    }

    public function testContactSyncWithMultipleConfigs(): void
    {
        $markdownContent = <<<'MD'
---
Classe: Personne
Nom: Durand
Email: durand@example.com
---
Contact Durand
MD;

        $file = $this->createMock(File::class);
        $file->method('getMimetype')->willReturn('text/markdown');
        $file->method('getName')->willReturn('durand.md');
        $file->method('getPath')->willReturn('/files/admin/durand.md');
        $file->method('fopen')->willReturn($this->createStringStream($markdownContent));
        
        $this->config->method('getAppValue')
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
                        'user_id' => 'user1',
                        'addressbook' => 'contacts',
                        'mapping' => ['FN' => 'Nom']
                    ],
                    [
                        'id' => 'config2',
                        'enabled' => true,
                        'class' => 'Personne',
                        'user_id' => 'user2',
                        'addressbook' => 'contacts',
                        'mapping' => ['FN' => 'Nom']
                    ]
                ])],
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
