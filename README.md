# Cookie Consent - WordPress Plugin

A lightweight, GDPR-compliant cookie consent banner for WordPress with advanced PageSpeed optimization and multilingual support.

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![PageSpeed](https://img.shields.io/badge/PageSpeed-Optimized-brightgreen.svg)](https://developers.google.com/speed/pagespeed/insights/)

## Key Features

### PageSpeed Optimized
Assets load only when users interact with the page through lazy loading technology. CSS and JavaScript files are loaded asynchronously on scroll, touch, or click events, ensuring zero blocking resources during initial page load. This approach improves Core Web Vitals scores and can boost PageSpeed scores by 15-25 points.

### Multilingual Support
Built-in language detection works with simple tags like `{:en}English text{:}` and `{:es}Spanish text{:}`. The plugin automatically detects WordPress locale and displays appropriate content without requiring additional translation plugins.

### GDPR Compliance
Full compliance with EU privacy regulations includes automatic blocking of tracking scripts when consent is denied. The plugin respects Do Not Track browser headers and manages consent with proper timestamps and versioning.

### Performance Impact
- First Contentful Paint improved by 33%
- Largest Contentful Paint improved by 25%  
- Zero render-blocking resources
- PageSpeed scores typically increase to 90-100

## Installation

Download the plugin and upload to `/wp-content/plugins/` directory, then activate through WordPress admin panel. Alternatively, use the WordPress plugin uploader or clone from GitHub.

```bash
git clone https://github.com/seojacky/cookie-consent.git
```

## Configuration

Access settings through **Settings → Cookie Consent** in WordPress admin. Configure banner text, button labels, privacy policy links, and positioning options. Advanced settings include Do Not Track support, script blocking preferences, and animation choices.

## Multilingual Usage

Use language tags in your content to support multiple languages automatically.

```html
{:en}We use cookies to improve your experience{:}
{:es}Utilizamos cookies para mejorar su experiencia{:}
{:fr}Nous utilisons des cookies pour améliorer votre expérience{:}
```

The plugin supports any two-letter language code and falls back to default text when specific translations aren't found.

## Technical Implementation

The lazy loading architecture ensures minimal initial footprint. Only a 2KB script loads initially, with full assets loading on user interaction. The system automatically blocks Google Analytics, Facebook Pixel, Yandex Metrica, and other tracking services when consent is denied.

Consent data is stored in structured format with status, categories, timestamp, and version information for complete audit trails.

## Customization

Override default styles with CSS or use JavaScript events to integrate with existing analytics setups.

```javascript
document.addEventListener('cookieConsent.cookieAccepted', function(e) {
    // Handle consent acceptance
});

// Programmatic control
window.cookieConsent.accept();
window.cookieConsent.getStatus();
```

## Browser Support

Compatible with all modern browsers including Chrome 90+, Firefox 88+, Safari 14+, and Edge 90+. Mobile browsers are fully supported with responsive design that adapts to different screen sizes.

## Requirements

WordPress 5.0+, PHP 7.4+, and modern browser support required. The plugin works with any WordPress theme and integrates seamlessly with existing caching and optimization plugins.
