<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\Telegram\TelegramBotService;
use Cake\Log\Log;

/**
 * TelegramWebhookController (C-04)
 *
 * Receives incoming Telegram Bot API webhook updates
 * and dispatches them to the TelegramBotService.
 *
 * Webhook URL: POST /api/v2/telegram/webhook/{org_id}/{token}
 *
 * The {token} is a secret shared between the app and Telegram
 * to prevent unauthorized webhook calls.
 */
class TelegramWebhookController extends AppController
{
    /**
     * Skip JWT authentication for webhook endpoint.
     *
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        // Do NOT call parent — webhook is authenticated via URL token, not JWT
        $this->response = $this->response->withType('application/json');
    }

    /**
     * POST /api/v2/telegram/webhook/{org_id}/{token}
     *
     * @param string $orgId Organization ID
     * @param string $token Webhook verification token
     * @return void
     */
    public function webhook(string $orgId, string $token): void
    {
        $this->request->allowMethod(['post']);

        // Verify the webhook token against organization settings
        $settingsTable = $this->fetchTable('Settings');
        $storedToken = null;
        $botToken = null;

        try {
            $tokenSetting = $settingsTable->find()
                ->where([
                    'organization_id' => (int)$orgId,
                    'key' => 'telegram_webhook_token',
                ])
                ->first();

            $botSetting = $settingsTable->find()
                ->where([
                    'organization_id' => (int)$orgId,
                    'key' => 'telegram_bot_token',
                ])
                ->first();

            $storedToken = $tokenSetting->value ?? null;
            $botToken = $botSetting->value ?? null;
        } catch (\Exception $e) {
            Log::error("Telegram webhook: Failed to read settings for org {$orgId}");
        }

        if (empty($storedToken) || $token !== $storedToken) {
            $this->response = $this->response->withStatus(403);
            $this->set('data', ['error' => 'Invalid webhook token']);
            $this->viewBuilder()->setOption('serialize', 'data');
            return;
        }

        if (empty($botToken)) {
            $this->response = $this->response->withStatus(500);
            $this->set('data', ['error' => 'Bot token not configured']);
            $this->viewBuilder()->setOption('serialize', 'data');
            return;
        }

        // Parse the update payload
        $update = $this->request->getData();
        if (empty($update)) {
            $body = (string)$this->request->getBody();
            $update = json_decode($body, true);
        }

        if (empty($update)) {
            $this->response = $this->response->withStatus(400);
            $this->set('data', ['error' => 'Empty update payload']);
            $this->viewBuilder()->setOption('serialize', 'data');
            return;
        }

        // Process the update
        try {
            $service = new TelegramBotService($botToken);
            $service->processUpdate($update, (int)$orgId);
        } catch (\Exception $e) {
            Log::error("Telegram webhook processing error: {$e->getMessage()}");
        }

        // Always return 200 to Telegram (even on errors, to prevent retries)
        $this->set('data', ['ok' => true]);
        $this->viewBuilder()->setOption('serialize', 'data');
    }
}
