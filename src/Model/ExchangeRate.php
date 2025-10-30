<?php

namespace SLONline\ExchangeRates\Model;

use SilverStripe\ORM\DataObject;

/**
 * Exchange Rate Data Object
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2025, SLONline, s.r.o.
 *
 * @property string $Date
 * @property string $FromCode
 * @property string $ToCode
 * @property float  $Rate
 */
class ExchangeRate extends DataObject
{
    private static string $table_name = 'ExchangeRates';
    private static string $singular_name = 'Exchange rate';
    private static string $plural_name = 'Exchange rates';

    private static array $db = [
        'Date' => 'Date',
        'FromCode' => 'Varchar(3)',
        'ToCode' => 'Varchar(3)',
        'Rate' => 'Decimal(10,4)',
    ];

    private static array $indexes = [
        'ToCodeI' => [
            'type' => 'index',
            'columns' => ['ToCode'],
        ],
        'DateI' => [
            'type' => 'index',
            'columns' => ['Date'],
        ],
        'ClassNameDateToCodeI' => [
            'type' => 'index',
            'columns' => ['ClassName', 'Date', 'ToCode'],
        ],
    ];

    private static string $default_sort = 'Date DESC';
}