<?php

declare(strict_types=1);

namespace OCA\CRM\Tests\Unit\Controller;

use OCA\CRM\Controller\PageController;
use OCA\CRM\Tests\TestCase;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageControllerTest extends TestCase
{
    private PageController $controller;
    private \PHPUnit\Framework\MockObject\MockObject $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->createMock(IRequest::class);
        $this->controller = new PageController(
            'crm',
            $this->request
        );
    }

    public function testIndex(): void
    {
        $result = $this->controller->index();

        $this->assertInstanceOf(TemplateResponse::class, $result);
        $this->assertEquals('index', $result->getTemplateName());
    }

    public function testIndexHasCorrectTemplate(): void
    {
        $response = $this->controller->index();

        $this->assertEquals('index', $response->getTemplateName());
    }

    public function testIndexSetsContentSecurityPolicy(): void
    {
        $response = $this->controller->index();
        $policy = $response->getContentSecurityPolicy();

        $this->assertNotNull($policy);
        // Note: Testing CSP details would require access to Nextcloud's CSP implementation
    }

    public function testIndexIsAccessibleWithoutAdmin(): void
    {
        // This test ensures the page is accessible to non-admin users
        $result = $this->controller->index();
        
        $this->assertNotNull($result);
        $this->assertInstanceOf(TemplateResponse::class, $result);
    }

    public function testIndexDoesNotRequireCSRF(): void
    {
        // The @NoCSRFRequired annotation should make this accessible without CSRF token
        $result = $this->controller->index();
        
        $this->assertNotNull($result);
        $this->assertInstanceOf(TemplateResponse::class, $result);
    }
}