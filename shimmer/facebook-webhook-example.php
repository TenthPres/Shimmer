<?php

/**
 * Example script showing Facebook webhook flow
 * 
 * This demonstrates how the webhook integration works when Facebook
 * sends notifications about live videos on the /tenth page.
 */

// This is for documentation purposes only - not meant to be executed directly

echo "=== Facebook Webhook Integration Example ===\n\n";

echo "1. WEBHOOK VERIFICATION (Facebook setup)\n";
echo "   When you set up the webhook in Facebook App:\n";
echo "   - Facebook sends: GET /wp-json/shimmer/v1/facebook-webhook\n";
echo "   - Parameters: hub.mode=subscribe, hub.verify_token=YOUR_TOKEN, hub.challenge=RANDOM_STRING\n";
echo "   - Plugin verifies token and returns the challenge\n";
echo "   - Facebook confirms webhook is valid\n\n";

echo "2. LIVE VIDEO NOTIFICATION (When /tenth goes live)\n";
echo "   When a live video starts on the /tenth Facebook page:\n";
echo "   - Facebook sends: POST /wp-json/shimmer/v1/facebook-webhook\n";
echo "   - Payload example:\n";

$examplePayload = [
    'object' => 'page',
    'entry' => [
        [
            'id' => '123456789',  // Page ID
            'time' => 1699824264,
            'changes' => [
                [
                    'field' => 'live_videos',
                    'value' => [
                        'id' => '987654321',  // Video ID
                        'status' => 'live',
                        'title' => 'Sunday Morning Service',
                        'description' => 'Live worship service',
                        'stream_url' => 'rtmps://live-api-s.facebook.com/...',
                        'secure_stream_url' => 'rtmps://live-api-s.facebook.com/...',
                        'dash_ingest_url' => 'https://live-api-s.facebook.com/...',
                        'dash_ingest_secure_url' => 'https://live-api-s.facebook.com/...',
                    ]
                ]
            ]
        ]
    ]
];

echo json_encode($examplePayload, JSON_PRETTY_PRINT) . "\n\n";

echo "3. WEBHOOK PROCESSING\n";
echo "   - Plugin validates the request signature using HMAC\n";
echo "   - Extracts the video ID from the payload\n";
echo "   - Calls: FacebookWebhook::handleLiveVideo('987654321')\n\n";

echo "4. CUSTOM HANDLING (in handleLiveVideo method)\n";
echo "   Currently the stub method just logs:\n";
echo "   'Facebook Live Video Detected - Video ID: 987654321'\n\n";
echo "   You can customize this to:\n";
echo "   - Create a WordPress post\n";
echo "   - Send email notifications\n";
echo "   - Update custom fields\n";
echo "   - Trigger other integrations\n";
echo "   - Start recording/archiving\n";
echo "   - Update digital signage\n";
echo "   - Etc.\n\n";

echo "5. RESPONSE TO FACEBOOK\n";
echo "   - Plugin returns: 200 OK with 'EVENT_RECEIVED'\n";
echo "   - Facebook marks the event as delivered\n\n";

echo "=== Configuration Required ===\n";
echo "Method 1 (Recommended): WordPress Admin\n";
echo "   - Go to Settings > Facebook Webhook\n";
echo "   - Enter your Facebook App Secret\n";
echo "   - Enter your Webhook Verify Token\n";
echo "   - Click 'Save Settings'\n\n";
echo "Method 2 (Alternative): wp-config.php\n";
echo "   define('FACEBOOK_APP_SECRET', 'your_app_secret');\n";
echo "   define('FACEBOOK_WEBHOOK_VERIFY_TOKEN', 'your_verify_token');\n\n";

echo "=== Facebook App Setup ===\n";
echo "1. Create Facebook App at developers.facebook.com\n";
echo "2. Add Webhooks product\n";
echo "3. Configure callback URL: https://your-site.com/wp-json/shimmer/v1/facebook-webhook\n";
echo "4. Subscribe to page events\n";
echo "5. Select 'live_videos' field\n";
echo "6. Grant app access to the /tenth page\n\n";

echo "=== Webhook URL ===\n";
echo "https://your-wordpress-site.com/wp-json/shimmer/v1/facebook-webhook\n\n";
