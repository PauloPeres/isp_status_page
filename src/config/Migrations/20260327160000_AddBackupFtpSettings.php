<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddBackupFtpSettings Migration
 *
 * Adds FTP/SFTP backup settings to the settings table.
 */
class AddBackupFtpSettings extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $settings = [
            [
                'key' => 'backup_ftp_enabled',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Enable automatic FTP/SFTP backup upload',
                'modified' => $now,
            ],
            [
                'key' => 'backup_ftp_type',
                'value' => 'ftp',
                'type' => 'string',
                'description' => 'Backup upload protocol: ftp or sftp',
                'modified' => $now,
            ],
            [
                'key' => 'backup_ftp_host',
                'value' => '',
                'type' => 'string',
                'description' => 'FTP/SFTP server hostname',
                'modified' => $now,
            ],
            [
                'key' => 'backup_ftp_port',
                'value' => '21',
                'type' => 'integer',
                'description' => 'FTP/SFTP server port',
                'modified' => $now,
            ],
            [
                'key' => 'backup_ftp_username',
                'value' => '',
                'type' => 'string',
                'description' => 'FTP/SFTP username',
                'modified' => $now,
            ],
            [
                'key' => 'backup_ftp_password',
                'value' => '',
                'type' => 'string',
                'description' => 'FTP/SFTP password',
                'modified' => $now,
            ],
            [
                'key' => 'backup_ftp_path',
                'value' => '/backups',
                'type' => 'string',
                'description' => 'Remote directory path for backups',
                'modified' => $now,
            ],
            [
                'key' => 'backup_ftp_passive',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Use passive mode for FTP connections',
                'modified' => $now,
            ],
        ];

        $table = $this->table('settings');
        foreach ($settings as $setting) {
            // Only insert if key does not exist
            $exists = $this->fetchRow(
                "SELECT id FROM settings WHERE `key` = '{$setting['key']}'"
            );
            if (!$exists) {
                $table->insert($setting);
            }
        }
        $table->saveData();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $keys = [
            'backup_ftp_enabled',
            'backup_ftp_type',
            'backup_ftp_host',
            'backup_ftp_port',
            'backup_ftp_username',
            'backup_ftp_password',
            'backup_ftp_path',
            'backup_ftp_passive',
        ];

        foreach ($keys as $key) {
            $this->execute("DELETE FROM settings WHERE `key` = '{$key}'");
        }
    }
}
