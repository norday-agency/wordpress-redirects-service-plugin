<?php namespace Grrr\Redirects\WordPress\Models;

use WP_Post;

class Redirect
{
    const FROM_FIELD_NAME = "from";
    const FROM_FIELD_ID = "field_64623e1f46602";
    const TO_FIELD_ID = "field_646240f646603";
    const PERMANENTLY_FIELD_ID = "field_646241d746604";

    const POST_TYPE = "grrr-redirect";

    const ORIGINAL_FROM_META_KEY = "_original_from";

    public function __construct(
        public string $from,
        public string $to,
        public bool $permanently,
        public ?string $_original_from = null
    ) {
    }

    public static function from_post_id(int $post_id): ?self
    {
        $from = get_field("from", $post_id);
        $to = get_field("to", $post_id);
        $permanently = get_field("permanently", $post_id);
        if (!$from) {
            return null;
        }

        /**
         * @var string|null $original_from
         */
        $original_from = get_post_meta(
            $post_id,
            self::ORIGINAL_FROM_META_KEY,
            true
        );
        return new self($from, $to, $permanently, $original_from);
    }

    public function insert_or_update(): int
    {
        $post_id = $this->get_post_id_by_from($this->from);
        $post_id = wp_insert_post([
            'ID' => $post_id,
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $this->from,
        ]);

        $original_from = get_field(self::ORIGINAL_FROM_META_KEY, $post_id);
        if (!$original_from) {
            update_post_meta($post_id, self::ORIGINAL_FROM_META_KEY, $this->from);
        }

        update_field(self::FROM_FIELD_ID, $this->from, $post_id);
        update_field(self::TO_FIELD_ID, $this->to, $post_id);
        update_field(self::PERMANENTLY_FIELD_ID, $this->permanently, $post_id);

        // Save post again to trigger dynamic title update
        return wp_update_post([
            'ID' => $post_id,
        ]);
    }


    protected function get_post_id_by_from(string $from): int {
        $query = new \WP_Query([
            'post_type' => self::POST_TYPE,
            'meta_query' => [
                [
                    'key' => self::FROM_FIELD_NAME,
                    'value' => $from,
                ],
            ],
        ]);
        $post = $query->posts[0] ?? null;
        return $post->ID ?? 0;
    }
}
