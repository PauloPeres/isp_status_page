<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateVoiceCallLogs Migration
 *
 * Creates the voice_call_logs table for tracking voice call alerts
 * initiated by the VoiceCallAlertChannel.
 */
class CreateVoiceCallLogs extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('voice_call_logs');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('incident_id', 'integer', [
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('monitor_id', 'integer', [
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('notification_channel_id', 'integer', [
                'null' => true,
                'default' => null,
                'signed' => true,
            ])
            ->addColumn('phone_number', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'E.164 format phone number',
            ])
            ->addColumn('call_sid', 'string', [
                'limit' => 64,
                'null' => true,
                'default' => null,
                'comment' => 'Twilio Call SID or provider reference',
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'initiated',
                'comment' => 'initiated/ringing/answered/completed/no-answer/busy/failed/canceled',
            ])
            ->addColumn('dtmf_input', 'string', [
                'limit' => 5,
                'null' => true,
                'default' => null,
                'comment' => 'DTMF key pressed (1=ack, 2=escalate)',
            ])
            ->addColumn('duration_seconds', 'integer', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('tts_language', 'string', [
                'limit' => 10,
                'null' => false,
                'default' => 'en',
            ])
            ->addColumn('tts_message', 'text', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('cost_credits', 'integer', [
                'null' => false,
                'default' => 0,
            ])
            ->addColumn('sip_provider', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'keepup',
            ])
            ->addColumn('escalation_position', 'integer', [
                'null' => false,
                'default' => 0,
            ])
            ->addColumn('public_id', 'string', [
                'limit' => 36,
                'null' => false,
                'comment' => 'UUID for webhook URLs',
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addIndex(['organization_id', 'created'], [
                'name' => 'idx_voice_call_logs_org_created',
                'order' => ['created' => 'DESC'],
            ])
            ->addIndex(['incident_id'], [
                'name' => 'idx_voice_call_logs_incident',
            ])
            ->addIndex(['call_sid'], [
                'unique' => true,
                'name' => 'idx_voice_call_logs_call_sid',
            ])
            ->addIndex(['public_id'], [
                'unique' => true,
                'name' => 'idx_voice_call_logs_public_id',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_voice_call_logs_organization',
            ])
            ->addForeignKey('incident_id', 'incidents', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_voice_call_logs_incident',
            ])
            ->addForeignKey('monitor_id', 'monitors', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_voice_call_logs_monitor',
            ])
            ->addForeignKey('notification_channel_id', 'notification_channels', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_voice_call_logs_channel',
            ])
            ->create();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('voice_call_logs')->drop()->save();
    }
}
