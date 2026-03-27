<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * CheckRegions Seed
 *
 * Seeds initial check regions for multi-region monitoring.
 */
class CheckRegionsSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * @return void
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'US East (N. Virginia)',
                'code' => 'us-east-1',
                'endpoint_url' => null,
                'active' => true,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'EU West (Ireland)',
                'code' => 'eu-west-1',
                'endpoint_url' => null,
                'active' => true,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Asia Pacific (Singapore)',
                'code' => 'ap-southeast-1',
                'endpoint_url' => null,
                'active' => true,
                'created' => date('Y-m-d H:i:s'),
            ],
        ];

        $table = $this->table('check_regions');
        $table->insert($data)->save();
    }
}
