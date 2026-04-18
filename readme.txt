=== Wapid Automation for WooCommerce ===
Contributors: wapid
Tags: woocommerce, notifications, otp, order status, automation
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WooCommerce store with Wapid to automate order notifications and OTP verification flows.

== Description ==

Wapid Automation for WooCommerce lets you:

* connect your store to your Wapid account
* authenticate using API key (recommended) with legacy token fallback support
* manage messaging instances
* configure automated order and order-status notifications
* enable OTP verification for login, registration, and checkout
* use template mapping or fallback messages per event

The plugin provides a dedicated admin interface inside WordPress so store admins can configure notification events without code changes.

== External Services ==

This plugin connects to the Wapid API to authenticate your account, fetch instances/templates, and send notifications.

Service provider:
* Wapid
* Terms: https://wapid.net/terms
* Privacy Policy: https://wapid.net/privacy

When connected, the plugin may send/store data such as:
* site URL and redirect URL during connect/auth flow
* API key for API authentication (recommended)
* access/refresh token for legacy fallback authentication
* recipient phone number and message content for outgoing notifications
* order context used to build notification messages (for configured events)

No external requests are made until the admin explicitly connects the plugin.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install via Plugins > Add New.
2. Activate the plugin through the `Plugins` screen.
3. Ensure WooCommerce is active.
4. Open `Wapid` from the WordPress admin menu.
5. In `Automated Notifications`, add your API key in `API Authentication` (recommended), or click `Connect` once and auto-generate a dedicated WordPress API key.
6. Configure events in `Automated Notifications` and save.

== Frequently Asked Questions ==

= Is WooCommerce required? =
Yes. This plugin is built for WooCommerce events and checkout flows.

= Can I use fallback text instead of templates? =
Yes. Each event supports fallback message text when no template is selected.

= Does it support custom WooCommerce order statuses? =
Yes. Order statuses are loaded dynamically from WooCommerce, including custom statuses.

== Changelog ==

= 2.0.0 =
* Rebranded and aligned admin experience for Wapid.
* Added dedicated plugin shell/sidebar UI in admin pages.
* Added connect/disconnect flow with token callback handling.
* Added dynamic WooCommerce order-status event support.
* Added OTP event support for login, registration, and checkout.
* Added event-based template/fallback configuration cards.
