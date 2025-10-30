<?php

namespace SLONline\ExchangeRates\Processor;

/**
 * Processor Interface for exchange rates
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2025, SLONline, s.r.o.
 */
interface ProcessorInterface
{
    public function process();
}