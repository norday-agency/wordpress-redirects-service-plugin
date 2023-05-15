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

use Grrr\WPRedirectsService\Plugin;

require_once __DIR__ . '/vendor/autoload.php';

$plugin = new Plugin();