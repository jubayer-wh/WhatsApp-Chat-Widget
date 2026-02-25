<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Front-end rendering and behavior.
 */
class WCW_Frontend
{
    /**
     * Shortcode render flag.
     *
     * @var bool
     */
    private $rendered_via_shortcode = false;

    /**
     * Render guard to avoid duplicate output across hooks.
     *
     * @var bool
     */
    private $has_rendered = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_body_open', [$this, 'render_widget'], 99);
        add_action('wp_footer', [$this, 'render_widget'], 99);
        add_shortcode('whatsapp_chat_widget', [$this, 'shortcode']);
    }

    /**
     * Enqueue styles/scripts only when needed.
     *
     * @return void
     */
    public function enqueue_assets()
    {
        if (! $this->should_render()) {
            return;
        }

        wp_enqueue_style(
            'wcw-widget',
            WCW_URL . 'assets/css/wcw-widget.css',
            [],
            WCW_VERSION
        );

        wp_enqueue_script(
            'wcw-widget',
            WCW_URL . 'assets/js/wcw-widget.js',
            [],
            WCW_VERSION,
            true
        );

        wp_script_add_data('wcw-widget', 'defer', true);

        $settings = wcw_get_settings();
        wp_localize_script('wcw-widget', 'wcwWidgetData', [
            'delay' => (int) $settings['delay_seconds'],
            'tooltipAutoHide' => (int) $settings['tooltip_auto_hide'],
            'tooltipHideSeconds' => (int) $settings['tooltip_hide_seconds'],
            'animationEnabled' => (int) $settings['animation_enabled'],
            'pulseEnabled' => (int) $settings['pulse_enabled'],
            'exitIntentEnabled' => (int) $settings['exit_intent_enabled'],
            'gaEventEnabled' => (int) $settings['ga_event_enabled'],
        ]);

        if (! empty($settings['custom_css'])) {
            wp_add_inline_style('wcw-widget', (string) $settings['custom_css']);
        }
    }

    /**
     * Render widget in footer.
     *
     * @return void
     */
    public function render_widget()
    {
        if ($this->rendered_via_shortcode || $this->has_rendered) {
            return;
        }

        if (! $this->should_render()) {
            return;
        }

        $markup = $this->get_widget_markup();
        if ($markup === '') {
            return;
        }

        $this->has_rendered = true;
        echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Render shortcode output.
     *
     * @return string
     */
    public function shortcode()
    {
        if (! $this->should_render()) {
            return '';
        }

        $this->rendered_via_shortcode = true;

        return $this->get_widget_markup('shortcode');
    }

    /**
     * Build widget HTML.
     *
     * @param string $source Source context.
     * @return string
     */
    private function get_widget_markup($source = 'footer')
    {
        $settings = wcw_get_settings();
        $contacts = $this->parse_numbers((string) $settings['numbers']);

        if (empty($contacts)) {
            return '';
        }

        $first = $contacts[0];
        $message_raw = (string) $settings['default_message'];
        $position_class = $settings['position'] === 'left' ? 'wcw-left' : 'wcw-right';
        $style = '--wcw-color:' . esc_attr((string) $settings['button_color']) . ';';

        ob_start();
        ?>
        <div class="wcw-widget <?php echo esc_attr($position_class); ?>" data-source="<?php echo esc_attr($source); ?>" style="<?php echo esc_attr($style); ?>">
            <?php if (! empty($settings['tooltip_text'])) : ?>
                <div class="wcw-tooltip" role="status" aria-live="polite"><?php echo esc_html((string) $settings['tooltip_text']); ?></div>
            <?php endif; ?>

            <div class="wcw-panel" aria-hidden="true">
                <div class="wcw-panel-header">
                    <div class="wcw-header-text">
                        <strong><?php echo esc_html__('Chat with us', 'whatsapp-chat-widget'); ?></strong>
                    </div>
                </div>

                <form class="wcw-form" novalidate>
                    <label class="wcw-label" for="wcw-message-<?php echo esc_attr(md5($source)); ?>"><?php echo esc_html__('Message', 'whatsapp-chat-widget'); ?></label>
                    <div class="wcw-input-row">
                        <textarea id="wcw-message-<?php echo esc_attr(md5($source)); ?>" class="wcw-input" name="message" rows="3" placeholder="<?php echo esc_attr__('Type your message…', 'whatsapp-chat-widget'); ?>"></textarea>
                        <button type="submit" class="wcw-send" aria-label="<?php echo esc_attr__('Send message on WhatsApp', 'whatsapp-chat-widget'); ?>">
                            <span aria-hidden="true">➤</span>
                        </button>
                    </div>
                </form>
            </div>

            <button type="button" class="wcw-button"
                aria-label="<?php echo esc_attr__('Open WhatsApp chat form', 'whatsapp-chat-widget'); ?>"
                aria-expanded="false"
                data-default-phone="<?php echo esc_attr($first['number']); ?>"
                data-default-message="<?php echo esc_attr($message_raw); ?>">
                <span class="wcw-icon" aria-hidden="true">
                    <svg class="wcw-icon-chat" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.04 3C9.03 3 3.33 8.7 3.33 15.72c0 2.23.58 4.41 1.67 6.33L3 29l7.12-1.87a12.67 12.67 0 0 0 5.92 1.5h.01c7.01 0 12.7-5.7 12.7-12.71A12.7 12.7 0 0 0 16.04 3Zm7.39 17.98c-.31.86-1.78 1.65-2.46 1.75-.64.09-1.45.13-2.34-.16-.55-.17-1.26-.41-2.17-.8-3.82-1.66-6.3-5.54-6.5-5.8-.2-.27-1.56-2.08-1.56-3.96 0-1.89.98-2.82 1.33-3.21.35-.4.76-.49 1.02-.49s.5 0 .72.01c.23.01.53-.09.82.6.31.74 1.07 2.56 1.17 2.75.1.18.16.4.03.65-.12.24-.18.4-.36.61-.18.22-.38.49-.54.66-.18.18-.37.37-.16.73.2.36.9 1.49 1.93 2.42 1.33 1.18 2.46 1.55 2.81 1.73.35.18.56.15.77-.09.21-.24.88-1.02 1.12-1.37.23-.35.47-.29.79-.17.33.11 2.09.99 2.45 1.16.36.18.6.27.69.42.08.14.08.85-.24 1.71Z"/>
                    </svg>
                    <span class="wcw-icon-close" aria-hidden="true">✕</span>
                </span>
                <span class="screen-reader-text"><?php echo esc_html__('Chat on WhatsApp', 'whatsapp-chat-widget'); ?></span>
            </button>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Verify if widget should render for current request.
     *
     * @return bool
     */
    private function should_render()
    {
        if (is_admin()) {
            return false;
        }

        $settings = wcw_get_settings();
        if (empty($settings['enabled'])) {
            return false;
        }

        if (! empty($settings['working_hours_enabled']) && ! $this->is_within_working_hours($settings['working_hours'] ?? [])) {
            return false;
        }

        $current_id = get_queried_object_id();
        if (! $this->is_allowed_page($current_id, (string) $settings['include_pages'], (string) $settings['exclude_pages'])) {
            return false;
        }

        return true;
    }

    /**
     * Parse newline-delimited name|number values.
     *
     * @param string $raw Raw stored data.
     * @return array<int, array<string, string>>
     */
    private function parse_numbers($raw)
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        if (! is_array($lines)) {
            return [];
        }

        $contacts = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 2));
            $name = $parts[0] ?? 'Support';
            $number = preg_replace('/\D+/', '', $parts[1] ?? ($parts[0] ?? ''));

            if (! preg_match('/^[1-9]\d{6,14}$/', (string) $number)) {
                continue;
            }

            $contacts[] = [
                'name' => $name,
                'number' => $number,
            ];
        }

        return $contacts;
    }

    /**
     * Check schedule.
     *
     * @param array<string, mixed> $hours Hours by day key.
     * @return bool
     */
    private function is_within_working_hours($hours)
    {
        if (! is_array($hours) || empty($hours)) {
            return true;
        }

        $day_key = strtolower(wp_date('D'));
        $day_key = substr($day_key, 0, 3);

        $day_settings = $hours[$day_key] ?? null;
        if (! is_array($day_settings)) {
            return true;
        }

        if (empty($day_settings['enabled'])) {
            return false;
        }

        $current_minutes = ((int) wp_date('G') * 60) + (int) wp_date('i');
        $start_minutes = $this->time_to_minutes((string) ($day_settings['start'] ?? '00:00'));
        $end_minutes = $this->time_to_minutes((string) ($day_settings['end'] ?? '23:59'));

        // Support overnight ranges (e.g. 22:00 -> 02:00).
        if ($start_minutes > $end_minutes) {
            return $current_minutes >= $start_minutes || $current_minutes <= $end_minutes;
        }

        return $current_minutes >= $start_minutes && $current_minutes <= $end_minutes;
    }

    /**
     * Convert HH:MM to minutes.
     *
     * @param string $time Time value.
     * @return int
     */
    private function time_to_minutes($time)
    {
        if (! preg_match('/^(\d{2}):(\d{2})$/', $time, $m)) {
            return 0;
        }

        return ((int) $m[1] * 60) + (int) $m[2];
    }

    /**
     * Check include/exclude IDs.
     *
     * @param int $current_id Current object ID.
     * @param string $include_csv Include IDs.
     * @param string $exclude_csv Exclude IDs.
     * @return bool
     */
    private function is_allowed_page($current_id, $include_csv, $exclude_csv)
    {
        if (is_feed() || is_404() || is_search()) {
            return false;
        }

        $include = $this->csv_to_int_array($include_csv);
        $exclude = $this->csv_to_int_array($exclude_csv);

        if (! empty($include) && ! in_array((int) $current_id, $include, true)) {
            return false;
        }

        if (! empty($exclude) && in_array((int) $current_id, $exclude, true)) {
            return false;
        }

        return true;
    }

    /**
     * Convert csv ids to int array.
     *
     * @param string $csv Raw values.
     * @return array<int, int>
     */
    private function csv_to_int_array($csv)
    {
        $parts = array_filter(array_map('trim', explode(',', $csv)));
        return array_values(array_unique(array_map('absint', $parts)));
    }
}
