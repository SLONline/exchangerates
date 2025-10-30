<?php

namespace SLONline\ExchangeRates;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SLONline\ExchangeRates\Model\ExchangeRate;

/**
 * Main Exchange Rates object used for calculating exchange rates
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2025, SLONline, s.r.o.
 */
class ExchangeRates
{
    use Injectable;
    use Configurable;

    private static ?string $registered_processor = null;

    private static array $supported_currencies = [];

    public function process(): bool
    {
        $registeredProcessor = Config::inst()->get(self::class, 'registered_processor');
        if ($registeredProcessor && class_exists($registeredProcessor)) {
            $processor = $registeredProcessor::create();
            if ($processor && method_exists($processor, 'process')) {
                return $processor->process();
            }
        }

        return false;
    }


    /**
     * Gets exchange rate for currency abbreviation
     *
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param string $date
     * @return float
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency, string $date = ''): float
    {
        if ($fromCurrency == $toCurrency) {
            return 1;
        }

        $filter = [
            'FromCode' => $fromCurrency,
            'ToCode' => $toCurrency,
        ];

        if (!empty($date)) {
            $filter['Date:LessThanOrEqual'] = date('Y-m-d', strtotime($date));
        }

        $obj = DataList::create(ExchangeRate::class)
            ->filter($filter)->first();
        if ($obj) {
            return $obj->Rate;
        }

        return 0.0;
    }
}