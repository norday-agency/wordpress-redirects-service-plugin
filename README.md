# WP redirects service

This plugin provides a Redirect post type and and makes sure the redirect data is sent
to the [GRRR Redirects Service](https://github.com/grrr-amsterdam/redirects-service).

## Installation via Composer

### Composer

First, add the `repositories` directive to your `composer.json`:

```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/grrr-amsterdam/wordpress-redirects-service-plugin"
    }
]
```

Second, require this package in your project:

```sh
composer require grrr-amsterdam/wp-redirects-service
```

Make sure to add the API URL from the redirects service to you `wp-config.php`:

```php
define("GRRR_REDIRECTS_SERVICE_API_URL", "https://your-api-url.com");
```

## How does this work?

When saving a `Redirect` post. The plugins reacts on these changes en we make sure the Redirects API is being kept up to date.
