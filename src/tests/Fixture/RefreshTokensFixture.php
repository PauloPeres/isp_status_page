<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RefreshTokensFixture
 *
 * Provides test data for the refresh_tokens table.
 * Most tests generate their own tokens, so we keep this minimal.
 */
class RefreshTokensFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [];

        parent::init();
    }
}
