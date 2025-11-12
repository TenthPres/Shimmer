<?php

namespace tp\Shimmer;

/**
 * FacebookWebhook handles Facebook webhook integration for live video notifications
 * 
 * This class:
 * 1. Verifies webhook subscriptions from Facebook
 * 2. Receives and processes webhook notifications when /tenth page goes live
 * 3. Extracts video ID and passes to handler method
 */
class FacebookWebhook {

    /**
     * Register hooks and initialize the webhook handler
     */
    public static function load(): void
    {
        add_action('rest_api_init', [self::class, 'registerWebhookEndpoint']);
        add_action('admin_init', [self::class, 'registerSettings'], 5);
    }

    /**
     * Register settings with ShimmerSettings
     */
    public static function registerSettings(): void
    {
        // Register Facebook webhook section
        ShimmerSettings::registerSection(
            'shimmer_facebook_webhook_section',
            __('Facebook Webhook', 'shimmer'),
            [self::class, 'renderSettingsSection']
        );

        // Register Facebook App Secret field
        ShimmerSettings::registerField(
            'shimmer_facebook_app_secret',
            'shimmer_facebook_webhook_section',
            __('Facebook App Secret', 'shimmer'),
            'password',
            [
                'description' => __('Enter your Facebook App Secret (found in Facebook App Settings > Basic)', 'shimmer'),
                'default' => '',
            ]
        );

        // Register Webhook Verify Token field
        ShimmerSettings::registerField(
            'shimmer_facebook_verify_token',
            'shimmer_facebook_webhook_section',
            __('Webhook Verify Token', 'shimmer'),
            'text',
            [
                'description' => __('Enter a custom verify token (you create this, and use the same value when configuring the webhook in Facebook)', 'shimmer'),
                'default' => '',
            ]
        );
    }

    /**
     * Render settings section description
     */
    public static function renderSettingsSection(): void
    {
        $webhookUrl = rest_url('shimmer/v1/facebook-webhook');
        ?>
        <p><?php esc_html_e('Configure your Facebook App credentials for webhook integration. These settings are required to receive live video notifications from the /tenth Facebook page.', 'shimmer'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Webhook URL', 'shimmer'); ?></th>
                <td>
                    <code><?php echo esc_html($webhookUrl); ?></code>
                    <p class="description"><?php esc_html_e('Use this URL when configuring your Facebook webhook', 'shimmer'); ?></p>
                </td>
            </tr>
        </table>

        <h3><?php esc_html_e('Setup Instructions', 'shimmer'); ?></h3>
        <ol>
            <li><?php esc_html_e('Create a Facebook App at developers.facebook.com', 'shimmer'); ?></li>
            <li><?php esc_html_e('In your Facebook App, go to Settings > Basic and copy your App Secret', 'shimmer'); ?></li>
            <li><?php esc_html_e('Paste the App Secret in the field below', 'shimmer'); ?></li>
            <li><?php esc_html_e('Create a custom Verify Token (any random string) and enter it below', 'shimmer'); ?></li>
            <li><?php esc_html_e('Save these settings', 'shimmer'); ?></li>
            <li><?php esc_html_e('In your Facebook App, add the "Webhooks" product', 'shimmer'); ?></li>
            <li><?php esc_html_e('Click "Add Callback URL" and enter the Webhook URL shown above', 'shimmer'); ?></li>
            <li><?php esc_html_e('Enter the same Verify Token you created in step 4', 'shimmer'); ?></li>
            <li><?php esc_html_e('Click "Verify and Save"', 'shimmer'); ?></li>
            <li><?php echo wp_kses_post(sprintf(__('Subscribe to the %s field for your page', 'shimmer'), '<strong>live_videos</strong>')); ?></li>
        </ol>
        <?php
    }

    /**
     * Register the REST API endpoint for Facebook webhooks
     */
    public static function registerWebhookEndpoint(): void
    {
        register_rest_route('shimmer/v1', '/facebook-webhook', [
            [
                'methods'  => 'GET',
                'callback' => [self::class, 'handleWebhookVerification'],
                'permission_callback' => '__return_true', // Facebook needs to access this
            ],
            [
                'methods'  => 'POST',
                'callback' => [self::class, 'handleWebhookNotification'],
                'permission_callback' => '__return_true', // Facebook needs to access this
            ],
        ]);
    }

    /**
     * Handle webhook verification requests from Facebook (GET)
     * 
     * Facebook sends a GET request with hub.mode, hub.verify_token, and hub.challenge
     * to verify the webhook endpoint during subscription setup.
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function handleWebhookVerification(\WP_REST_Request $request): \WP_REST_Response
    {
        $mode = $request->get_param('hub_mode');
        $token = $request->get_param('hub_verify_token');
        $challenge = $request->get_param('hub_challenge');

        // Verify that the mode and token match
        if ($mode === 'subscribe' && $token === self::getVerifyToken()) {
            // Respond with the challenge token to complete verification
            return new \WP_REST_Response($challenge, 200, ['Content-Type' => 'text/plain']);
        }

        return new \WP_REST_Response('Forbidden', 403);
    }

    /**
     * Handle incoming webhook notifications from Facebook (POST)
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function handleWebhookNotification(\WP_REST_Request $request): \WP_REST_Response
    {
        // Verify the request signature
        if (!self::verifySignature($request)) {
            return new \WP_REST_Response('Forbidden', 403);
        }

        $body = $request->get_json_params();

        // Process each entry in the webhook payload
        if (isset($body['entry']) && is_array($body['entry'])) {
            foreach ($body['entry'] as $entry) {
                self::processEntry($entry);
            }
        }

        // Always return 200 OK to acknowledge receipt
        return new \WP_REST_Response('EVENT_RECEIVED', 200);
    }

    /**
     * Verify the signature of the webhook request
     * 
     * @param \WP_REST_Request $request
     * @return bool
     */
    private static function verifySignature(\WP_REST_Request $request): bool
    {
        $signature = $request->get_header('X-Hub-Signature-256');
        
        if (!$signature) {
            // Fallback to older signature header
            $signature = $request->get_header('X-Hub-Signature');
            if (!$signature) {
                return false;
            }
            $hashAlgorithm = 'sha1';
        } else {
            $hashAlgorithm = 'sha256';
        }

        $appSecret = self::getAppSecret();
        if (!$appSecret) {
            error_log('Facebook webhook: App secret not configured');
            return false;
        }

        $body = $request->get_body();
        $expectedSignature = $hashAlgorithm . '=' . hash_hmac($hashAlgorithm, $body, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process a webhook entry to detect live video events
     * 
     * @param array $entry
     */
    private static function processEntry(array $entry): void
    {
        if (!isset($entry['changes']) || !is_array($entry['changes'])) {
            return;
        }

        foreach ($entry['changes'] as $change) {
            if (isset($change['field']) && $change['field'] === 'live_videos') {
                self::processLiveVideoChange($change);
            }
        }
    }

    /**
     * Process a live video change notification
     * 
     * @param array $change
     */
    private static function processLiveVideoChange(array $change): void
    {
        if (!isset($change['value'])) {
            return;
        }

        $value = $change['value'];

        // Check if this is a live video going live
        if (isset($value['status']) && $value['status'] === 'live') {
            $videoId = $value['id'] ?? null;
            $videoTitle = $value['title'] ?? $value['description'] ?? '';
            
            if ($videoId) {
                self::handleLiveVideo($videoId, $videoTitle);
            }
        }
    }

    /**
     * Stub method to handle when a live video is detected
     * 
     * This is the method that will be called with the video ID and title when
     * the /tenth Facebook page starts a live video.
     * 
     * @param string $videoId The Facebook video ID
     * @param string $videoTitle The video title/name (may be empty if not provided)
     */
    public static function handleLiveVideo(string $videoId, string $videoTitle = ''): void
    {
        // TODO: Implement actual handling logic
        // This is a stub method that receives the video ID and title
        // You can add your custom logic here to process the live video
        
        $logMessage = "Facebook Live Video Detected - Video ID: {$videoId}";
        if (!empty($videoTitle)) {
            $logMessage .= ", Title: {$videoTitle}";
        }
        error_log($logMessage);
        
        // Example: You could create a WordPress post, send notifications, etc.
        // For now, this is just a placeholder that logs the video ID and title
    }

    /**
     * Get the Facebook app verify token from WordPress settings
     * Falls back to constant if setting not found (for backward compatibility)
     * 
     * @return string|null
     */
    private static function getVerifyToken(): ?string
    {
        $token = get_option('shimmer_facebook_verify_token');
        if (empty($token) && defined('FACEBOOK_WEBHOOK_VERIFY_TOKEN')) {
            $token = FACEBOOK_WEBHOOK_VERIFY_TOKEN;
        }
        return !empty($token) ? $token : null;
    }

    /**
     * Get the Facebook app secret from WordPress settings
     * Falls back to constant if setting not found (for backward compatibility)
     * 
     * @return string|null
     */
    private static function getAppSecret(): ?string
    {
        $secret = get_option('shimmer_facebook_app_secret');
        if (empty($secret) && defined('FACEBOOK_APP_SECRET')) {
            $secret = FACEBOOK_APP_SECRET;
        }
        return !empty($secret) ? $secret : null;
    }
}
