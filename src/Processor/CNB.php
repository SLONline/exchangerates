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

    private static string $basic_currency = 'CZK';

    /**
     * The rates of a whole year, one line per day (the history of process:exchangerates-history).
     */
    private static string $year_url = 'https://www.cnb.cz/en/financial-markets/foreign-exchange-market/'
        . 'central-bank-exchange-rate-fixing/central-bank-exchange-rate-fixing/year.txt';

    public function process()
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
                    $toCurrency = (string)$rate[3];
                    $obj = DataList::create(ExchangeRate::class)
                        ->filter([
                            'Date' => $date,
                            'FromCode' => $basicCurrency,
                            'ToCode' => $toCurrency,
                        ])->first();
                    if (!$obj) {
                        $obj = ExchangeRate::create();
                        $obj->Date = $date;
                        $obj->FromCode = $basicCurrency;
                        $obj->ToCode = $toCurrency;
                        $obj->Rate = 1 / (float)($rate[4] / $rate[2]);
                        $obj->write();
                    }

                    $fromCurrency = (string)$rate[3];
                    $obj = DataList::create(ExchangeRate::class)
                        ->filter([
                            'Date' => $date,
                            'FromCode' => $fromCurrency,
                            'ToCode' => $basicCurrency,
                        ])->first();
                    if (!$obj) {
                        $obj = ExchangeRate::create();
                        $obj->Date = $date;
                        $obj->FromCode = $fromCurrency;
                        $obj->ToCode = $basicCurrency;
                        $obj->Rate = (float)$rate[4] / $rate[2];
                        $obj->write();
                    }
                }
            }
        }

        return true;
    }

    /**
     * Stores the missing rates of the supported currencies of the days of a year between the dates (Y-m-d, both
     * included, empty for no limit), in both directions like process(); returns how many rates it stored.
     */
    public function processYear(int $year, string $from = '', string $to = ''): int
    {
        $response = (new Client())->request('GET', (string)static::config()->get('year_url'), [
            'query' => ['year' => $year],
        ]);
        if ($response->getStatusCode() !== 200) {
            return 0;
        }
        $stored = 0;
        foreach ($this->parseYear($response->getBody()->getContents()) as $date => $rates) {
            if (($from !== '' && $date < $from) || ($to !== '' && $date > $to)) {
                continue;
            }
            $stored += $this->storeRates($date, $rates);
        }

        return $stored;
    }

    /**
     * The rates of a yearly file of the CNB: CZK for one unit of each currency, by the day (Y-m-d) and the code.
     *
     * @return array<string, array<string, float>>
     */
    public function parseYear(string $body): array
    {
        $lines = preg_split('/\R/', trim($body)) ?: [];
        $header = explode('|', (string)array_shift($lines));
        $columns = [];
        foreach (array_slice($header, 1, null, true) as $index => $title) {
            if (preg_match('/^(\d+)\s+([A-Z]{3})$/', trim($title), $match)) {
                $columns[$index] = [$match[2], (float)$match[1]];
            }
        }
        $days = [];
        foreach ($lines as $line) {
            $cells = explode('|', trim($line));
            $day = \DateTime::createFromFormat('!d.m.Y', (string)$cells[0]);
            if (!$day) {
                continue;
            }
            foreach ($columns as $index => [$code, $amount]) {
                $value = (float)str_replace(',', '.', (string)($cells[$index] ?? ''));
                if ($value > 0 && $amount > 0) {
                    $days[$day->format('Y-m-d')][$code] = $value / $amount;
                }
            }
        }

        return $days;
    }

    /**
     * @param array<string, float> $rates CZK for one unit of each currency
     */
    private function storeRates(string $date, array $rates): int
    {
        $supported = (array)(Config::inst()->get(ExchangeRates::class, 'supported_currencies') ?? []);
        $basic = (string)static::config()->get('basic_currency');
        $stored = 0;
        foreach ($rates as $code => $rate) {
            if (!in_array($code, $supported, true)) {
                continue;
            }
            foreach ([[$basic, $code, 1 / $rate], [$code, $basic, $rate]] as [$fromCode, $toCode, $value]) {
                $exists = DataList::create(ExchangeRate::class)
                    ->filter(['Date' => $date, 'FromCode' => $fromCode, 'ToCode' => $toCode])
                    ->exists();
                if ($exists) {
                    continue;
                }
                ExchangeRate::create([
                    'Date' => $date,
                    'FromCode' => $fromCode,
                    'ToCode' => $toCode,
                    'Rate' => $value,
                ])->write();
                $stored++;
            }
        }

        return $stored;
    }
}
