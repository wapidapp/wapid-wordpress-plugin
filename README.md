# Wapid for WooCommerce

Official WordPress plugin for Wapid to automate WooCommerce-triggered WhatsApp notifications using secure API integration.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](#requirements)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-purple.svg)](#requirements)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4.svg)](#requirements)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](./LICENSE)

## Overview

Wapid for WooCommerce lets store owners trigger WhatsApp notifications based on order events with centralized control from Wapid.

## Features

- WooCommerce order event integration
- Template-driven message workflows
- Delivery logging and audit visibility
- Admin settings for API keys and behavior control
- Secure communication with Wapid backend
- Production-focused defaults for reliability

## Requirements

- WordPress `6.0+`
- WooCommerce `7.0+`
- PHP `8.0+`
- Active Wapid account
- Reachable Wapid API endpoint

## Installation

1. Download or clone this repository.
2. Place plugin folder in `wp-content/plugins/`.
3. Activate **Wapid for WooCommerce** from WordPress Admin.
4. Open plugin settings and connect your Wapid API credentials.
5. Configure templates and event mappings.
6. Run a test order flow.

## Configuration

Set the following in plugin settings:

- API Base URL
- API Key / Secret
- Webhook secret (if enabled)
- Event-to-template mappings
- Retry and timeout behavior

## Usage

1. Create or sync templates in Wapid.
2. Map WooCommerce events to templates.
3. Trigger events with order lifecycle updates.
4. Track delivery status and logs from dashboard/admin logs.

## Security

- Never hardcode secrets in source code.
- Rotate API keys periodically.
- Restrict admin access to trusted roles.
- Report vulnerabilities privately to: `support@wapid.net`

See [SECURITY.md](./SECURITY.md) for full policy.

## Support

- Technical Support: `support@wapid.net`
- Billing: `billing@wapid.net`

## Contributing

Please read [CONTRIBUTING.md](./CONTRIBUTING.md) before submitting PRs.

## Changelog

See [CHANGELOG.md](./CHANGELOG.md).

## License

Released under [GPL-2.0-or-later](./LICENSE).

---

Maintained by **Wapid**  
Website: https://wapid.net
