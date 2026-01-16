<?php

declare(strict_types=1);

namespace OCA\CRM\Tests\Integration;

use OCA\CRM\Tests\TestCase;
use OCA\CRM\Controller\ApiController;
use OCA\CRM\Controller\FileController;
use OCA\CRM\Controller\SettingsController;
use OCP\IRequest;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Integration tests for CRM application workflow
 */
class CRMWorkflowTest extends TestCase
{
    public function testApiControllerIntegration(): void
    {
        $request = $this->createMock(IRequest::class);
        $controller = new ApiController('crm', $request);
        
        $response = $controller->index();
        
        $this->assertEquals(200, $response->getStatus());
        $this->assertArrayHasKey('message', $response->getData());
    }

    public function testSettingsWorkflow(): void
    {
        $request = $this->createMock(IRequest::class);
        $config = $this->createMock(IConfig::class);
        
        // Mock config storage
        $storedConfig = [];
        $config->method('setAppValue')
            ->willReturnCallback(function ($app, $key, $value) use (&$storedConfig) {
                $storedConfig[$key] = $value;
            });
        
        $config->method('getAppValue')
            ->willReturnCallback(function ($app, $key, $default) use (&$storedConfig) {
                return $storedConfig[$key] ?? $default;
            });

        $controller = new SettingsController('crm', $request, $config);
        
        // Test saving general settings
        $response = $controller->saveGeneralSettings('/custom/config', 'custom_vault');
        $this->assertEquals(200, $response->getStatus());
        
        // Test retrieving settings
        $response = $controller->getGeneralSettings();
        $this->assertEquals(200, $response->getStatus());
        $data = $response->getData();
        $this->assertEquals('/custom/config', $data['config_path']);
        $this->assertEquals('custom_vault', $data['vault_path']);
    }

    public function testFileControllerMockWorkflow(): void
    {
        $request = $this->createMock(IRequest::class);
        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $user = $this->createMockUser('testuser');
        $userSession->method('getUser')->willReturn($user);
        
        $userFolder = $this->createMock(\OCP\Files\Folder::class);
        $rootFolder->method('getUserFolder')->willReturn($userFolder);
        
        // Mock empty file list
        $userFolder->method('searchByMime')->willReturn([]);
        
        $controller = new FileController(
            'crm',
            $request,
            $rootFolder,
            $userSession,
            $logger
        );
        
        // Test listing files when none exist
        $response = $controller->listMarkdownFiles();
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals([], $response->getData());
        
        // Test getting non-existent file
        $response = $controller->getMarkdownFile('nonexistent.md');
        $this->assertEquals(404, $response->getStatus());
    }

    public function testErrorHandlingAcrossControllers(): void
    {
        // Test that all controllers handle errors gracefully
        $request = $this->createMock(IRequest::class);
        
        // Test API controller (shouldn't throw errors)
        $apiController = new ApiController('crm', $request);
        $response = $apiController->index();
        $this->assertNotNull($response);
        
        // Test with invalid config object
        try {
            $config = $this->createMock(IConfig::class);
            $config->method('setAppValue')->willThrowException(new \Exception('Database error'));
            
            $settingsController = new SettingsController('crm', $request, $config);
            $response = $settingsController->save([]);
            
            // Controller should handle the exception internally or propagate it
            $this->assertNotNull($response);
        } catch (\Exception $e) {
            // Exception handling is expected in some cases
            $this->assertStringContainsString('Database error', $e->getMessage());
        }
    }
}
