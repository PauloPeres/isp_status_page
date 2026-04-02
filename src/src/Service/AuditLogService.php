<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * AuditLogService (TASK-AUTH-018)
 *
 * Centralised service for recording security-relevant events
 * in the security_audit_logs table.
 */
class AuditLogService
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * Log a security event.
     *
     * @param string $eventType The event type identifier.
     * @param int|null $userId The user ID associated with the event.
     * @param string $ipAddress The client IP address.
     * @param string|null $userAgent The client user agent string.
     * @param array|null $details Optional key-value details to store as JSON.
     * @return void
     */
    public function log(string $eventType, ?int $userId, string $ipAddress, ?string $userAgent = null, ?array $details = null): void
    {
        try {
            $table = $this->fetchTable('SecurityAuditLogs');
            $entry = $table->newEntity([
                'user_id' => $userId,
                'event_type' => $eventType,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'details' => $details ? json_encode($details) : null,
                'created' => DateTime::now()->format('Y-m-d H:i:s'),
            ]);
            $table->save($entry);
        } catch (\Exception $e) {
            // Never let audit logging break the main flow
            // Use LogTrait::log() via explicit level-based call
            \Cake\Log\Log::write('error', "Failed to write audit log ({$eventType}): {$e->getMessage()}");
        }
    }
}
