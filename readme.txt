=== WhatsApp Chat Widget ===
Contributors: jubayer1
Tags: whatsapp, chat, widget, floating button, contact
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, modern, and responsive WhatsApp chat widget with scheduling, animation controls, and shortcode support.

== Description ==

WhatsApp Chat Widget adds a clean floating WhatsApp button and compact chat panel to your WordPress website.

Features include:

* Floating WhatsApp button (left/right positioning)
* Prefilled message support
* Multiple WhatsApp numbers with contact selector
* Optional working-hours scheduling
* Tooltip text and auto-hide behavior
* Fade/pulse animation toggles
* Delay appearance and optional exit-intent trigger
* Page include/exclude visibility control
* Optional Google Analytics click event support
* Shortcode support: `[whatsapp_chat_widget]`
* Custom CSS field for advanced styling
* Responsive and dark-mode-friendly UI

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install via Plugins > Add New.
2. Activate **WhatsApp Chat Widget** through the 'Plugins' screen in WordPress.
3. Go to **Settings > WhatsApp Chat Widget**.
4. Configure your WhatsApp numbers, message, display rules, and style settings.

== Frequently Asked Questions ==

= How do I add multiple WhatsApp numbers? =

Use one line per contact in this format:

`Business Name|InternationalNumber`

Example:

`Sales|15551234567`

= How do I show it only on selected pages? =

Use the include/exclude page ID fields in plugin settings.

= Is there a shortcode? =

Yes. Use:

`[whatsapp_chat_widget]`

== Changelog ==

= 1.0.0 =
* Initial release.
* Admin settings page with secure sanitization.
* Frontend floating widget with popup form and WhatsApp redirection.
* Scheduling, animation controls, visibility targeting, and shortcode support.
