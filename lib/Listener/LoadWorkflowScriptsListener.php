<?php

declare(strict_types=1);

namespace OCA\CRM\Listener;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * @template-implements IEventListener<BeforeTemplateRenderedEvent>
 */
class LoadWorkflowScriptsListener implements IEventListener {
    public function handle(Event $event): void {
        if (!($event instanceof BeforeTemplateRenderedEvent)) {
            return;
        }

        // Only load on admin/workflow pages
        if ($event->isLoggedIn() && str_contains($_SERVER['REQUEST_URI'] ?? '', '/settings/admin/workflow')) {
            Util::addScript('crm', 'workflowengine-check');
        }
    }
}
