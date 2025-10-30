<?php

namespace SLONline\ExchangeRates\Cli\Command;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Exchange Rates Sake Command
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2025, SLONline, s.r.o.
 */
#[AsCommand(name: 'process:exchangerates', description: '<fg=blue>Downlaod exchange rates</>', hidden: true)]
class ExchangeRates extends Command
{
    use Configurable;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Environment::increaseTimeLimitTo();
        Environment::setMemoryLimitMax(-1);
        Environment::increaseMemoryLimitTo(-1);

        if (\SLONline\ExchangeRates\ExchangeRates::create()->process()) {
            return Command::SUCCESS;
        }

        return Command::INVALID;
    }
}