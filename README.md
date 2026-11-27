# Exchange Rates for Silverstripe 6

### Requirements
* **silverstripe/framework** ~6.0

### Installation
Add the following code to the `require` key in `composer.json` file:
```bash
composer require slonline/exchangerates
```
Add  configuration into yml, like this:
```yml
SLONline\ExchangeRates\ExchangeRates:
  registered_processor: SLONline\ExchangeRates\Processor\ECB
  supported_currencies:
    - EUR
    - USD
```

## Loading data
Data can be loaded via sake command 
```shell
php vendor/silverstripe/framework/bin/sake process:exchangerates
```
or in script by calling
```php
SLONline\ExchangeRates\ExchangeRates::create()->process();
```
or
```php
SLONline\ExchangeRates\ExchangeRates::singleton()->process();
```

### Past rates (CNB)

The processor of the CNB downloads the rates of a day. The rates of the past are stored by a command, year by year
from a date (the default 2014-01-01) until today; only the missing rates of the supported currencies are added:

```shell
vendor/bin/sake process:exchangerates-history --from=2014-01-01
```

A rate of a day is the latest stored on or before it. A pair with the basic currency without any such rate is not
known (`getExchangeRate()` returns null).

## Converting amount from the basic currency to the second
1. Getting the latest value
```php
SLONline\ExchangeRates\ExchangeRates::singleton()->getExchangeRate('EUR','USD');
```
2. Getting the value for specific date if exists
```php
SLONline\ExchangeRates\ExchangeRates::singleton()->getExchangeRate('EUR','USD', '2025-10-30');
```
Calling it via singleton is recommended to avoid multiple loading of rates from database.

### Conversion is available on DBMoney field via extension
First method for getting converted value from base currency to target currency.
```php
$money = DBMoney::create('money')->setAmount( 100)->setCurrency('EUR');
Debug::dump($money->getAmountInCurrency('USD')); // will output amount converted to USD

Debug::dump($money->getInAllSupportedCurrencies()); // will output ArrayList with amounts in all supported currencies
```

## Processors
Processor is main class for downloading, parsing and writing exchange rates from some source.
Processed data are stored into data objects ExchangeRate

### Available processors
1. ECB - European Central Bank
```yml
SLONline\ExchangeRates\ExchangeRates:
  registered_processor: SLONline\ExchangeRates\Processor\ECB
```
2. CNB - Czech National Bank
```yml
SLONline\ExchangeRates\ExchangeRates:
  registered_processor: SLONline\ExchangeRates\Processor\CNB
```
### Adding new processor
If you want to add new source of rates, you can create new processor. 
Each processor should implement ProcessorInterface.

## Tests

The module cannot boot Silverstripe on its own, so a project runs its tests:

```bash
SS_PHPUNIT_FLUSH=1 vendor/bin/phpunit vendor/slonline/exchangerates/tests/php
```
