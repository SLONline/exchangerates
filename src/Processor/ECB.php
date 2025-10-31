<?php

namespace SLONline\ExchangeRates\Processor;

use GuzzleHttp\Client;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SLONline\ExchangeRates\ExchangeRates;
use SLONline\ExchangeRates\Model\ExchangeRate;

/**
 * ECB - European Central Bank Exchange Rates
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2025, SLONline, s.r.o.
 */
final class ECB implements ProcessorInterface
{
    use Injectable;
    use Configurable;

    private static string $url = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    private static string $basic_currency = 'EUR';

    public function process(): bool
    {
        $url = Config::inst()->get(self::class, 'url');
        $supportedCurrencies = Config::inst()->get(ExchangeRates::class, 'supported_currencies') ?? [];
        $basicCurrency = Config::inst()->get(self::class, 'basic_currency');

        $client = new Client();
        $response = $client->request('GET', $url);
        if ($response->getStatusCode() != 200) {
            return false;
        }

        $body = $response->getBody()->getContents();
        $xml = simplexml_load_string($body);
        if ($xml === false) {
            return false;
        }

        $rates = $xml->Cube->Cube->Cube;
        $date = date('Y-m-d', strtotime((string)$xml->Cube->Cube['time']));

        foreach ($rates as $rate) {
            if (in_array($rate['currency'], $supportedCurrencies)) {
                $toCurrency = (string)$rate['currency'];
                $object = DataList::create(ExchangeRate::class)
                    ->filter([
                        'Date' => $date,
                        'FromCode' => $basicCurrency,
                        'ToCode' => $toCurrency,
                    ])->first();
                if (!$object) {
                    $object = ExchangeRate::create();
                    $object->Date = $date;
                    $object->FromCode = $basicCurrency;
                    $object->ToCode = $toCurrency;
                    $object->Rate = (float)$rate['rate'];
                    $object->write();
                }

                $fromCurrency = (string)$rate['currency'];
                $object = DataList::create(ExchangeRate::class)
                    ->filter([
                        'Date' => $date,
                        'FromCode' => $fromCurrency,
                        'ToCode' => $basicCurrency,
                    ])->first();
                if (!$object) {
                    $object = ExchangeRate::create();
                    $object->Date = $date;
                    $object->FromCode = $fromCurrency;
                    $object->ToCode = $basicCurrency;
                    $object->Rate = 1 / (float)$rate['rate'];
                    $object->write();
                }
            }
        }

        return true;
    }
}