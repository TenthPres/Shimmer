# Facebook Webhook Configuration

This plugin now includes Facebook webhook functionality to detect when the Facebook page `/tenth` has a new live video.

## Setup Instructions

### 1. Facebook App Configuration

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app or use an existing one
3. Add the "Webhooks" product to your app
4. In your app settings, note down:
   - App Secret (found in Settings > Basic)
   - Create a Verify Token (a random string you create)

### 2. WordPress Configuration

Add the following constants to your `wp-config.php` file:

```php
// Facebook App Configuration
define('FACEBOOK_APP_SECRET', 'your_app_secret_here');
define('FACEBOOK_WEBHOOK_VERIFY_TOKEN', 'your_verify_token_here');
```

### 3. Webhook Subscription Setup

1. In your Facebook App dashboard, go to Webhooks
2. Click "Add Callback URL"
3. Enter your webhook URL: `https://your-site.com/wp-json/shimmer/v1/facebook-webhook`
4. Enter the Verify Token (the same one you defined in wp-config.php)
5. Click "Verify and Save"

### 4. Subscribe to Page Events

1. In the Webhooks section, find your Page subscription
2. Click "Add Subscriptions"
3. Select the `live_videos` field
4. Save the subscription

### 5. Grant Permissions

1. Your Facebook App needs permission to access the page
2. Go to your Facebook Page settings
3. Add your app with appropriate permissions

## How It Works

When a live video is started on the Facebook page `/tenth`:

1. Facebook sends a POST request to the webhook endpoint
2. The request is verified using the app secret
3. The webhook payload is parsed to extract the video ID
4. The `FacebookWebhook::handleLiveVideo($videoId)` method is called with the video ID

## Customization

The stub method `FacebookWebhook::handleLiveVideo($videoId)` currently just logs the video ID. You can customize this method in `/shimmer/FacebookWebhook.php` to:

- Create a WordPress post
- Send notifications
- Update custom fields
- Trigger other actions

## Testing

To test the webhook:

1. Use Facebook's webhook testing tool in the App dashboard
2. Send a test event for `live_videos`
3. Check your WordPress error logs for the message: "Facebook Live Video Detected - Video ID: {id}"

## Security

- All webhook requests are verified using HMAC signature validation
- The verify token prevents unauthorized webhook subscriptions
- Both GET (verification) and POST (notifications) endpoints are properly secured
