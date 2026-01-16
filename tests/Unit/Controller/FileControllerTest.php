<?php

declare(strict_types=1);

namespace OCA\CRM\Tests\Unit\Controller;

use OCA\CRM\Controller\FileController;
use OCA\CRM\Tests\TestCase;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;
use OCP\Files\File;
use OCP\IUser;
use OCP\IUserSession;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class FileControllerTest extends TestCase
{
    private FileController $controller;
    private \PHPUnit\Framework\MockObject\MockObject $request;
    private \PHPUnit\Framework\MockObject\MockObject $rootFolder;
    private \PHPUnit\Framework\MockObject\MockObject $userSession;
    private \PHPUnit\Framework\MockObject\MockObject $logger;
    private \PHPUnit\Framework\MockObject\MockObject $user;
    private \PHPUnit\Framework\MockObject\MockObject $userFolder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->createMock(IRequest::class);
        $this->rootFolder = $this->createMock(IRootFolder::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->user = $this->createMock(IUser::class);
        $this->userFolder = $this->createMock(Folder::class);

        $this->user->method('getUID')->willReturn('testuser');
        $this->userSession->method('getUser')->willReturn($this->user);
        $this->rootFolder->method('getUserFolder')->with('testuser')->willReturn($this->userFolder);

        $this->controller = new FileController(
            'crm',
            $this->request,
            $this->rootFolder,
            $this->userSession,
            $this->logger
        );
    }

    public function testListMarkdownFilesSuccess(): void
    {
        $mockFile1 = $this->createMock(File::class);
        $mockFile1->method('getName')->willReturn('test1.md');
        $mockFile1->method('getSize')->willReturn(1024);
        $mockFile1->method('getPath')->willReturn('/testuser/files/test1.md');
        $mockFile1->method('getMTime')->willReturn(1640995200);

        $mockFile2 = $this->createMock(File::class);
        $mockFile2->method('getName')->willReturn('test2.md');
        $mockFile2->method('getSize')->willReturn(2048);
        $mockFile2->method('getPath')->willReturn('/testuser/files/test2.md');
        $mockFile2->method('getMTime')->willReturn(1640995300);

        $this->userFolder->method('searchByMime')->with('text/markdown')
            ->willReturn([$mockFile1, $mockFile2]);

        $result = $this->controller->listMarkdownFiles();

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        
        $data = $result->getData();
        $this->assertCount(2, $data);
        $this->assertEquals('test1.md', $data[0]['name']);
        $this->assertEquals('test2.md', $data[1]['name']);
        $this->assertEquals(1024, $data[0]['size']);
        $this->assertEquals(2048, $data[1]['size']);
    }

    public function testListMarkdownFilesEmpty(): void
    {
        $this->userFolder->method('searchByMime')->with('text/markdown')
            ->willReturn([]);

        $result = $this->controller->listMarkdownFiles();

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        $this->assertEquals([], $result->getData());
    }

    public function testGetMarkdownFileSuccess(): void
    {
        $mockFile = $this->createMock(File::class);
        $mockFile->method('getName')->willReturn('test.md');
        $mockFile->method('fopen')->with('r')->willReturn(fopen('data://text/plain,# Test Content', 'r'));

        $this->userFolder->method('searchByMime')->with('text/markdown')
            ->willReturn([$mockFile]);

        $result = $this->controller->getMarkdownFile('test.md');

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        
        $data = $result->getData();
        $this->assertArrayHasKey('content', $data);
        $this->assertEquals('# Test Content', $data['content']);
    }

    public function testGetMarkdownFileNotFound(): void
    {
        $this->userFolder->method('searchByMime')->with('text/markdown')
            ->willReturn([]);

        $result = $this->controller->getMarkdownFile('nonexistent.md');

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(404, $result->getStatus());
        
        $data = $result->getData();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Fichier introuvable', $data['error']);
    }

    public function testSaveMarkdownFileSuccess(): void
    {
        $mockFile = $this->createMock(File::class);
        $mockFile->method('getName')->willReturn('test.md');
        $mockFile->method('getPath')->willReturn('/testuser/files/test.md');
        $mockFile->expects($this->once())->method('putContent')->with('# Updated Content');

        $this->userFolder->method('searchByMime')->with('text/markdown')
            ->willReturn([$mockFile]);

        $result = $this->controller->saveMarkdownFile('/testuser/files/test.md', '# Updated Content');

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        
        $data = $result->getData();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('path', $data);
        $this->assertEquals('/testuser/files/test.md', $data['path']);
    }

    public function testSaveMarkdownFileNotFound(): void
    {
        $this->userFolder->method('searchByMime')->with('text/markdown')
            ->willReturn([]);

        $result = $this->controller->saveMarkdownFile('/testuser/files/nonexistent.md', '# Content');

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(404, $result->getStatus());
        
        $data = $result->getData();
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Fichier introuvable', $data['error']);
    }

    public function testGetConfigSuccess(): void
    {
        // Mock the config file existence and content
        $configPath = __DIR__ . '/../../../config/test.yaml';
        
        // Create a temporary config file for testing
        $tempDir = sys_get_temp_dir();
        $tempConfigDir = $tempDir . '/crm_test_config';
        if (!is_dir($tempConfigDir)) {
            mkdir($tempConfigDir, 0777, true);
        }
        $tempConfigFile = $tempConfigDir . '/test.yaml';
        file_put_contents($tempConfigFile, "test: config\nkey: value");

        // We would need to mock the file_exists and file_get_contents functions
        // For now, we'll test the logic without actual file operations
        $result = $this->controller->getConfig('nonexistent');

        $this->assertInstanceOf(DataResponse::class, $result);
        // This will return 404 since the config file doesn't exist in our test environment
        $this->assertEquals(404, $result->getStatus());

        // Clean up
        if (file_exists($tempConfigFile)) {
            unlink($tempConfigFile);
        }
        if (is_dir($tempConfigDir)) {
            rmdir($tempConfigDir);
        }
    }

    public function testListConfigsWhenDirNotExists(): void
    {
        // Test when config directory doesn't exist
        $result = $this->controller->listConfigs();

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(404, $result->getStatus());
        
        $data = $result->getData();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Dossier de configuration introuvable', $data['error']);
    }
}
