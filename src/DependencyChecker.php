<?php namespace Grrr\Redirects\WordPress;

use Grrr\Redirects\WordPress\Plugin;

final class DependencyChecker {

    /**
     * Check if required plugins are active.
     *
     * @param array<string> $required_plugins
     */
    public function __construct(protected array $required_plugins) {
    }

    public function register() {
        add_action("activate_plugin", [$this, "check_dependencies"]);
        add_action("plugins_loaded", [$this, "check_dependencies"]);
    }

    public function check_dependencies(): void
    {
        /** @var array<int, string> $active_plugins */
        $active_plugins = get_option("active_plugins") ?: [];
        $missing_plugins = array_diff($this->required_plugins, $active_plugins);

        if (count($missing_plugins)) {
            add_action(
                "admin_notices",
                $this->show_missing_plugin_notices($missing_plugins)
            );
        }
    }

    public function show_missing_plugin_notices(
        array $missing_plugins
    ): callable {
        return function () use ($missing_plugins) {
            $message = sprintf(
                "The plugin <strong>%s</strong> requires the following plugin(s)to be installed and activated.<br><strong>%s</strong>",
                Plugin::NAME,
                implode(", ", $missing_plugins)
            );
            echo '<div class="notice notice-error"><p>' .
                $message .
                "</p></div>";
        };
    }
}

