<?php

namespace SLONline\ExchangeRates\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SLONline\ExchangeRates\ExchangeRates;
use SLONline\ExchangeRates\Model\ExchangeRate;
use SLONline\ExchangeRates\Processor\CNB;

/**
 * Exchange rates of direct pairs and of pairs crossed through the basic currency of the processor.
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2026, SLONline, s.r.o.
 */
class ExchangeRatesTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        Config::modify()->set(ExchangeRates::class, 'registered_processor', CNB::class);
        Config::modify()->set(CNB::class, 'basic_currency', 'CZK');
        $rates = [
            ['EUR', 'CZK', 24.455],
            ['CZK', 'EUR', 0.0409],
            ['USD', 'CZK', 22.358],
            ['CZK', 'USD', 0.0447],
        ];
        foreach ($rates as [$from, $to, $rate]) {
            ExchangeRate::create()->update([
                'Date' => '2026-11-23',
                'FromCode' => $from,
                'ToCode' => $to,
                'Rate' => $rate,
            ])->write();
        }
    }

    public function testADirectPairUsesItsRate(): void
    {
        $this->assertEqualsWithDelta(24.455, ExchangeRates::create()->getExchangeRate('EUR', 'CZK'), 0.0001);
    }

    public function testAPairWithoutItsRateIsCrossedThroughTheBasicCurrency(): void
    {
        $rates = ExchangeRates::create();

        $this->assertEqualsWithDelta(24.455 / 22.358, $rates->getExchangeRate('EUR', 'USD'), 0.0001);
        $this->assertEqualsWithDelta(22.358 / 24.455, $rates->getExchangeRate('USD', 'EUR'), 0.0001);
    }
}
