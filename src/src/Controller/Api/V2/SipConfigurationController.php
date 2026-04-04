<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Model\Entity\SipConfiguration;
use App\Service\Voice\VoiceCallService;
use Cake\Log\Log;

/**
 * SipConfigurationController
 *
 * Manage the organization's SIP trunk configuration for voice call alerts.
 * Each organization can have at most one SIP configuration.
 */
class SipConfigurationController extends AppController
{
    /**
     * GET /api/v2/sip-configuration
     *
     * Return the org's SIP config. If none exists, return defaults.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('SipConfigurations');
        $config = $table->find()
            ->where(['SipConfigurations.organization_id' => $this->currentOrgId])
            ->first();

        if ($config === null) {
            // Return defaults when no config exists
            $this->success([
                'sip_configuration' => [
                    'provider' => SipConfiguration::PROVIDER_KEEPUP_DEFAULT,
                    'sip_host' => null,
                    'sip_port' => 5060,
                    'sip_username' => null,
                    'sip_transport' => 'udp',
                    'caller_id' => null,
                    'twilio_trunk_sid' => null,
                    'active' => true,
                    'last_tested_at' => null,
                    'last_test_result' => null,
                ],
            ]);

            return;
        }

        $this->success(['sip_configuration' => $config]);
    }

    /**
     * PUT /api/v2/sip-configuration
     *
     * Create or update the org's SIP config.
     * If provider is 'keepup_default', clear custom SIP fields.
     *
     * @return void
     */
    public function save(): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('SipConfigurations');
        $config = $table->find()
            ->where(['SipConfigurations.organization_id' => $this->currentOrgId])
            ->first();

        $data = $this->request->getData();

        // If provider is keepup_default, clear custom SIP fields
        $provider = $data['provider'] ?? SipConfiguration::PROVIDER_KEEPUP_DEFAULT;
        if ($provider === SipConfiguration::PROVIDER_KEEPUP_DEFAULT) {
            $data['sip_host'] = null;
            $data['sip_port'] = null;
            $data['sip_username'] = null;
            $data['sip_password'] = null;
            $data['sip_transport'] = null;
            $data['caller_id'] = null;
            $data['twilio_trunk_sid'] = null;
        }

        if ($config === null) {
            // Create new
            $config = $table->newEntity($data);
            $config->set('organization_id', $this->currentOrgId);
        } else {
            // Don't overwrite password with masked value
            if (isset($data['sip_password']) && $data['sip_password'] === '••••••••') {
                unset($data['sip_password']);
            }
            $config = $table->patchEntity($config, $data);
        }

        if (!$table->save($config)) {
            $this->error('Validation failed', 422, $config->getErrors());

            return;
        }

        $this->success(['sip_configuration' => $config]);
    }

    /**
     * POST /api/v2/sip-configuration/test
     *
     * Test the configured SIP connection.
     *
     * @return void
     */
    public function test(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('SipConfigurations');
        $config = $table->find()
            ->where(['SipConfigurations.organization_id' => $this->currentOrgId])
            ->first();

        if ($config === null) {
            $this->error('No SIP configuration found. Save a configuration first.', 404);

            return;
        }

        if ($config->provider === SipConfiguration::PROVIDER_KEEPUP_DEFAULT) {
            // For default provider, just verify Twilio credentials are set
            $twilioSid = (string)env('TWILIO_SID', '');
            $twilioToken = (string)env('TWILIO_AUTH_TOKEN', '');

            if (empty($twilioSid) || empty($twilioToken)) {
                $result = [
                    'success' => false,
                    'message' => 'Twilio credentials not configured on the server. Contact support.',
                ];
            } else {
                $result = [
                    'success' => true,
                    'message' => 'KeepUp default voice provider (Twilio) is available.',
                ];
            }
        } else {
            // Test custom SIP / Twilio trunk connection
            try {
                $voiceService = new VoiceCallService();
                $provider = $voiceService->resolveProvider($this->currentOrgId);
                $result = $provider->testConnection();
            } catch (\Exception $e) {
                Log::error("SIP connection test failed for org {$this->currentOrgId}: {$e->getMessage()}");
                $result = [
                    'success' => false,
                    'message' => 'Connection test failed: ' . $e->getMessage(),
                ];
            }
        }

        // Update last test result
        $config->last_tested_at = new \Cake\I18n\DateTime();
        $config->last_test_result = $result['success'] ? 'success' : 'failed';
        $table->save($config);

        if ($result['success']) {
            $this->success(['message' => $result['message']]);
        } else {
            $this->error($result['message'], 422);
        }
    }
}
