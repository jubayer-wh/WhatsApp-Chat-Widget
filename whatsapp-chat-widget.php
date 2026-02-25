<?php
/**
 * Plugin Name: WhatsApp Chat Widget
 * Plugin URI:  https://example.com/whatsapp-chat-widget
 * Description: Lightweight, modern, and responsive WhatsApp chat widget with scheduling, animation controls, and shortcode support.
 * Version:     1.0.0
 * Author:      ChatGPT
 * Text Domain: whatsapp-chat-widget
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WCW_VERSION', '1.0.0');
define('WCW_FILE', __FILE__);
define('WCW_PATH', plugin_dir_path(__FILE__));
define('WCW_URL', plugin_dir_url(__FILE__));
define('WCW_OPTION_KEY', 'wcw_settings');

require_once WCW_PATH . 'includes/class-wcw-admin.php';
require_once WCW_PATH . 'includes/class-wcw-frontend.php';

/**
 * Bootstrap plugin components.
 *
 * @return void
 */
function wcw_init()
{
    new WCW_Admin();
    new WCW_Frontend();
}
add_action('plugins_loaded', 'wcw_init');

/**
 * Add quick settings link on plugins screen.
 *
 * @param string[] $links Existing links.
 * @return string[]
 */
function wcw_add_settings_link($links)
{
    $url = admin_url('options-general.php?page=wcw-settings');
    $settings_link = '<a href="' . esc_url($url) . '">' . esc_html__('Settings', 'whatsapp-chat-widget') . '</a>';
    array_unshift($links, $settings_link);

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(WCW_FILE), 'wcw_add_settings_link');

/**
 * Default settings.
 *
 * @return array<string, mixed>
 */
function wcw_get_default_settings()
{
    return [
        'enabled' => 1,
        'numbers' => "Support|15551234567",
        'default_message' => 'Hi 👋 Need help?',
        'position' => 'right',
        'button_color' => '#25D366',
        'tooltip_text' => 'Hi 👋 Need help?',
        'tooltip_auto_hide' => 1,
        'tooltip_hide_seconds' => 5,
        'animation_enabled' => 1,
        'pulse_enabled' => 1,
        'delay_seconds' => 3,
        'exit_intent_enabled' => 0,
        'working_hours' => [
            'mon' => ['enabled' => 1, 'start' => '09:00', 'end' => '18:00'],
            'tue' => ['enabled' => 1, 'start' => '09:00', 'end' => '18:00'],
            'wed' => ['enabled' => 1, 'start' => '09:00', 'end' => '18:00'],
            'thu' => ['enabled' => 1, 'start' => '09:00', 'end' => '18:00'],
            'fri' => ['enabled' => 1, 'start' => '09:00', 'end' => '18:00'],
            'sat' => ['enabled' => 0, 'start' => '09:00', 'end' => '13:00'],
            'sun' => ['enabled' => 0, 'start' => '09:00', 'end' => '13:00'],
        ],
        'custom_css' => '',
        'include_pages' => '',
        'exclude_pages' => '',
        'ga_event_enabled' => 0,
    ];
}

/**
 * Get merged settings.
 *
 * @return array<string, mixed>
 */
function wcw_get_settings()
{
    $saved = get_option(WCW_OPTION_KEY, []);

    if (! is_array($saved)) {
        $saved = [];
    }

    return wp_parse_args($saved, wcw_get_default_settings());
}

register_activation_hook(WCW_FILE, function () {
    if (! get_option(WCW_OPTION_KEY)) {
        add_option(WCW_OPTION_KEY, wcw_get_default_settings());
    }
});
