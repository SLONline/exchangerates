# Changelog

Releases of branch `main` that projects (briefcase, suitcase, dstype, antikvariatsteiner) should know about. Projects
require `slonline/exchangerates: ^6.0` and get the tagged releases from packages.slonline.sk.

## 6.0.6 (unreleased)

### Fixed

- `ExchangeRates::getExchangeRate()` returned the inverted rate for a pair without its own rate, crossed through the
  basic currency of the processor: with CNB, EUR→USD gave (USD→CZK)/(EUR→CZK) = 0.914 instead of 1.094. Conversions
  such as `DBMoney::getInCurrency()` and `getCurrencyRate()` now give the right amount. Direct pairs with the basic
  currency were right before.
