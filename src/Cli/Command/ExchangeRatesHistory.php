<?php

namespace SLONline\ExchangeRates\Cli\Command;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SLONline\ExchangeRates\ExchangeRates;
use SLONline\ExchangeRates\Processor\CNB;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Stores the missing rates of the CNB from a date until today, year by year (process:exchangerates-history), so the
 * amounts of older orders can be converted with the rate of their day.
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2026, SLONline, s.r.o.
 */
#[AsCommand(
    name: 'process:exchangerates-history',
    description: '<fg=blue>Download the past exchange rates of the CNB from a date</>'
)]
class ExchangeRatesHistory extends Command
{
    protected function configure(): void
    {
        $this->addOption('from', null, InputOption::VALUE_REQUIRED, 'First day (Y-m-d)', '2014-01-01');
        $this->addOption('to', null, InputOption::VALUE_REQUIRED, 'Last day (Y-m-d), today by default', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (Config::inst()->get(ExchangeRates::class, 'registered_processor') !== CNB::class) {
            $output->writeln('The history is available for the processor of the CNB only.');

            return Command::INVALID;
        }
        $from = date('Y-m-d', (int)strtotime((string)$input->getOption('from')));
        $to = (string)$input->getOption('to') !== ''
            ? date('Y-m-d', (int)strtotime((string)$input->getOption('to')))
            : date('Y-m-d');
        Environment::increaseTimeLimitTo();
        $processor = CNB::create();
        $stored = 0;
        for ($year = (int)substr($from, 0, 4); $year <= (int)substr($to, 0, 4); $year++) {
            $count = $processor->processYear($year, $from, $to);
            $output->writeln(sprintf('%d: %d rates stored', $year, $count));
            $stored += $count;
        }
        $output->writeln(sprintf('Stored %d rates.', $stored));

        return Command::SUCCESS;
    }
}
