<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddAiChatToProAndBusinessPlans Migration
 *
 * Adds the 'ai_chat' feature to Pro and Business plan features.
 */
class AddAiChatToProAndBusinessPlans extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // Add ai_chat feature to Pro and Business plans
        $plans = $this->fetchAll("SELECT id, slug, features FROM plans WHERE slug IN ('pro', 'business')");

        foreach ($plans as $plan) {
            $features = json_decode($plan['features'] ?? '[]', true);
            if (!is_array($features)) {
                $features = [];
            }

            // Check if already has ai_chat
            if (array_is_list($features)) {
                if (!in_array('ai_chat', $features, true)) {
                    $features[] = 'ai_chat';
                }
            } else {
                if (empty($features['ai_chat'])) {
                    $features['ai_chat'] = true;
                }
            }

            $encoded = json_encode($features);
            $this->execute(
                "UPDATE plans SET features = '{$encoded}' WHERE id = {$plan['id']}"
            );
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove ai_chat feature from Pro and Business plans
        $plans = $this->fetchAll("SELECT id, slug, features FROM plans WHERE slug IN ('pro', 'business')");

        foreach ($plans as $plan) {
            $features = json_decode($plan['features'] ?? '[]', true);
            if (!is_array($features)) {
                continue;
            }

            if (array_is_list($features)) {
                $features = array_values(array_filter($features, fn($f) => $f !== 'ai_chat'));
            } else {
                unset($features['ai_chat']);
            }

            $encoded = json_encode($features);
            $this->execute(
                "UPDATE plans SET features = '{$encoded}' WHERE id = {$plan['id']}"
            );
        }
    }
}
