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

    /**
     * @var array<string, float|null>
     */
    private array $cachedRates = [];

    private ?bool $hasRates = null;

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
     * Rate that converts an amount in the first currency to the second one at the date (the latest rate on or before
     * it), else now; a pair without its own rate is crossed through the basic currency of the processor. Null when
     * the rate is not known; 1 while no rates are stored at all.
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency, ?string $date = null): ?float
    {
        if ($fromCurrency == $toCurrency) {
            return 1;
        }
        if (!$this->hasRates()) {
            return 1;
        }
        $day = !empty($date) ? date('Y-m-d', strtotime($date)) : '';
        $key = $fromCurrency . '|' . $toCurrency . '|' . $day;
        if (!array_key_exists($key, $this->cachedRates)) {
            $this->cachedRates[$key] = $this->storedRate($fromCurrency, $toCurrency, $day)
                ?? $this->crossedRate($fromCurrency, $toCurrency, $date);
        }

        return $this->cachedRates[$key];
    }

    private function storedRate(string $fromCurrency, string $toCurrency, string $day): ?float
    {
        $filter = [
            'FromCode' => $fromCurrency,
            'ToCode' => $toCurrency,
        ];
        if ($day !== '') {
            $filter['Date:LessThanOrEqual'] = $day;
        }
        $exchangeRate = DataList::create(ExchangeRate::class)->filter($filter)->sort('Date', 'DESC')->first();

        return $exchangeRate ? (float)$exchangeRate->Rate : null;
    }

    /**
     * The pair through the basic currency of the processor; null when one of the currencies is the basic one (its
     * direct rate is missing, crossing would ask for the same pair again) or a leg is not known.
     */
    private function crossedRate(string $fromCurrency, string $toCurrency, ?string $date): ?float
    {
        $basicCurrency = null;
        $registeredProcessor = Config::inst()->get(self::class, 'registered_processor');
        if ($registeredProcessor && class_exists($registeredProcessor)) {
            $basicCurrency = Config::inst()->get($registeredProcessor, 'basic_currency');
        }
        if (!$basicCurrency || $fromCurrency === $basicCurrency || $toCurrency === $basicCurrency) {
            return null;
        }
        $rateFromBasic = $this->getExchangeRate($fromCurrency, $basicCurrency, $date);
        $rateToBasic = $this->getExchangeRate($toCurrency, $basicCurrency, $date);
        if ($rateFromBasic === null || $rateToBasic === null || $rateToBasic == 0.0) {
            return null;
        }

        return $rateFromBasic / $rateToBasic;
    }

    private function hasRates(): bool
    {
        $this->hasRates ??= DataList::create(ExchangeRate::class)->exists();

        return $this->hasRates;
    }
}
