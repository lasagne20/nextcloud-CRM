<?php

declare(strict_types=1);

namespace OCA\CRM\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case class with common setup for CRM tests
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize any common test setup here
        $this->setUpMocks();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up any test artifacts
    }

    /**
     * Set up common mocks for Nextcloud dependencies
     */
    protected function setUpMocks(): void
    {
        // Mock common Nextcloud interfaces if not available
        if (!interface_exists('OCP\IRequest')) {
            $this->createMockInterface('OCP\IRequest');
        }
        
        if (!interface_exists('OCP\IConfig')) {
            $this->createMockInterface('OCP\IConfig');
        }
        
        if (!interface_exists('OCP\Files\IRootFolder')) {
            $this->createMockInterface('OCP\Files\IRootFolder');
        }
        
        if (!interface_exists('OCP\IUserSession')) {
            $this->createMockInterface('OCP\IUserSession');
        }
    }

    /**
     * Create a mock interface if it doesn't exist
     */
    private function createMockInterface(string $interface): void
    {
        if (!interface_exists($interface)) {
            $parts = explode('\\', $interface);
            $className = array_pop($parts);
            $namespace = implode('\\', $parts);
            eval("namespace $namespace; interface $className {}");
        }
    }

    /**
     * Create a mock user for testing
     */
    protected function createMockUser(string $uid = 'testuser'): \PHPUnit\Framework\MockObject\MockObject
    {
        $user = $this->createMock(\OCP\IUser::class);
        $user->method('getUID')->willReturn($uid);
        return $user;
    }

    /**
     * Create a mock request with parameters
     */
    protected function createMockRequest(array $params = []): \PHPUnit\Framework\MockObject\MockObject
    {
        $request = $this->createMock(\OCP\IRequest::class);
        $request->method('getParam')->willReturnCallback(function($key) use ($params) {
            return $params[$key] ?? null;
        });
        return $request;
    }

    /**
     * Create a mock config with common CRM app values
     */
    protected function createMockConfig(array $appValues = []): \PHPUnit\Framework\MockObject\MockObject
    {
        $config = $this->createMock(\OCP\IConfig::class);
        
        // Default values for CRM
        $defaults = [
            'sync_contacts_global_enabled' => '0',
            'sync_contacts_global_class' => 'Personne',
            'sync_contacts_global_mapping' => '{}',
            'sync_contacts_global_filter' => '{}',
            'sync_contacts_configs' => '[]',
            'sync_calendar_global_enabled' => '0',
            'sync_calendar_global_class' => 'Evenement',
            'sync_calendar_global_mapping' => '{}',
            'sync_calendar_global_filter' => '{}',
            'sync_calendar_global_array_properties' => '[]',
            'sync_calendar_configs' => '[]',
            'animation_configs' => '[]',
        ];
        
        $merged = array_merge($defaults, $appValues);
        
        $returnMap = [];
        foreach ($merged as $key => $value) {
            $returnMap[] = ['crm', $key, $defaults[$key] ?? '', $value];
        }
        
        $config->method('getAppValue')->willReturnMap($returnMap);
        
        return $config;
    }
}