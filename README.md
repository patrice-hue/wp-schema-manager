# WP Schema Manager

A lightweight, developer-friendly WordPress plugin for managing JSON-LD structured data. Built for technical SEOs and developers who need precise, conflict-free schema output without the bloat of all-in-one SEO plugins.

---

## Overview

Most WordPress SEO plugins treat schema as an afterthought — outputting generic, often incorrect markup that fails validation or conflicts with existing structured data. WP Schema Manager is built differently: it gives you full control over JSON-LD schema output at the post, page, taxonomy, and template level, with a clean UI and no dependency on third-party SEO plugin ecosystems.

Designed with enterprise and agency workflows in mind, the plugin supports multiple schema types, custom field mapping, and environment-aware output — making it suitable for everything from local business sites to large-scale multi-location and franchise deployments.

---

## Features

### v0.1 — Foundation
- JSON-LD output injected cleanly into `<head>` — no inline `<script>` conflicts
- Schema type support: `LocalBusiness`, `Person`, `Organisation`, `WebSite`, `WebPage`
- Post/page-level schema override via meta box
- Enable/disable schema output per post, page, or post type
- Conflict detection — flags if another active plugin is already outputting schema
- Schema preview panel in the WordPress admin (raw JSON-LD output before publish)
- Validation-ready output tested against Google's Rich Results Test and Schema.org standards

### Current (v0.2 — Extended Types)
- `Service` and `ProfessionalService` schema support
- `FAQPage` schema with WordPress Details block (accordion) integration
- `BreadcrumbList` auto-generation from permalink structure and post hierarchy
- `Product` and `Offer` schema for WooCommerce (auto-detects WC product data)
- Bulk schema assignment by category, tag, or custom taxonomy (Tools → Schema Bulk Assign)
- Admin settings sections for Service details and BreadcrumbList toggle
- Per-post schema type override dropdown now includes all extended types

### Roadmap (v0.3 — Enterprise Features)
- Multisite network support with site-level schema inheritance
- REST API endpoints for headless and decoupled WordPress deployments
- Schema import/export (JSON) for cross-environment migration
- Hreflang-aware schema for multilingual sites (Polylang / WPML compatible)
- Integration with Google Search Console API for rich result performance tracking

---

## Why JSON-LD (and Not Microdata)?

Google explicitly recommends JSON-LD for structured data implementation. Unlike Microdata, JSON-LD is injected as a separate script block — it doesn't interfere with HTML structure, is easier to manage dynamically, and is far simpler to validate and debug. WP Schema Manager outputs only JSON-LD.

---

## Requirements

- WordPress 6.0+
- PHP 8.0+
- No third-party dependencies (vanilla WordPress APIs only)

---

## Installation

### Manual (Development)

```bash
git clone https://github.com/patrice-hue/wp-schema-manager.git
```

Copy the `wp-schema-manager` folder to your `/wp-content/plugins/` directory and activate via the WordPress admin.

### Via WordPress Admin (Coming Soon)

The plugin will be submitted to the WordPress Plugin Directory once v1.0 is stable. A Composer-compatible version is also planned.

---

## Configuration

Once activated, navigate to **Settings → Schema Manager** in your WordPress admin to:

1. Set your global/default schema type and organisation details
2. Configure site-wide `WebSite` and `Organisation` schema
3. Define which post types support schema overrides
4. Enable or disable schema output globally or per content type

Individual posts and pages have a **Schema** meta box in the editor for post-level overrides, custom field mapping, and output toggling.

---

## Schema Output Example

```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "My Business",
  "url": "https://example.com.au",
  "telephone": "+61 8 0000 0000",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "123 Example Street",
    "addressLocality": "Perth",
    "addressRegion": "WA",
    "postalCode": "6000",
    "addressCountry": "AU"
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
      "opens": "08:30",
      "closes": "17:00"
    }
  ]
}
```

---

## Developer Notes

The plugin is structured following WordPress coding standards and is built with extensibility in mind. Key filters and actions will be documented in the `/docs` folder as development progresses. Contributions, issues, and feature requests are welcome.

### Planned Filter Hooks

```php
// Modify schema output before it is rendered
apply_filters( 'wpschema_output', $schema_array, $post_id );

// Disable schema output for specific post types
apply_filters( 'wpschema_enabled_post_types', $post_types );

// Add custom schema type support
apply_filters( 'wpschema_register_type', $types );
```

---

## About the Author

[Patrice Cognard](https://www.digitalhitmen.com.au/) is a Senior SEO Strategist at [Digital Hitmen](https://www.digitalhitmen.com.au/) with 25+ years of experience delivering enterprise SEO strategies for complex, high-traffic digital platforms. His background spans systems architecture, security, and large-scale digital infrastructure — and this plugin is a direct product of real-world frustrations with schema implementation at enterprise scale.

---

## Licence

GPL-2.0-or-later — see [LICENSE](LICENSE) for details. In line with WordPress core and plugin directory requirements.

---

## Contributing

This project is in active early development. If you're a developer or technical SEO with an interest in structured data tooling, feel free to open an issue or submit a pull request. Feature requests aligned with the roadmap are prioritised.

---

## Links

- [Digital Hitmen](https://www.digitalhitmen.com.au/)
- [Schema.org Documentation](https://schema.org/)
- [Google Structured Data Guidelines](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data)
- [Google Rich Results Test](https://search.google.com/test/rich-results)
