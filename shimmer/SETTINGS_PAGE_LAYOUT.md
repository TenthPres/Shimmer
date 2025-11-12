# WordPress Settings Page - Facebook Webhook

## Location
WordPress Admin → Settings → Facebook Webhook

## Settings Page Layout

```
┌─────────────────────────────────────────────────────────────────┐
│ Facebook Webhook Settings                                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│ Configure your Facebook App credentials for webhook             │
│ integration. These settings are required to receive live video  │
│ notifications from the /tenth Facebook page.                    │
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐ │
│ │ Facebook App Credentials                                   │ │
│ ├────────────────────────────────────────────────────────────┤ │
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
├─────────────────────────────────────────────────────────────────┤
│ Webhook Information                                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│ Webhook URL:                                                     │
│ https://your-site.com/wp-json/shimmer/v1/facebook-webhook      │
│ Use this URL when configuring your Facebook webhook             │
│                                                                  │
├─────────────────────────────────────────────────────────────────┤
│ Setup Instructions                                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. Create a Facebook App at developers.facebook.com            │
│  2. In your Facebook App, go to Settings > Basic and copy       │
│     your App Secret                                              │
│  3. Paste the App Secret in the field above                     │
│  4. Create a custom Verify Token (any random string) and        │
│     enter it above                                               │
│  5. Save these settings                                          │
│  6. In your Facebook App, add the "Webhooks" product            │
│  7. Click "Add Callback URL" and enter the Webhook URL          │
│     shown above                                                  │
│  8. Enter the same Verify Token you created in step 4           │
│  9. Click "Verify and Save"                                      │
│ 10. Subscribe to the live_videos field for your page            │
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
3. Call the `handleLiveVideo()` stub method with video IDs
