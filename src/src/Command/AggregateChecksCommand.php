<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\ChecksAggregationService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;

/**
 * Aggregate Checks Command
 *
 * Runs check data aggregation into rollup windows.
 * Intended for hourly cron execution.
 *
 * Usage: bin/cake aggregate_checks
 */
class AggregateChecksCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser
            ->setDescription('Aggregate raw monitor checks into rollup windows (5min, 1hour, 1day)')
            ->addOption('level', [
                'help' => 'Run only a specific aggregation level: 5min, 1hour, 1day, or all (default: all)',
                'default' => 'all',
                'choices' => ['all', '5min', '1hour', '1day'],
            ]);

        return $parser;
    }

    /**
     * Execute the aggregation command.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('<info>Starting check data aggregation...</info>');
        $io->hr();

        $service = new ChecksAggregationService();
        $level = (string)$args->getOption('level');

        try {
            if ($level === 'all') {
                $results = $service->runAll();
            } else {
                $method = match ($level) {
                    '5min' => 'aggregate5Min',
                    '1hour' => 'aggregate1Hour',
                    '1day' => 'aggregate1Day',
                };
                $results = [$level => $service->{$method}()];
            }

            foreach ($results as $periodType => $count) {
                $io->out("  {$periodType}: <info>{$count}</info> rollup rows processed");
            }

            $total = array_sum($results);
            $io->hr();
            $io->out("<success>Aggregation completed!</success> Total: {$total} rollup rows");

            Log::info("AggregateChecksCommand completed: " . json_encode($results));

            return static::CODE_SUCCESS;
        } catch (\Exception $e) {
            $io->error('Aggregation failed: ' . $e->getMessage());
            Log::error("AggregateChecksCommand failed: {$e->getMessage()}");

            return static::CODE_ERROR;
        }
    }
}
