<?php

declare(strict_types=1);

namespace OCA\CRM\Controller;

use OCA\CRM\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller {
	public function __construct(string $appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse {
		$response = new TemplateResponse(Application::APP_ID, 'index');
		
		// Ajouter une politique CSP plus permissive pour les modules ES6
		// Note: Nextcloud gère automatiquement les nonces pour les scripts
		$policy = new \OCP\AppFramework\Http\ContentSecurityPolicy();
		$policy->addAllowedScriptDomain('\'self\'');
		$response->setContentSecurityPolicy($policy);
		
		return $response;
	}

}
