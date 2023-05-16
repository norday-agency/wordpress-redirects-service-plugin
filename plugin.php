<?php
/**
 * WP Redirects Service
 *
 * Manage redirects for the GRRR redirects service via WordPress
 *
 * Plugin Name: WP Redirects Service
 * Description: Manage redirects for the GRRR redirects service via WordPress
 * Author:      Ramiro Hammen <ramiro@grrr.nl>
 * Version:     1.0.0
 */

use Grrr\Redirects\WordPress\Plugin;

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('GRRR_REDIRECTS_SERVICE_API_URL')) {
    define('GRRR_REDIRECTS_SERVICE_API_URL', '');
}

$redirects_api = new Grrr\Redirects\WordPress\RedirectsApi(GRRR_REDIRECTS_SERVICE_API_URL);
$plugin = new Plugin($redirects_api);
$plugin->init();
