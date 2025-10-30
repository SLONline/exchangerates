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
 * CNB - Czech National Bank Exchange Rates
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2025, SLONline, s.r.o.
 */
final class CNB implements ProcessorInterface
{
    use Injectable;
    use Configurable;

    private static $url = 'https://www.cnb.cz/en/financial-markets/foreign-exchange-market/central-bank-exchange-rate-fixing/central-bank-exchange-rate-fixing/daily.txt';

    public function process()
    {
        $url = Config::inst()->get(self::class, 'url');
        $supportedCurrencies = Config::inst()->get(ExchangeRates::class, 'supported_currencies') ?? [];

        $client = new Client();
        $response = $client->request('GET', $url);
        if ($response->getStatusCode() != 200) {
            return false;
        }

        $body = $response->getBody()->getContents();
        $body = explode("\n", $body);

        //check if date is correct
        if (count($body) > 2 &&
            preg_match('/\d{2} \w{3} \d{4}/smi', $body[0], $matchDate) &&
            preg_match('/Country\|Currency\|Amount\|Code\|Rate/smi', $body[1], $matchHeader)) {
            $date = date('Y-m-d', strtotime($matchDate[0]));
            for ($i = 2; $i < count($body); $i++) {
                $rate = explode('|', $body[$i]);
                if (count($rate) == 5 &&
                    in_array($rate[3], $supportedCurrencies)) {
                    $fromCurrency = 'CZK';
                    $toCurrency = (string)$rate[3];
                    $obj = DataList::create(ExchangeRate::class)
                        ->filter([
                            'Date' => $date,
                            'FromCode' => $fromCurrency,
                            'ToCode' => $toCurrency,
                        ])->first();
                    if (!$obj) {
                        $obj = ExchangeRate::create();
                        $obj->Date = $date;
                        $obj->FromCode = $fromCurrency;
                        $obj->ToCode = $toCurrency;
                        $obj->Rate = 1 / (float)($rate[4] / $rate[2]);
                        $obj->write();
                    }

                    $fromCurrency = (string)$rate[3];
                    $toCurrency = 'CZK';
                    $obj = DataList::create(ExchangeRate::class)
                        ->filter([
                            'Date' => $date,
                            'FromCode' => $fromCurrency,
                            'ToCode' => $toCurrency,
                        ])->first();
                    if (!$obj) {
                        $obj = ExchangeRate::create();
                        $obj->Date = $date;
                        $obj->FromCode = $fromCurrency;
                        $obj->ToCode = $toCurrency;
                        $obj->Rate = (float)$rate[4] / $rate[2];
                        $obj->write();
                    }
                }
            }
        }

        return true;
    }
}