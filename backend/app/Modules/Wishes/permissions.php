<?php

return [
    [
        'key' => 'modules.wishes.view',
        'module' => 'wishes',
        'name' => '檢視許願板模組',
        'default_roles' => ['guest', 'member'],
    ],
    [
        'key' => 'wishes.view',
        'module' => 'wishes',
        'name' => '檢視公開願望',
        'default_roles' => ['guest', 'member'],
    ],
    [
        'key' => 'wishes.create',
        'module' => 'wishes',
        'name' => '新增願望',
        'default_roles' => ['guest', 'member'],
    ],
    [
        'key' => 'wishes.manage.view',
        'module' => 'wishes',
        'name' => '檢視願望管理頁',
    ],
    [
        'key' => 'wishes.update',
        'module' => 'wishes',
        'name' => '編輯願望內容',
    ],
    [
        'key' => 'wishes.status.update',
        'module' => 'wishes',
        'name' => '更新願望狀態',
    ],
    [
        'key' => 'wishes.moderate',
        'module' => 'wishes',
        'name' => '審核及調整願望可見性',
    ],
    [
        'key' => 'wishes.archive',
        'module' => 'wishes',
        'name' => '封存願望',
    ],
    [
        'key' => 'wishes.restore',
        'module' => 'wishes',
        'name' => '恢復願望',
    ],
    [
        'key' => 'wishes.history.view',
        'module' => 'wishes',
        'name' => '檢視願望變更紀錄',
    ],
];
