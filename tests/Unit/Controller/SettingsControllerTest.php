<?php

declare(strict_types=1);

namespace OCA\CRM\Tests\Unit\Controller;

use OCA\CRM\Controller\SettingsController;
use OCA\CRM\Tests\TestCase;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsControllerTest extends TestCase
{
    private SettingsController $controller;
    private \PHPUnit\Framework\MockObject\MockObject $request;
    private \PHPUnit\Framework\MockObject\MockObject $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->createMock(IRequest::class);
        $this->config = $this->createMock(IConfig::class);

        $this->controller = new SettingsController(
            'crm',
            $this->request,
            $this->config
        );
    }

    public function testSave(): void
    {
        $configData = ['setting1' => 'value1', 'setting2' => 'value2'];
        
        $this->config->expects($this->once())
            ->method('setAppValue')
            ->with('crm', 'config', json_encode($configData));

        $result = $this->controller->save($configData);

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        
        $data = $result->getData();
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('ok', $data['status']);
    }

    public function testSaveGeneralSettings(): void
    {
        $configPath = '/apps/crm/config';
        $vaultPath = 'vault';

        $this->config->expects($this->exactly(2))
            ->method('setAppValue')
            ->willReturnCallback(function ($app, $key, $value) use ($configPath, $vaultPath) {
                $this->assertEquals('crm', $app);
                if ($key === 'config_path') {
                    $this->assertEquals($configPath, $value);
                } elseif ($key === 'vault_path') {
                    $this->assertEquals($vaultPath, $value);
                }
            });

        $result = $this->controller->saveGeneralSettings($configPath, $vaultPath);

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        
        $data = $result->getData();
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('ok', $data['status']);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Paramètres enregistrés avec succès', $data['message']);
    }

    public function testGetGeneralSettings(): void
    {
        $this->config->method('getAppValue')
            ->willReturnCallback(function ($app, $key, $default) {
                $this->assertEquals('crm', $app);
                if ($key === 'config_path') {
                    return '/custom/config/path';
                } elseif ($key === 'vault_path') {
                    return 'custom_vault';
                }
                return $default;
            });

        $result = $this->controller->getGeneralSettings();

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        
        $data = $result->getData();
        $this->assertArrayHasKey('config_path', $data);
        $this->assertArrayHasKey('vault_path', $data);
        $this->assertEquals('/custom/config/path', $data['config_path']);
        $this->assertEquals('custom_vault', $data['vault_path']);
    }

    public function testGetGeneralSettingsWithDefaults(): void
    {
        $this->config->method('getAppValue')
            ->willReturnCallback(function ($app, $key, $default) {
                return $default; // Return default values
            });

        $result = $this->controller->getGeneralSettings();

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        
        $data = $result->getData();
        $this->assertEquals('/apps/crm/config', $data['config_path']);
        $this->assertEquals('vault', $data['vault_path']);
    }

    public function testSaveSyncSettings(): void
    {
        $this->config->expects($this->any())
            ->method('setAppValue')
            ->willReturnCallback(function ($app, $key, $value) {
                $this->assertEquals('crm', $app);
                
                // Test boolean conversions
                if (in_array($key, ['sync_contacts_global_enabled', 'sync_calendar_global_enabled'])) {
                    $this->assertContains($value, ['0', '1']);
                }
            });

        $result = $this->controller->saveSyncSettings(
            true,
            'Personne',
            '{"field": "value"}',
            '{"active": true}',
            '[{"id": 1}]',
            false,
            'Action',
            '{"field": "value2"}',
            '{"active": false}',
            '[{"id": 2}]'
        );

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        
        $data = $result->getData();
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('ok', $data['status']);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Paramètres de synchronisation enregistrés avec succès', $data['message']);
    }

    public function testSaveSyncSettingsWithDefaults(): void
    {
        $this->config->expects($this->any())
            ->method('setAppValue');

        $result = $this->controller->saveSyncSettings();

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
    }

    public function testListMdFiles(): void
    {
        // This test would require mocking the filesystem
        // For now, we test that the method returns a DataResponse
        $result = $this->controller->listMdFiles();

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        
        $data = $result->getData();
        $this->assertIsArray($data);
        // The array will likely be empty since the path doesn't exist in test environment
    }
}