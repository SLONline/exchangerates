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

    public function testAMissingRateOfTheBasicCurrencyIsNotKnown(): void
    {
        $rates = ExchangeRates::create();

        $this->assertNull($rates->getExchangeRate('EUR', 'CZK', '2014-07-08'));
        $this->assertNull($rates->getExchangeRate('CZK', 'EUR', '2014-07-08'));
        $this->assertNull($rates->getExchangeRate('EUR', 'USD', '2014-07-08'));
        $this->assertNull($rates->getExchangeRate('GBP', 'CZK'));
    }

    public function testTheRateOfADayIsTheLatestOnOrBeforeIt(): void
    {
        ExchangeRate::create()->update([
            'Date' => '2014-07-08',
            'FromCode' => 'EUR',
            'ToCode' => 'CZK',
            'Rate' => 27.4,
        ])->write();
        $rates = ExchangeRates::create();

        $this->assertEqualsWithDelta(27.4, $rates->getExchangeRate('EUR', 'CZK', '2014-07-08'), 0.0001);
        $this->assertEqualsWithDelta(27.4, $rates->getExchangeRate('EUR', 'CZK', '2020-01-01'), 0.0001);
        $this->assertEqualsWithDelta(24.455, $rates->getExchangeRate('EUR', 'CZK', '2026-11-24'), 0.0001);
        $this->assertEqualsWithDelta(24.455, $rates->getExchangeRate('EUR', 'CZK'), 0.0001);
    }

    public function testTheYearlyFileOfTheCnbIsParsedPerUnit(): void
    {
        $body = "Date|1 EUR|100 HUF|1 USD\n02.01.2014|27.480|9.202|20.120\n03.01.2014|27.520|9.223|20.186\n";

        $days = CNB::create()->parseYear($body);

        $this->assertSame(['2014-01-02', '2014-01-03'], array_keys($days));
        $this->assertEqualsWithDelta(27.48, $days['2014-01-02']['EUR'], 0.00001);
        $this->assertEqualsWithDelta(0.09202, $days['2014-01-02']['HUF'], 0.00001);
        $this->assertEqualsWithDelta(20.186, $days['2014-01-03']['USD'], 0.00001);
    }
}
