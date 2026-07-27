<?php

return [
    [
        'key' => 'modules.lottery.view',
        'module' => 'lottery',
        'name' => '檢視威力彩模組',
        'default_roles' => ['guest', 'member'],
    ],
    [
        'key' => 'lottery.generate',
        'module' => 'lottery',
        'name' => '產生威力彩號碼',
        'default_roles' => ['guest', 'member'],
    ],
    [
        'key' => 'lottery.simulate',
        'module' => 'lottery',
        'name' => '執行威力彩模擬',
        'default_roles' => ['guest', 'member'],
    ],
];
