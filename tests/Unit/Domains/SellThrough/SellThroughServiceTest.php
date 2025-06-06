<?php

declare(strict_types=1);

use App\Domains\SellThroughAggregate\Services\SellThroughServices;

test('getOnlyTenSellThrough method will return the array as expected', function (): void {
    $labels = [
        'test1',
        'test2',
        'test3',
        'test4',
        'test5',
        'test6',
        'test7',
        'test8',
        'test9',
        'test10',
        'test11',
    ];

    $saleThrough = ['1.1', '1.2', '1.3', '1.4', '1.5', '1.6', '1.7', '1.8', '1.9', '1.10', '1.11'];

    $sellThroughServices = new SellThroughServices();
    $response = $sellThroughServices->getOnlyTenSellThrough($labels, $saleThrough, 2);

    expect($response)->toBe([
        'labels' => [
            'test1',
            'test2',
            'test3',
            'test4',
            'test5',
            'test6',
            'test7',
            'test8',
            'test9',
            'test10',
            'Other',
        ],
        'sell_through' => ['1.1', '1.2', '1.3', '1.4', '1.5', '1.6', '1.7', '1.8', '1.9', '1.10', 2.0],
    ]);
});
