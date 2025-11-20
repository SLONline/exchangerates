<?php

namespace SLONline\ExchangeRates\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Model\List\ArrayList;
use SLONline\ExchangeRates\ExchangeRates;

/**
 * DBMoney Extension for currency conversion
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2025, SLONline, s.r.o.
 *
 * @property \SilverStripe\ORM\FieldType\DBMoney $owner
 */
class DBMoney extends Extension
{
    public function getAmountInCurrency(string $currencyCode, ?string $date = null): ?float
    {
        $baseCurrency = $this->owner->getCurrency();
        $amount = $this->owner->getAmount();

        if (!$baseCurrency || !$currencyCode || $amount === null) {
            return null;
        }

        $rate = ExchangeRates::singleton()->getExchangeRate($baseCurrency, $currencyCode, $date);
        if ($rate === null) {
            return null;
        }

        return $amount * $rate;
    }

    public function getInAllSupportedCurrencies(?string $date = null): ArrayList
    {
        $result = ArrayList::create();
        $baseCurrency = $this->owner->getCurrency();
        $amount = $this->owner->getAmount();

        if (!$baseCurrency || $amount === null) {
            return $result;
        }

        $supportedCurrencies = ExchangeRates::config()->get('supported_currencies');
        foreach ($supportedCurrencies as $currencyCode) {
            if ($currencyCode === $baseCurrency) {
                continue;
            }

            $rate = ExchangeRates::singleton()->getExchangeRate($baseCurrency, $currencyCode, $date);
            if ($rate !== null) {
                $result->push(\SilverStripe\ORM\FieldType\DBMoney::create()
                    ->setCurrency($currencyCode)
                    ->setAmount($amount * $rate));
            }
        }

        return $result;
    }

    public function getInCurrency(string $currencyCode, ?string $date = null): ?\SilverStripe\ORM\FieldType\DBMoney
    {
        $baseCurrency = $this->owner->getCurrency();
        $amount = $this->owner->getAmount();

        if (!$baseCurrency || !$currencyCode || $amount === null) {
            return null;
        }

        $rate = ExchangeRates::singleton()->getExchangeRate($baseCurrency, $currencyCode, $date);
        if ($rate === null) {
            return null;
        }

        return \SilverStripe\ORM\FieldType\DBMoney::create()
            ->setCurrency($currencyCode)
            ->setAmount($amount * $rate);
    }

    public function getCurrencyRate(string $currencyCode, ?string $date = null): ?float
    {
        $baseCurrency = $this->owner->getCurrency();
        $amount = $this->owner->getAmount();

        if (!$baseCurrency || !$currencyCode || $amount === null) {
            return null;
        }

        return ExchangeRates::singleton()->getExchangeRate($baseCurrency, $currencyCode, $date);
    }
}