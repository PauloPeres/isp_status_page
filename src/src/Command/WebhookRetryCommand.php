<?php
declare(strict_types=1);

namespace App\Command;

use App\Job\WebhookDeliveryJob;
use App\Service\WebhookDeliveryService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\QueueManager;

/**
 * WebhookRetryCommand (C-04)
 *
 * @deprecated Since Phase 4. Webhook retries are now handled automatically by
 *             WebhookDeliveryJob via the Redis-backed queue workers. This command
 *             is kept for backward compatibility and as a manual fallback.
 *             In queue mode it simply pushes WebhookDeliveryJob instances; when no
 *             queue is configured it falls back to synchronous delivery.
 *
 * Processes failed webhook deliveries that are eligible for retry.
 *
 * Usage: bin/cake webhook_retry
 */
class WebhookRetryCommand extends Command
{
    use LocatorAwareTrait;

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser
            ->setDescription('Retry failed webhook deliveries with exponential backoff')
            ->addOption('limit', [
                'short' => 'l',
                'help' => 'Maximum number of deliveries to process per run',
                'default' => '50',
            ])
            ->addOption('dry-run', [
                'help' => 'Show pending retries without actually delivering',
                'boolean' => true,
            ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $limit = (int)$args->getOption('limit');
        $dryRun = (bool)$args->getOption('dry-run');

        $deliveriesTable = $this->fetchTable('WebhookDeliveries');
        $service = new WebhookDeliveryService();

        // Find pending retries
        $pending = $deliveriesTable->find('pendingRetry')
            ->contain(['WebhookEndpoints'])
            ->limit($limit)
            ->orderBy(['WebhookDeliveries.next_retry_at' => 'ASC'])
            ->all();

        $count = $pending->count();

        if ($count === 0) {
            $io->verbose('No pending webhook retries.');
            return self::CODE_SUCCESS;
        }

        $io->out("Found {$count} pending webhook deliveries to retry.");

        if ($dryRun) {
            foreach ($pending as $delivery) {
                $endpoint = $delivery->webhook_endpoint;
                $io->out(sprintf(
                    '  [%d] %s → %s (attempt %d/%d)',
                    $delivery->id,
                    $delivery->event_type,
                    $endpoint->url ?? '(unknown)',
                    $delivery->attempts,
                    WebhookDeliveryService::MAX_ATTEMPTS
                ));
            }
            $io->out('Dry run — no deliveries attempted.');
            return self::CODE_SUCCESS;
        }

        $success = 0;
        $failed = 0;
        $exhausted = 0;
        $useQueue = !empty(Configure::read('Queue.default'));

        if ($useQueue) {
            $io->verbose('Queue mode: pushing WebhookDeliveryJobs to default queue');
        }

        foreach ($pending as $delivery) {
            try {
                // When queue is available, push a WebhookDeliveryJob instead of retrying inline
                if ($useQueue) {
                    try {
                        QueueManager::push(WebhookDeliveryJob::class, [
                            'data' => [
                                'delivery_id' => $delivery->id,
                                'attempt' => $delivery->attempts + 1,
                            ],
                        ], ['config' => 'default']);

                        $success++;
                        $io->verbose("  → Queued WebhookDeliveryJob for #{$delivery->id} ({$delivery->event_type})");
                        Log::debug("WebhookRetry: Queued WebhookDeliveryJob for delivery #{$delivery->id}");

                        continue;
                    } catch (\Exception $e) {
                        Log::warning("WebhookRetry: Failed to queue WebhookDeliveryJob for #{$delivery->id}, falling back to sync: {$e->getMessage()}");
                    }
                }

                // Synchronous fallback
                $result = $service->deliver($delivery->id);

                if ($result) {
                    $success++;
                    $io->verbose("  ✓ Delivered #{$delivery->id} ({$delivery->event_type})");
                } else {
                    // Reload to check if exhausted
                    $updated = $deliveriesTable->get($delivery->id);
                    if ($updated->isExhausted()) {
                        $exhausted++;
                        $io->out("  ✗ Exhausted #{$delivery->id} ({$delivery->event_type}) after {$updated->attempts} attempts");
                    } else {
                        $failed++;
                        $io->verbose("  ↻ Retry scheduled #{$delivery->id} (attempt {$updated->attempts})");
                    }
                }
            } catch (\Exception $e) {
                $failed++;
                $io->error("  Error processing #{$delivery->id}: {$e->getMessage()}");
            }
        }

        $io->out('');
        $io->out("Results: {$success} delivered/queued, {$failed} rescheduled, {$exhausted} exhausted");

        return self::CODE_SUCCESS;
    }
}
