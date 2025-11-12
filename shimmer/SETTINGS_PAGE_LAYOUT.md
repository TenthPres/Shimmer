# WordPress Settings Page - Shimmer

## Location
WordPress Admin → Settings → Shimmer

## Settings Page Layout

```
┌─────────────────────────────────────────────────────────────────┐
│ Shimmer Settings                                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐ │
│ │ Facebook Webhook                                           │ │
│ ├────────────────────────────────────────────────────────────┤ │
│ │                                                            │ │
│ │ Configure your Facebook App credentials for webhook       │ │
│ │ integration. These settings are required to receive live   │ │
│ │ video notifications from the /tenth Facebook page.         │ │
│ │                                                            │ │
│ │ Webhook URL:                                               │ │
│ │ https://your-site.com/wp-json/shimmer/v1/facebook-webhook │ │
│ │ Use this URL when configuring your Facebook webhook       │ │
│ │                                                            │ │
│ │ Setup Instructions:                                        │ │
│ │  1. Create a Facebook App at developers.facebook.com      │ │
│ │  2. In your Facebook App, go to Settings > Basic and      │ │
│ │     copy your App Secret                                   │ │
│ │  3. Paste the App Secret in the field below               │ │
│ │  ... (more instructions)                                   │ │
│ │                                                            │ │
│ │ Facebook App Secret                                        │ │
│ │ [••••••••••••••••••••••••••••••]                          │ │
│ │ Enter your Facebook App Secret (found in Facebook App     │ │
│ │ Settings > Basic)                                          │ │
│ │                                                            │ │
│ │ Webhook Verify Token                                       │ │
│ │ [________________________________]                          │ │
│ │ Enter a custom verify token (you create this, and use     │ │
│ │ the same value when configuring the webhook in Facebook)  │ │
│ │                                                            │ │
│ └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│ [ Save Settings ]                                                │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Features

### Security
- **App Secret Field**: Password type input (masked)
- **Verify Token Field**: Text input (visible for copying)
- Values stored in WordPress options table
- Sanitized on save using `sanitize_text_field()`

### User Experience
- Clear instructions integrated into the settings page
- Webhook URL displayed for easy copying
- Step-by-step setup guide
- Success message shown after saving

### Access Control
- Only users with `manage_options` capability can access
- Typically administrators only

### Backward Compatibility
- Constants in wp-config.php still work
- Settings page values take precedence
- Gradual migration path for existing users

## Usage

After saving settings, the webhook will automatically:
1. Accept verification requests from Facebook
2. Process live video notifications
3. Call the `handleLiveVideo($videoId, $videoTitle)` stub method with video IDs and titles
