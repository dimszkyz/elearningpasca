<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascasync;

/**
 * HTTP client for the Pasca Moodle user sync endpoint.
 *
 * @package    local_pascasync
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class api_client {
    /** @var string Full endpoint URL. */
    private string $apiurl;

    /** @var string Sanctum bearer token. */
    private string $apitoken;

    /** @var int Request timeout in seconds. */
    private int $timeout;

    /** @var int Number of users requested per page. */
    private int $perpage;

    /** @var bool Whether Moodle curl private-host protection is bypassed. */
    private bool $allowprivatehost;

    /**
     * Constructor.
     *
     * @param object|null $config Optional injected plugin configuration.
     */
    public function __construct(?object $config = null) {
        $config = $config ?? (object) get_config('local_pascasync');

        $this->apiurl = trim((string) ($config->apiurl ?? ''));
        $this->apitoken = trim((string) ($config->apitoken ?? ''));
        $this->timeout = max(5, min(300, (int) ($config->timeout ?? 30)));
        $this->perpage = max(1, min(100, (int) ($config->perpage ?? 100)));
        $this->allowprivatehost = !empty($config->allowprivatehost);
    }

    /**
     * Fetch a page of Pasca users.
     *
     * @param int $page Page number.
     * @param string|null $updatedafter Optional ISO-8601 incremental timestamp.
     * @return array Decoded and validated response payload.
     */
    public function fetch_page(int $page = 1, ?string $updatedafter = null): array {
        global $CFG;

        $this->validate_configuration();
        require_once($CFG->libdir . '/filelib.php');

        $curl = new \curl(['ignoresecurity' => $this->allowprivatehost]);
        $curl->setHeader([
            'Authorization: Bearer ' . $this->apitoken,
            'Accept: application/json',
            'Cache-Control: no-cache',
            'Expect:',
        ]);

        $params = [
            'page' => max(1, $page),
            'per_page' => $this->perpage,
        ];
        if ($updatedafter !== null && $updatedafter !== '') {
            $params['updated_after'] = $updatedafter;
        }

        $response = $curl->get($this->apiurl, $params, [
            'CONNECTTIMEOUT' => min(15, $this->timeout),
            'TIMEOUT' => $this->timeout,
            'RETURNTRANSFER' => true,
            'HEADER' => false,
        ]);

        if (!empty($curl->errno)) {
            throw new \moodle_exception(
                'connectionfailed',
                'local_pascasync',
                '',
                null,
                clean_param((string) $curl->error, PARAM_TEXT),
            );
        }

        $statuscode = (int) ($curl->info['http_code'] ?? 0);
        if ($statuscode !== 200) {
            throw new \moodle_exception('unexpectedstatus', 'local_pascasync', '', $statuscode);
        }

        try {
            $payload = json_decode((string) $response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \moodle_exception(
                'invalidjson',
                'local_pascasync',
                '',
                null,
                $exception->getMessage(),
            );
        }

        if (!is_array($payload) || !isset($payload['data'], $payload['meta']) || !is_array($payload['data']) ||
                !is_array($payload['meta'])) {
            throw new \moodle_exception('invalidresponse', 'local_pascasync');
        }

        $payload['meta']['current_page'] = max(1, (int) ($payload['meta']['current_page'] ?? $page));
        $payload['meta']['last_page'] = max(1, (int) ($payload['meta']['last_page'] ?? 1));
        $payload['meta']['total'] = max(0, (int) ($payload['meta']['total'] ?? count($payload['data'])));

        return $payload;
    }

    /**
     * Return the configured page size.
     *
     * @return int
     */
    public function get_per_page(): int {
        return $this->perpage;
    }

    /**
     * Validate required connection settings without exposing the token.
     */
    private function validate_configuration(): void {
        if ($this->apiurl === '' || $this->apitoken === '') {
            throw new \moodle_exception('missingconfiguration', 'local_pascasync');
        }

        $parts = parse_url($this->apiurl);
        if (!is_array($parts) || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            throw new \moodle_exception('invalidapiurl', 'local_pascasync');
        }
    }
}
