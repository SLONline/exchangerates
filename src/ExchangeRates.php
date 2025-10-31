<?php

namespace SLONline\ExchangeRates;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\List\ArrayList;
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

    private ArrayList $cachedRates;

    public function __construct()
    {
        $this->cachedRates = ArrayList::create();
    }

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
     * @param string|null $date
     * @return float|null
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency, ?string $date = null): ?float
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

        $exchangeRate = $this->cachedRates->filter($filter)->first();
        if ($exchangeRate) {
            return $exchangeRate->Rate;
        }

        $exchangeRate = DataList::create(ExchangeRate::class)->filter($filter)->first();
        if ($exchangeRate) {
            $this->cachedRates->push($exchangeRate);
        } else {
            $basicCurrency = null;
            $registeredProcessor = Config::inst()->get(self::class, 'registered_processor');
            if ($registeredProcessor && class_exists($registeredProcessor)) {
                $basicCurrency = Config::inst()->get($registeredProcessor, 'basic_currency');
            }
            if ($basicCurrency) {
                $rateFromBasic = $this->getExchangeRate($fromCurrency, $basicCurrency, $date);
                $rateToBasic = $this->getExchangeRate($toCurrency, $basicCurrency, $date);
                if ($rateFromBasic !== null && $rateToBasic !== null) {
                    return $rateToBasic / $rateFromBasic;
                }
            }
        }

        return $exchangeRate?->Rate;
    }
}