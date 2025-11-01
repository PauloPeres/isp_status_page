<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\I18n\DateTime;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * AlertLogsFixture
 */
class AlertLogsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $now = new DateTime('now');
        $oneHourAgo = new DateTime('-1 hour');
        $twoHoursAgo = new DateTime('-2 hours');
        $threeHoursAgo = new DateTime('-3 hours');

        $this->records = [
            [
                'id' => 1,
                'alert_rule_id' => 1,
                'incident_id' => 1,
                'monitor_id' => 1,
                'channel' => 'email',
                'recipient' => 'admin@example.com',
                'status' => 'sent',
                'sent_at' => $threeHoursAgo->format('Y-m-d H:i:s'),
                'error_message' => null,
                'created' => $threeHoursAgo->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'alert_rule_id' => 1,
                'incident_id' => 1,
                'monitor_id' => 1,
                'channel' => 'email',
                'recipient' => 'ops@example.com',
                'status' => 'sent',
                'sent_at' => $threeHoursAgo->format('Y-m-d H:i:s'),
                'error_message' => null,
                'created' => $threeHoursAgo->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 3,
                'alert_rule_id' => 2,
                'incident_id' => 1,
                'monitor_id' => 1,
                'channel' => 'email',
                'recipient' => 'admin@example.com',
                'status' => 'failed',
                'sent_at' => null,
                'error_message' => 'SMTP connection failed',
                'created' => $twoHoursAgo->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 4,
                'alert_rule_id' => 3,
                'incident_id' => null,
                'monitor_id' => 2,
                'channel' => 'email',
                'recipient' => 'api-team@example.com',
                'status' => 'queued',
                'sent_at' => null,
                'error_message' => null,
                'created' => $oneHourAgo->format('Y-m-d H:i:s'),
            ],
        ];

        parent::init();
    }
}
