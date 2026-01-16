<?php

declare(strict_types=1);

namespace OCA\CRM\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;

/**
 * CRM Application
 * 
 * Provides workflow filtering by markdown YAML frontmatter metadata
 */
class Application extends App implements IBootstrap {
    public const APP_ID = 'crm';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        // Register JavaScript to load the MarkdownMetadataCheck UI in workflow settings
        $context->registerEventListener(
            BeforeTemplateRenderedEvent::class,
            \OCA\CRM\Listener\LoadWorkflowScriptsListener::class
        );

        // Register Markdown file listener for contact/calendar synchronization
        $context->registerEventListener(
            NodeWrittenEvent::class,
            \OCA\CRM\Listener\MarkdownListener::class
        );
    }

    public function boot(IBootContext $context): void {
        // Application is ready
    }
}
