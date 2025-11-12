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
            
            if ($videoId) {
                self::handleLiveVideo($videoId);
            }
        }
    }

    /**
     * Stub method to handle when a live video is detected
     * 
     * This is the method that will be called with the video ID when
     * the /tenth Facebook page starts a live video.
     * 
     * @param string $videoId The Facebook video ID
     */
    public static function handleLiveVideo(string $videoId): void
    {
        // TODO: Implement actual handling logic
        // This is a stub method that receives the video ID
        // You can add your custom logic here to process the live video
        
        error_log("Facebook Live Video Detected - Video ID: {$videoId}");
        
        // Example: You could create a WordPress post, send notifications, etc.
        // For now, this is just a placeholder that logs the video ID
    }

    /**
     * Get the Facebook app verify token from configuration
     * 
     * @return string|null
     */
    private static function getVerifyToken(): ?string
    {
        return defined('FACEBOOK_WEBHOOK_VERIFY_TOKEN') ? FACEBOOK_WEBHOOK_VERIFY_TOKEN : null;
    }

    /**
     * Get the Facebook app secret from configuration
     * 
     * @return string|null
     */
    private static function getAppSecret(): ?string
    {
        return defined('FACEBOOK_APP_SECRET') ? FACEBOOK_APP_SECRET : null;
    }
}
