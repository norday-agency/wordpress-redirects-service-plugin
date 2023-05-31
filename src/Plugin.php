<?php namespace Grrr\Redirects\WordPress;

use Grrr\Redirects\WordPress\RedirectsApi;
use Grrr\Redirects\WordPress\Models\Redirect;
use WP_Post;

class Plugin
{
    const VERSION = "1.0.0";
    const NAME = "WP Redirects Service";
    const PATH = "wp-redirects-service/plugin.php";
    const POST_TYPE = "grrr-redirect";

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
        add_action("activate_plugin", [$this, "check_dependencies"]);
        add_action("plugins_loaded", [$this, "check_dependencies"]);

        // Register Redirect post type
        // add_action('init', [$this, 'register_post_type']);

        // Load ACF configuration (post type and custom fields)
        add_filter("acf/settings/load_json", function ($paths) {
            $paths[] = __DIR__ . "/acf-json";
            return $paths;
        });

        // Programmatically update the post title to reflect the redirect
        add_action("save_post", [$this, "update_redirect_post_title"]);

        // Update remote redirects on save
        // This is done in two steps, before and after save, because we need to
        // know the original from value to determine whether to create or update.

        // Applied before save, because the hook priority is < 10
        add_action("acf/save_post", [$this, "save_original_from"], 5);

        // Applied after save, because the hook priority is >= 10
        add_action("acf/save_post", [$this, "update_remote_redirect"], 10);

        // Remove from remote on delete
        add_action("wp_trash_post", [$this, "delete_redirect"]);

        // Update remote on certain status transitions
        add_action(
            "transition_post_status",
            [$this, "update_remote_redirect_on_status_change"],
            10,
            3
        );
    }

    public function update_remote_redirect_on_status_change(
        string $new_status,
        string $old_status,
        WP_Post $post
    ): void {
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }
        if ($old_status == $new_status) {
            return;
        }
        $from = get_field("from", $post->ID);
        // When the from value is empty, we are dealing with a newly created redirect
        // that has not been saved yet. We don't want to update the remote in this case.
        if (!$from) {
            return;
        }
        $redirect = Redirect::from_post_id($post->ID);

        if (!is_post_status_viewable($new_status)) {
            $this->redirects_api->delete($redirect->from);
            return;
        }
        $this->redirects_api->update($redirect);
    }

    public function delete_redirect(int $post_id): void
    {
        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }
        $redirect = Redirect::from_post_id($post_id);
        $this->redirects_api->delete($redirect->from);
    }

    /**
     * Save original from value as meta value
     *
     * This is called before the post is saved.
     *
     * @param integer $post_id
     * @return void
     */
    public function save_original_from(int $post_id)
    {
        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }
        // Get previous from value
        $from_before_save = get_field("from", $post_id);

        // Create or save from before save as meta value
        update_post_meta(
            $post_id,
            Redirect::ORIGINAL_FROM_META_KEY,
            $from_before_save
        );
    }

    /**
     * Update remote redirect
     *
     * This is called after the post is saved.
     *
     * @param integer $post_id
     * @return void
     */
    public function update_remote_redirect(int $post_id)
    {
        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }
        $redirect = Redirect::from_post_id($post_id);
        $post_status = get_post_status($post_id);

        if (!$post_status || !is_post_status_viewable($post_status)) {
            $this->redirects_api->delete($redirect->from);
            return;
        }

        $this->redirects_api->update($redirect);
    }

    /**
     * Update post title to reflect redirect
     *
     * The title will be updated to the following format:
     * [from] -› [to]
     *
     * @param int $post_id
     * @return void
     */
    public function update_redirect_post_title(int $post_id)
    {
        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }
        $post_status = get_post_status($post_id);
        if (!$post_status || !is_post_status_viewable($post_status)) {
            return;
        }

        // Prevent loop, because this action updates the current post
        remove_action("save_post", [$this, __FUNCTION__]);

        $from = get_field("from", $post_id);
        $to = get_field("to", $post_id);

        wp_update_post([
            "ID" => $post_id,
            "post_title" => $from . " &#8594; " . $to,
        ]);
    }

    public function check_dependencies(): void
    {
        /** @var array<int, string> $active_plugins */
        $active_plugins = get_option("active_plugins") ?: [];
        $missing_plugins = array_diff(self::REQUIRED_PLUGINS, $active_plugins);

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
                self::NAME,
                implode(", ", $missing_plugins)
            );
            echo '<div class="notice notice-error"><p>' .
                $message .
                "</p></div>";
        };
    }
}
