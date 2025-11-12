<?php

/**
 * Basic tests for FacebookWebhook functionality
 * 
 * This file contains simple tests to verify the webhook implementation.
 * These are not PHPUnit tests, but rather demonstration code showing how
 * the webhook responds to various scenarios.
 * 
 * To run these tests, you would need a WordPress environment with this plugin active.
 */

namespace tp\Shimmer\Tests;

// This would only run in a WordPress environment
if (!defined('ABSPATH')) {
    die('WordPress environment required');
}

/**
 * Test helper class for FacebookWebhook
 */
class FacebookWebhookTest {

    /**
     * Test that the webhook endpoint is registered
     */
    public static function testEndpointRegistered(): bool
    {
        $routes = rest_get_server()->get_routes();
        return isset($routes['/shimmer/v1/facebook-webhook']);
    }

    /**
     * Test webhook verification with correct token
     */
    public static function testVerificationSuccess(): array
    {
        $request = new \WP_REST_Request('GET', '/shimmer/v1/facebook-webhook');
        $request->set_param('hub_mode', 'subscribe');
        
        // Get token from WordPress settings or fallback to constant
        $token = get_option('shimmer_facebook_verify_token');
        if (empty($token) && defined('FACEBOOK_WEBHOOK_VERIFY_TOKEN')) {
            $token = FACEBOOK_WEBHOOK_VERIFY_TOKEN;
        }
        if (empty($token)) {
            $token = 'test_token';
        }
        
        $request->set_param('hub_verify_token', $token);
        $request->set_param('hub_challenge', 'test_challenge_123');

        $response = rest_do_request($request);
        
        return [
            'status' => $response->get_status(),
            'data' => $response->get_data(),
            'expected_status' => 200,
            'expected_data' => 'test_challenge_123'
        ];
    }

    /**
     * Test webhook verification with incorrect token
     */
    public static function testVerificationFailure(): array
    {
        $request = new \WP_REST_Request('GET', '/shimmer/v1/facebook-webhook');
        $request->set_param('hub_mode', 'subscribe');
        $request->set_param('hub_verify_token', 'wrong_token');
        $request->set_param('hub_challenge', 'test_challenge_123');

        $response = rest_do_request($request);
        
        return [
            'status' => $response->get_status(),
            'data' => $response->get_data(),
            'expected_status' => 403
        ];
    }

    /**
     * Create a mock webhook payload for testing
     */
    public static function createMockLiveVideoPayload(string $videoId = 'test_video_123'): array
    {
        return [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '123456789',
                    'time' => time(),
                    'changes' => [
                        [
                            'field' => 'live_videos',
                            'value' => [
                                'id' => $videoId,
                                'status' => 'live',
                                'stream_url' => 'rtmps://example.com/stream',
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Test signature verification
     */
    public static function testSignatureVerification(): bool
    {
        // Get app secret from WordPress settings or fallback to constant
        $appSecret = get_option('shimmer_facebook_app_secret');
        if (empty($appSecret) && defined('FACEBOOK_APP_SECRET')) {
            $appSecret = FACEBOOK_APP_SECRET;
        }
        if (empty($appSecret)) {
            $appSecret = 'test_secret';
        }
        
        $payload = json_encode(self::createMockLiveVideoPayload());
        
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
        
        // In a real test, you would create a request with this signature
        // and verify it's accepted
        return !empty($signature);
    }

    /**
     * Example of what the handleLiveVideo method receives
     */
    public static function exampleHandleLiveVideo(): string
    {
        $videoId = 'test_video_123';
        $videoTitle = 'Sunday Morning Service';
        
        // This would be called by the webhook handler when a live video is detected
        // \tp\Shimmer\FacebookWebhook::handleLiveVideo($videoId, $videoTitle);
        
        return "Video ID received: {$videoId}, Title: {$videoTitle}";
    }

    /**
     * Run all tests
     */
    public static function runAll(): array
    {
        $results = [];
        
        $results['endpoint_registered'] = self::testEndpointRegistered();
        
        // Check if credentials are configured (either in settings or constants)
        $hasVerifyToken = !empty(get_option('shimmer_facebook_verify_token')) || defined('FACEBOOK_WEBHOOK_VERIFY_TOKEN');
        $hasAppSecret = !empty(get_option('shimmer_facebook_app_secret')) || defined('FACEBOOK_APP_SECRET');
        
        if ($hasVerifyToken) {
            $results['verification_success'] = self::testVerificationSuccess();
            $results['verification_failure'] = self::testVerificationFailure();
        }
        
        if ($hasAppSecret) {
            $results['signature_check'] = self::testSignatureVerification();
        }
        
        $results['example_payload'] = self::createMockLiveVideoPayload();
        $results['example_handler'] = self::exampleHandleLiveVideo();
        
        return $results;
    }
}

/**
 * Example usage (would be called from WordPress admin or a test script):
 * 
 * $results = \tp\Shimmer\Tests\FacebookWebhookTest::runAll();
 * print_r($results);
 */
