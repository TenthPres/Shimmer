<?php

namespace tp\Shimmer;

/**
 * ShimmerSettings manages all settings for the Shimmer plugin
 * 
 * This class provides a centralized settings interface for all Shimmer components.
 * Components can register their settings sections and fields through this class.
 */
class ShimmerSettings {

    private static $sections = [];
    private static $fields = [];
    
    /**
     * Initialize the settings system
     */
    public static function load(): void
    {
        add_action('admin_menu', [self::class, 'addSettingsPage']);
        add_action('admin_init', [self::class, 'registerSettings']);
    }

    /**
     * Register a settings section
     * 
     * @param string $id Section ID
     * @param string $title Section title
     * @param callable|null $callback Optional callback to render section description
     */
    public static function registerSection(string $id, string $title, ?callable $callback = null): void
    {
        self::$sections[$id] = [
            'title' => $title,
            'callback' => $callback
        ];
    }

    /**
     * Register a settings field
     * 
     * @param string $id Field ID (also used as option name)
     * @param string $sectionId Section ID this field belongs to
     * @param string $title Field title
     * @param string $type Field type (text, password, textarea, checkbox)
     * @param array $args Additional arguments (description, default, sanitize_callback, etc.)
     */
    public static function registerField(string $id, string $sectionId, string $title, string $type = 'text', array $args = []): void
    {
        self::$fields[$id] = [
            'section' => $sectionId,
            'title' => $title,
            'type' => $type,
            'args' => $args
        ];
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public static function addSettingsPage(): void
    {
        add_options_page(
            __('Shimmer Settings', 'shimmer'),
            __('Shimmer', 'shimmer'),
            'manage_options',
            'shimmer-settings',
            [self::class, 'renderSettingsPage']
        );
    }

    /**
     * Register all settings with WordPress
     */
    public static function registerSettings(): void
    {
        // Register each field as a WordPress setting
        foreach (self::$fields as $fieldId => $field) {
            $args = $field['args'];
            register_setting('shimmer_settings', $fieldId, [
                'type' => 'string',
                'sanitize_callback' => $args['sanitize_callback'] ?? 'sanitize_text_field',
                'default' => $args['default'] ?? '',
            ]);
        }

        // Register sections
        foreach (self::$sections as $sectionId => $section) {
            add_settings_section(
                $sectionId,
                $section['title'],
                $section['callback'],
                'shimmer-settings'
            );
        }

        // Register fields
        foreach (self::$fields as $fieldId => $field) {
            add_settings_field(
                $fieldId,
                $field['title'],
                [self::class, 'renderField'],
                'shimmer-settings',
                $field['section'],
                ['field_id' => $fieldId, 'field' => $field]
            );
        }
    }

    /**
     * Render a settings field
     * 
     * @param array $args Field arguments
     */
    public static function renderField(array $args): void
    {
        $fieldId = $args['field_id'];
        $field = $args['field'];
        $value = get_option($fieldId, $field['args']['default'] ?? '');
        $description = $field['args']['description'] ?? '';

        switch ($field['type']) {
            case 'password':
                echo '<input type="password" name="' . esc_attr($fieldId) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
            case 'textarea':
                echo '<textarea name="' . esc_attr($fieldId) . '" class="large-text" rows="5">' . esc_textarea($value) . '</textarea>';
                break;
            case 'checkbox':
                $checked = checked($value, '1', false);
                echo '<input type="checkbox" name="' . esc_attr($fieldId) . '" value="1" ' . $checked . ' />';
                break;
            case 'text':
            default:
                echo '<input type="text" name="' . esc_attr($fieldId) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
        }

        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    /**
     * Render the settings page
     */
    public static function renderSettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Show success message if settings were updated
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'shimmer_settings_messages',
                'shimmer_settings_message',
                __('Settings Saved', 'shimmer'),
                'updated'
            );
        }

        settings_errors('shimmer_settings_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('shimmer_settings');
                do_settings_sections('shimmer-settings');
                submit_button(__('Save Settings', 'shimmer'));
                ?>
            </form>
        </div>
        <?php
    }
}
