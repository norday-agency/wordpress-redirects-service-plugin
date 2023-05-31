<?php namespace Grrr\Redirects\WordPress;

use Grrr\Redirects\WordPress\Models\Redirect;
use WP_Http;

class RedirectsApi
{
    public function __construct(private string $api_url)
    {
    }

    public function create(Redirect $redirect): void
    {
        $client = new WP_Http();
        $client->post($this->api_url, [
            "method" => "POST",
            "headers" => [
                "Content-Type" => "application/json",
            ],
            "body" => json_encode([
                "from" => $redirect->from,
                "to" => $redirect->to,
                "permanently" => $redirect->permanently,
            ]),
        ]);
    }

    public function update(Redirect $redirect): void
    {
        if ($redirect->_original_from) {
            $this->delete($redirect->_original_from);
        }
        $this->create($redirect);
    }

    public function delete(string $from): void
    {
        $client = new WP_Http();
        $client->post($this->api_url, [
            "method" => "DELETE",
            "headers" => [
                "Content-Type" => "application/json",
            ],
            "body" => json_encode([
                "from" => $from,
            ]),
        ]);
    }
}
