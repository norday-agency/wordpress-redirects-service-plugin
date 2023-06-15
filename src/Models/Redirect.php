<?php namespace Grrr\Redirects\WordPress\Models;

class Redirect
{
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

    public static function from_post_id(int $post_id): self
    {
        $from = get_field("from", $post_id);
        $to = get_field("to", $post_id);
        $permanently = get_field("permanently", $post_id);

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
}
