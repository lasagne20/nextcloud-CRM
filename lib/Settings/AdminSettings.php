<?php

namespace OCA\CRM\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;

class AdminSettings implements ISettings {

    private IConfig $config;
    private IGroupManager $groupManager;
    private IUserManager $userManager;

    public function __construct(IConfig $config, IGroupManager $groupManager, IUserManager $userManager) {
        $this->config = $config;
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
    }

    public function getForm(): TemplateResponse {
        $groups = array_map(fn($g) => $g->getGID(), $this->groupManager->search(''));
        $users  = array_map(fn($u) => $u->getUID(), $this->userManager->search(''));

        return new TemplateResponse(
            'crm',
            'admin-settings',
            [
                'config' => $this->config->getAppValue('crm', 'config', '[]'),
                'config_path' => $this->config->getAppValue('crm', 'config_path', '/apps/crm/config'),
                'vault_path' => $this->config->getAppValue('crm', 'vault_path', 'vault'),
                'groups'   => $groups,
                'users'    => $users
            ]
        );
    }

    public function getSection(): string {
        return 'additional';
    }

    public function getPriority(): int {
        return 50;
    }
}
