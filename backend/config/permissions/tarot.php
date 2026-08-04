<?php

/** Tarot permission definitions. */
return [
    [
        'key' => 'modules.tarot.view',
        'module' => 'tarot',
        'name' => '檢視塔羅模組',
        'default_roles' => ['guest', 'member'],
    ],
    [
        'key' => 'tarot.read',
        'module' => 'tarot',
        'name' => '進行塔羅解讀',
        'default_roles' => ['guest', 'member'],
    ],
];
