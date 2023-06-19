<?php namespace Grrr\Redirects\WordPress\Cli;

use WP_CLI;

use Grrr\Redirects\WordPress\Models\Redirect;
use Grrr\Redirects\WordPress\RedirectsApi;

final class ImportRedirectsCommand
{
    const COMMAND = "redirects import";

    const DOMAIN_SELF = "https://www.wakkerdier.nl";

    public function __construct(protected RedirectsApi $redirects_api)
    {
    }

    public function register(): void
    {
        WP_CLI::add_command(
            self::COMMAND,
            [$this, "handle"],
            [
                "shortdesc" => "Import redirects with a csv file",
                "synopsis" => [
                    [
                        "type" => "positional",
                        "name" => "source_path",
                        "optional" => false,
                    ],
                ],
            ]
        );
    }

    public function handle(array $args, array $assoc_args): void
    {
        $source_path = $args[0];

        if (!file_exists($source_path)) {
            WP_CLI::error("Source file does not exist");
        }

        // Load spreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($source_path);

        // Get all data from the first sheet
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray();

        // Remove header row and use the value as the value for the key in the rows
        $header = array_shift($rows);
        $rows = array_map(function ($row) use ($header) {
            return array_combine($header, $row);
        }, $rows);

        // For every value, remove DOMAIN_SELF from the value
        $rows = array_map(function ($row) {
            $row["source"] = str_replace(self::DOMAIN_SELF, "", $row["source"]);
            $row["target"] = str_replace(self::DOMAIN_SELF, "", $row["target"]);
            return $row;
        }, $rows);

        array_map(function (array $row) {
            $redirect = new Redirect($row["source"], $row["target"], true);

            $redirectPostId = $redirect->insert_or_update();

            $redirect->from_post_id($redirectPostId);

            $this->redirects_api->update($redirect);
        }, $rows);
    }
}
