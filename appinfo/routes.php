<?php
return [
    'routes' => [
        [
            'name' => 'page#index',
            'url' => '/',
            'verb' => 'GET',
        ],
        [
            'name' => 'file#listMarkdownFiles',
            'url' => '/files/md',
            'verb' => 'GET',
        ],
       [
            'name' => 'file#getMarkdownFile',
            'url' => '/files/md/{name}',
            'verb' => 'GET',
        ],
        [
        'name' => 'file#saveMarkdownFile',
        'url' => '/files/md/save',
        'verb' => 'POST',
    ],

    [
        'name' => 'file#listConfigs',
        'url' => '/config/list',
        'verb' => 'GET',
    ],

    [
        'name' => 'file#getConfig',
        'url' => '/config/{name}',
        'verb' => 'GET',
    ],

    [
        'name' => 'settings#save',
        'url'  => '/settings/save',
        'verb' => 'POST'
    ],

    [
        'name' => 'settings#saveGeneralSettings',
        'url'  => '/settings/general',
        'verb' => 'POST'
    ],

    [
        'name' => 'settings#getGeneralSettings',
        'url'  => '/settings/general',
        'verb' => 'GET'
    ],

    [
        'name' => 'settings#saveSyncSettings',
        'url'  => '/settings/sync',
        'verb' => 'POST'
    ],

    [
        'name' => 'settings#saveAnimationConfigs',
        'url'  => '/settings/saveAnimationConfigs',
        'verb' => 'POST'
    ],

    [
        'name' => 'settings#getUserCalendars',
        'url'  => '/settings/getUserCalendars/{userId}',
        'verb' => 'GET'
    ],

    [
        'name' => 'settings#listMdFiles',
        'url' => '/settings/md-files',
        'verb' => 'GET'
    ],



    ],
];

