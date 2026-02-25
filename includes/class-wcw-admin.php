<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles all admin settings and UI rendering.
 */
class WCW_Admin
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Register menu.
     *
     * @return void
     */
    public function add_menu_page()
    {
        add_options_page(
            __('WhatsApp Chat Widget', 'whatsapp-chat-widget'),
            __('WhatsApp Chat Widget', 'whatsapp-chat-widget'),
            'manage_options',
            'wcw-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue color picker for settings page.
     *
     * @param string $hook Current admin hook.
     * @return void
     */
    public function enqueue_assets($hook)
    {
        if ($hook !== 'settings_page_wcw-settings') {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }

    /**
     * Register settings and sections.
     *
     * @return void
     */
    public function register_settings()
    {
        register_setting(
            'wcw_settings_group',
            WCW_OPTION_KEY,
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => wcw_get_default_settings(),
            ]
        );

        add_settings_section(
            'wcw_main_section',
            __('Widget Settings', 'whatsapp-chat-widget'),
            '__return_false',
            'wcw-settings'
        );

        $fields = [
            'enabled' => __('Enable Widget', 'whatsapp-chat-widget'),
            'numbers' => __('WhatsApp Numbers', 'whatsapp-chat-widget'),
            'default_message' => __('Default Message', 'whatsapp-chat-widget'),
            'position' => __('Button Position', 'whatsapp-chat-widget'),
            'button_color' => __('Button Color', 'whatsapp-chat-widget'),
            'tooltip_text' => __('Tooltip Text', 'whatsapp-chat-widget'),
            'tooltip_auto_hide' => __('Auto-hide Tooltip', 'whatsapp-chat-widget'),
            'tooltip_hide_seconds' => __('Tooltip Hide Delay (seconds)', 'whatsapp-chat-widget'),
            'animation_enabled' => __('Enable Fade Animation', 'whatsapp-chat-widget'),
            'pulse_enabled' => __('Enable Pulse Animation', 'whatsapp-chat-widget'),
            'delay_seconds' => __('Delay Appearance (seconds)', 'whatsapp-chat-widget'),
            'exit_intent_enabled' => __('Enable Exit Intent (Desktop)', 'whatsapp-chat-widget'),
            'ga_event_enabled' => __('Enable Google Analytics Click Event', 'whatsapp-chat-widget'),
            'working_hours' => __('Working Hours', 'whatsapp-chat-widget'),
            'include_pages' => __('Show Only on Page IDs', 'whatsapp-chat-widget'),
            'exclude_pages' => __('Hide on Page IDs', 'whatsapp-chat-widget'),
            'custom_css' => __('Custom CSS', 'whatsapp-chat-widget'),
        ];

        foreach ($fields as $key => $label) {
            add_settings_field(
                'wcw_' . $key,
                $label,
                [$this, 'render_field'],
                'wcw-settings',
                'wcw_main_section',
                ['key' => $key]
            );
        }
    }

    /**
     * Sanitize settings values.
     *
     * @param array<string, mixed> $input Raw input.
     * @return array<string, mixed>
     */
    public function sanitize_settings($input)
    {
        if (! is_array($input)) {
            return wcw_get_default_settings();
        }

        $defaults = wcw_get_default_settings();
        $clean = $defaults;

        $clean['enabled'] = ! empty($input['enabled']) ? 1 : 0;
        $clean['default_message'] = sanitize_text_field($input['default_message'] ?? '');
        $clean['position'] = in_array($input['position'] ?? 'right', ['left', 'right'], true) ? $input['position'] : 'right';
        $clean['button_color'] = sanitize_hex_color($input['button_color'] ?? $defaults['button_color']) ?: $defaults['button_color'];
        $clean['tooltip_text'] = sanitize_text_field($input['tooltip_text'] ?? '');
        $clean['tooltip_auto_hide'] = ! empty($input['tooltip_auto_hide']) ? 1 : 0;
        $clean['tooltip_hide_seconds'] = max(1, absint($input['tooltip_hide_seconds'] ?? 5));
        $clean['animation_enabled'] = ! empty($input['animation_enabled']) ? 1 : 0;
        $clean['pulse_enabled'] = ! empty($input['pulse_enabled']) ? 1 : 0;
        $clean['delay_seconds'] = min(60, max(0, absint($input['delay_seconds'] ?? 0)));
        $clean['exit_intent_enabled'] = ! empty($input['exit_intent_enabled']) ? 1 : 0;
        $clean['ga_event_enabled'] = ! empty($input['ga_event_enabled']) ? 1 : 0;
        $clean['custom_css'] = $this->sanitize_custom_css((string) ($input['custom_css'] ?? ''));
        $clean['include_pages'] = preg_replace('/[^0-9,]/', '', (string) ($input['include_pages'] ?? ''));
        $clean['exclude_pages'] = preg_replace('/[^0-9,]/', '', (string) ($input['exclude_pages'] ?? ''));

        $clean['numbers'] = $this->sanitize_numbers((string) ($input['numbers'] ?? ''));

        $clean['working_hours'] = $defaults['working_hours'];
        if (! empty($input['working_hours']) && is_array($input['working_hours'])) {
            foreach ($clean['working_hours'] as $day => $value) {
                $day_input = $input['working_hours'][$day] ?? [];
                $start = $this->sanitize_time($day_input['start'] ?? $value['start']);
                $end = $this->sanitize_time($day_input['end'] ?? $value['end']);
                $clean['working_hours'][$day] = [
                    'enabled' => ! empty($day_input['enabled']) ? 1 : 0,
                    'start' => $start,
                    'end' => $end,
                ];
            }
        }

        return $clean;
    }

    /**
     * Render each settings field.
     *
     * @param array<string, string> $args Field args.
     * @return void
     */
    public function render_field($args)
    {
        $settings = wcw_get_settings();
        $key = $args['key'];
        $name = WCW_OPTION_KEY . '[' . $key . ']';

        switch ($key) {
            case 'enabled':
            case 'tooltip_auto_hide':
            case 'animation_enabled':
            case 'pulse_enabled':
            case 'exit_intent_enabled':
            case 'ga_event_enabled':
                printf(
                    '<label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label>',
                    esc_attr($name),
                    checked(1, (int) $settings[$key], false),
                    esc_html__('Enabled', 'whatsapp-chat-widget')
                );
                break;

            case 'numbers':
                printf(
                    '<textarea name="%1$s" rows="5" class="large-text code">%2$s</textarea><p class="description">%3$s</p>',
                    esc_attr($name),
                    esc_textarea((string) $settings[$key]),
                    esc_html__('One per line in format: Business Name|InternationalNumber (digits only, e.g., Support|15551234567).', 'whatsapp-chat-widget')
                );
                break;

            case 'default_message':
            case 'tooltip_text':
                printf(
                    '<input type="text" class="regular-text" name="%1$s" value="%2$s">',
                    esc_attr($name),
                    esc_attr((string) $settings[$key])
                );
                break;

            case 'position':
                printf(
                    '<select name="%1$s"><option value="right" %2$s>%3$s</option><option value="left" %4$s>%5$s</option></select>',
                    esc_attr($name),
                    selected('right', (string) $settings[$key], false),
                    esc_html__('Right', 'whatsapp-chat-widget'),
                    selected('left', (string) $settings[$key], false),
                    esc_html__('Left', 'whatsapp-chat-widget')
                );
                break;

            case 'button_color':
                printf(
                    '<input type="text" class="wcw-color-field" data-default-color="#25D366" name="%1$s" value="%2$s">',
                    esc_attr($name),
                    esc_attr((string) $settings[$key])
                );
                echo '<script>document.addEventListener("DOMContentLoaded",function(){if(window.jQuery&&jQuery.fn.wpColorPicker){jQuery(".wcw-color-field").wpColorPicker();}});</script>';
                break;

            case 'tooltip_hide_seconds':
            case 'delay_seconds':
                printf(
                    '<input type="number" min="0" max="60" name="%1$s" value="%2$s" class="small-text">',
                    esc_attr($name),
                    esc_attr((string) $settings[$key])
                );
                break;

            case 'working_hours':
                $labels = [
                    'mon' => __('Mon', 'whatsapp-chat-widget'),
                    'tue' => __('Tue', 'whatsapp-chat-widget'),
                    'wed' => __('Wed', 'whatsapp-chat-widget'),
                    'thu' => __('Thu', 'whatsapp-chat-widget'),
                    'fri' => __('Fri', 'whatsapp-chat-widget'),
                    'sat' => __('Sat', 'whatsapp-chat-widget'),
                    'sun' => __('Sun', 'whatsapp-chat-widget'),
                ];
                echo '<table><tbody>';
                foreach ($labels as $day => $label) {
                    $day_settings = $settings['working_hours'][$day] ?? ['enabled' => 0, 'start' => '09:00', 'end' => '18:00'];
                    printf(
                        '<tr><td><strong>%1$s</strong></td><td><label><input type="checkbox" name="%2$s[working_hours][%3$s][enabled]" value="1" %4$s> %5$s</label></td><td><input type="time" name="%2$s[working_hours][%3$s][start]" value="%6$s"></td><td><input type="time" name="%2$s[working_hours][%3$s][end]" value="%7$s"></td></tr>',
                        esc_html($label),
                        esc_attr(WCW_OPTION_KEY),
                        esc_attr($day),
                        checked(1, (int) $day_settings['enabled'], false),
                        esc_html__('Open', 'whatsapp-chat-widget'),
                        esc_attr((string) $day_settings['start']),
                        esc_attr((string) $day_settings['end'])
                    );
                }
                echo '</tbody></table>';
                break;

            case 'include_pages':
            case 'exclude_pages':
                printf(
                    '<input type="text" class="regular-text" name="%1$s" value="%2$s"><p class="description">%3$s</p>',
                    esc_attr($name),
                    esc_attr((string) $settings[$key]),
                    esc_html__('Comma-separated WordPress page/post IDs.', 'whatsapp-chat-widget')
                );
                break;

            case 'custom_css':
                printf(
                    '<textarea name="%1$s" rows="6" class="large-text code" placeholder=".wcw-widget { bottom: 30px; }">%2$s</textarea>',
                    esc_attr($name),
                    esc_textarea((string) $settings[$key])
                );
                break;
        }
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('WhatsApp Chat Widget', 'whatsapp-chat-widget'); ?></h1>
            <p><?php echo esc_html__('Gentle and conversion-focused WhatsApp chat for your visitors.', 'whatsapp-chat-widget'); ?></p>
            <form method="post" action="options.php">
                <?php
                settings_fields('wcw_settings_group');
                do_settings_sections('wcw-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize business-number lines.
     *
     * @param string $raw Raw input lines.
     * @return string
     */
    private function sanitize_numbers($raw)
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $clean_lines = [];

        if (! is_array($lines)) {
            return '';
        }

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 2));
            $name = sanitize_text_field($parts[0] ?? 'Support');
            $number = preg_replace('/\D+/', '', $parts[1] ?? ($parts[0] ?? ''));

            if (! $this->is_valid_international_number($number)) {
                continue;
            }

            $clean_lines[] = $name . '|' . $number;
        }

        return implode("\n", $clean_lines);
    }

    /**
     * Check if number looks like international E.164-compatible digits.
     *
     * @param string $number Phone number.
     * @return bool
     */
    private function is_valid_international_number($number)
    {
        return (bool) preg_match('/^[1-9]\d{6,14}$/', (string) $number);
    }

    /**
     * Sanitize HH:MM format.
     *
     * @param string $time Input.
     * @return string
     */
    private function sanitize_time($time)
    {
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', (string) $time)) {
            return $time;
        }

        return '09:00';
    }

    /**
     * Best-effort custom CSS sanitization without stripping CSS syntax.
     *
     * @param string $css Raw css input.
     * @return string
     */
    private function sanitize_custom_css($css)
    {
        // Remove HTML tags and null bytes while preserving CSS braces/selectors.
        $css = wp_kses($css, []);
        $css = str_replace("\0", '', $css);

        // Block obvious remote/script injection vectors.
        $css = preg_replace('/@import\s+url\s*\(.+?\)\s*;?/i', '', (string) $css);
        $css = preg_replace('/expression\s*\(|javascript\s*:/i', '', (string) $css);

        return trim((string) $css);
    }
}
