<?php namespace Grrr\Redirects\WordPress;

use Grrr\Redirects\WordPress\DependencyChecker;
use Grrr\Redirects\WordPress\RedirectsApi;
use Grrr\Redirects\WordPress\RedirectsSyncer;
use Grrr\Redirects\WordPress\Cli\ImportRedirectsCommand;

class Plugin
{
    const VERSION = "1.0.0";
    const NAME = "WP Redirects Service";
    const PATH = "wp-redirects-service/plugin.php";

    /**
     * @var array<string> Required plugins paths (relative to plugins directory)
     *
     */
    const REQUIRED_PLUGINS = ["advanced-custom-fields-pro/acf.php"];

    public function __construct(protected RedirectsApi $redirects_api)
    {

    }

    public function init(): void
    {
        (new DependencyChecker(self::REQUIRED_PLUGINS))->register();

        // Load ACF configuration (post type and custom fields)
        add_filter("acf/settings/load_json", function ($paths) {
            $paths[] = __DIR__ . "/acf-json";
            return $paths;
        });

        (new RedirectsSyncer($this->redirects_api))->register();

        if (defined("WP_CLI") && WP_CLI) {
            (new ImportRedirectsCommand($this->redirects_api))->register();
        }

    }


}
