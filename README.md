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

## Converting amount from the basic currency to the second
1. Getting the latest value
```php
SLONline\ExchangeRates\ExchangeRates::singleton()->getExchangeRate('EUR','USD');
```
2. Getting the value for specific date if exists
```php
SLONline\ExchangeRates\ExchangeRates::singleton()->getExchangeRate('EUR','USD', '2025-10-30');
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