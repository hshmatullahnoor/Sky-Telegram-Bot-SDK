<?php

namespace Classes\Commands\Helper;

use Telegram\Bot\Api;

class WebhookCommand
{
    private Api $telegram;
    private array $config;

    public function __construct()
    {
        $this->config = require config_path('bot.php');

        $this->telegram = new Api($this->config['token']);

        if (!empty($this->config['api_url']) && $this->config['api_url'] !== 'https://api.telegram.org') {
            $this->telegram->setBaseBotUrl($this->config['api_url']);
        }

        $timeout = (int) ($this->config['http_timeout'] ?? 30);
        $connectTimeout = (int) ($this->config['connect_timeout'] ?? 10);
        $this->telegram->setTimeOut($timeout);
        $this->telegram->setConnectTimeOut($connectTimeout);
    }

    /**
     * Set the webhook URL.
     */
    public function set(): void
    {
        $url    = $this->config['webhook']['url'] ?? '';
        $secret = $this->config['webhook']['secret'] ?? '';

        if (empty($url)) {
            echo "  ✗ TELEGRAM_DOMAIN is not set in .env" . PHP_EOL;
            return;
        }

        if (empty($this->config['token'])) {
            echo "  ✗ TELEGRAM_BOT_TOKEN is not set in .env" . PHP_EOL;
            return;
        }

        if (empty($secret)) {
            $secret = bin2hex(random_bytes(32));
            $this->saveSecretToEnv($secret);
            echo "  ✓ Generated new secret token and saved to .env" . PHP_EOL;
        }

        try {
            $response = $this->telegram->setWebhook([
                'url'          => $url,
                'secret_token' => $secret,
            ]);

            if ($response) {
                echo "  ✓ Set Webhook: Success" . PHP_EOL;
                echo "    URL: {$url}" . PHP_EOL;
            } else {
                echo "  ✗ Set Webhook: Failed" . PHP_EOL;
            }
        } catch (\Throwable $e) {
            echo "  ✗ Set Webhook: " . $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * Delete the webhook.
     */
    public function delete(): void
    {
        try {
            $response = $this->telegram->deleteWebhook();

            if ($response) {
                echo "  ✓ Delete Webhook: Success" . PHP_EOL;
            } else {
                echo "  ✗ Delete Webhook: Failed" . PHP_EOL;
            }
        } catch (\Throwable $e) {
            echo "  ✗ Delete Webhook: " . $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * Get webhook info.
     */
    public function info(): void
    {
        try {
            $result = $this->telegram->getWebhookInfo();

            echo "Webhook Info:" . PHP_EOL;

            $fields = [
                'url'                    => 'URL',
                'has_custom_certificate' => 'Custom Certificate',
                'pending_update_count'   => 'Pending Updates',
                'ip_address'             => 'IP Address',
                'last_error_date'        => 'Last Error Date',
                'last_error_message'     => 'Last Error Message',
                'max_connections'        => 'Max Connections',
                'allowed_updates'        => 'Allowed Updates',
            ];

            foreach ($fields as $key => $label) {
                $value = $result->$key ?? null;

                if ($value !== null) {
                    if ($key === 'last_error_date') {
                        $value = date('Y-m-d H:i:s', $value);
                    }
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                    if (is_bool($value)) {
                        $value = $value ? 'Yes' : 'No';
                    }

                    echo "  {$label}: {$value}" . PHP_EOL;
                }
            }
        } catch (\Throwable $e) {
            echo "  ✗ Failed to get webhook info: " . $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * Delete pending updates by deleting webhook with drop_pending_updates.
     */
    public function dropPending(): void
    {
        try {
            $response = $this->telegram->deleteWebhook([
                'drop_pending_updates' => true,
            ]);

            if ($response) {
                echo "  ✓ Drop Pending Updates: Success" . PHP_EOL;
            } else {
                echo "  ✗ Drop Pending Updates: Failed" . PHP_EOL;
            }
        } catch (\Throwable $e) {
            echo "  ✗ Drop Pending Updates: " . $e->getMessage() . PHP_EOL;
        }
    }

    // ── Private Helpers ────────────────────────────────────────────

    private function saveSecretToEnv(string $token): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        if (preg_match('/^TELEGRAM_WEBHOOK_SECRET=.*$/m', $content)) {
            $content = preg_replace('/^TELEGRAM_WEBHOOK_SECRET=.*$/m', "TELEGRAM_WEBHOOK_SECRET={$token}", $content);
        } else {
            $content .= PHP_EOL . "TELEGRAM_WEBHOOK_SECRET={$token}" . PHP_EOL;
        }

        file_put_contents($envPath, $content);
    }
}
