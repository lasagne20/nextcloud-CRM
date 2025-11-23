<?php

declare(strict_types=1);

namespace OCA\CRM\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use OCP\EventDispatcher\IEventDispatcher; 
use OCP\Contacts\IManager as ContactsManager;
use OCP\IUserManager;
use OCP\Files\Events\Node\NodeWrittenEvent;

use OCA\CRM\Listener\MarkdownListener;
use OCP\IConfig;


class Application extends App implements IBootstrap {
    public const APP_ID = 'crm';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        // Pas de services à enregistrer pour l'instant
    }

    public function boot(IBootContext $context): void {
        $logger = \OC::$server->get(LoggerInterface::class);
        $rootFolder = \OC::$server->get(IRootFolder::class);
        $contactsManager = \OC::$server->get(ContactsManager::class);
        $userManager = \OC::$server->get(IUserManager::class);
        $eventDispatcher = \OC::$server->get(IEventDispatcher::class);

        $userSession = \OC::$server->get(\OCP\IUserSession::class);

		$config = \OC::$server->get(IConfig::class);
		$markdownListener = new MarkdownListener($logger, $rootFolder, $userSession, $config);


        // Enregistrement du listener sur NodeWrittenEvent
        $eventDispatcher->addListener(NodeWrittenEvent::class, [$markdownListener, 'handle']);

		

        $logger->info('CRM Application booting... MarkdownListener registered for NodeWrittenEvent.');
    }
}
