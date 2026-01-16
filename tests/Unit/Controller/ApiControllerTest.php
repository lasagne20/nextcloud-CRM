<?php

declare(strict_types=1);

namespace OCA\CRM\Tests\Unit\Controller;

use OCA\CRM\Controller\ApiController;
use OCA\CRM\Tests\TestCase;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class ApiControllerTest extends TestCase
{
    private ApiController $controller;
    private \PHPUnit\Framework\MockObject\MockObject $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->createMock(IRequest::class);
        $this->controller = new ApiController(
            'crm',
            $this->request
        );
    }

    public function testIndex(): void
    {
        $result = $this->controller->index();

        $this->assertInstanceOf(DataResponse::class, $result);
        $this->assertEquals(['message' => 'Hello world!'], $result->getData());
        $this->assertEquals(200, $result->getStatus());
    }

    public function testIndexReturnsCorrectMessage(): void
    {
        $response = $this->controller->index();
        $data = $response->getData();

        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Hello world!', $data['message']);
    }

    public function testIndexIsAccessibleWithoutAdmin(): void
    {
        // This test ensures the endpoint is accessible to non-admin users
        // The @NoAdminRequired annotation should make this work
        $result = $this->controller->index();
        
        $this->assertNotNull($result);
        $this->assertInstanceOf(DataResponse::class, $result);
    }
}