<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\I18n\DateTime;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * ApiKeysFixture
 */
class ApiKeysFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $yesterday = new DateTime('-1 day');
        $nextMonth = new DateTime('+1 month');
        $lastWeek = new DateTime('-1 week');

        // The key_hash values are bcrypt hashes of test keys.
        // These are for testing only; the actual key prefix and hash must correspond.
        $this->records = [
            [
                'id' => 1,
                'public_id' => 'd0e1f2a3-b4c5-4d6e-7f80-910213243546',
                'organization_id' => 1,
                'user_id' => 1,
                'name' => 'Production API Key',
                'key_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // placeholder
                'key_prefix' => 'sk_live_abc',
                'permissions' => '["read","write"]',
                'rate_limit' => 1000,
                'last_used_at' => $yesterday->format('Y-m-d H:i:s'),
                'expires_at' => $nextMonth->format('Y-m-d H:i:s'),
                'active' => true,
                'created' => $lastWeek->format('Y-m-d H:i:s'),
                'modified' => $yesterday->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'public_id' => 'e1f2a3b4-c5d6-4e7f-8091-021324354657',
                'organization_id' => 1,
                'user_id' => 1,
                'name' => 'Read Only Key',
                'key_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // placeholder
                'key_prefix' => 'sk_live_def',
                'permissions' => '["read"]',
                'rate_limit' => 500,
                'last_used_at' => null,
                'expires_at' => null,
                'active' => true,
                'created' => $lastWeek->format('Y-m-d H:i:s'),
                'modified' => $lastWeek->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 3,
                'public_id' => 'f2a3b4c5-d6e7-4f80-9102-132435465768',
                'organization_id' => 1,
                'user_id' => 1,
                'name' => 'Expired Key',
                'key_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // placeholder
                'key_prefix' => 'sk_live_ghi',
                'permissions' => '["read"]',
                'rate_limit' => 1000,
                'last_used_at' => null,
                'expires_at' => $lastWeek->format('Y-m-d H:i:s'), // expired
                'active' => true,
                'created' => (new DateTime('-2 weeks'))->format('Y-m-d H:i:s'),
                'modified' => (new DateTime('-2 weeks'))->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 4,
                'public_id' => 'a3b4c5d6-e7f8-4091-0213-243546576879',
                'organization_id' => 1,
                'user_id' => 1,
                'name' => 'Revoked Key',
                'key_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // placeholder
                'key_prefix' => 'sk_live_jkl',
                'permissions' => '["read","write","admin"]',
                'rate_limit' => 1000,
                'last_used_at' => $lastWeek->format('Y-m-d H:i:s'),
                'expires_at' => null,
                'active' => false, // revoked
                'created' => (new DateTime('-2 weeks'))->format('Y-m-d H:i:s'),
                'modified' => $lastWeek->format('Y-m-d H:i:s'),
            ],
        ];

        parent::init();
    }
}
