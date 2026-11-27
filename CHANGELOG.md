# Changelog

Releases of branch `main` that projects (briefcase, suitcase, dstype, antikvariatsteiner) should know about. Projects
require `slonline/exchangerates: ^6.0` and get the tagged releases from packages.slonline.sk.

## 6.1.0 (unreleased)

### Added

- `process:exchangerates-history [--from=2014-01-01] [--to=]` stores the past rates of the CNB year by year (one
  request per year), so amounts of older orders are converted with the rate of their day. Only the missing rates of
  the supported currencies are stored; it can run again.

### Fixed

- `getExchangeRate()` no longer calls itself without end when a pair with the basic currency of the processor has no
  rate (e.g. EUR→CZK before the first stored day): it returns null, the rate is not known. Such a lookup filled the
  memory until the process failed (the statistics of commerce-stats on briefcase and suitcase, SLE-19).
- A rate looked up before for an earlier day is no longer returned for a later one: the cache is kept per pair and
  day, and the rate of a day is the latest stored on or before it.

## 6.0.6 (2026-11-24)

### Fixed

- `ExchangeRates::getExchangeRate()` returned the inverted rate for a pair without its own rate, crossed through the
  basic currency of the processor: with CNB, EUR→USD gave (USD→CZK)/(EUR→CZK) = 0.914 instead of 1.094. Conversions
  such as `DBMoney::getInCurrency()` and `getCurrencyRate()` now give the right amount. Direct pairs with the basic
  currency were right before.
