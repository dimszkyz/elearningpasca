<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascasync;

/**
 * Accumulates a safe user sync summary.
 *
 * Password hashes and tokens must never be added to this object.
 *
 * @package    local_pascasync
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sync_result {
    public int $total = 0;
    public int $created = 0;
    public int $updated = 0;
    public int $unchanged = 0;
    public int $conflicts = 0;
    public int $failed = 0;

    /** @var array<int, array<string, string|int>> Safe issue details. */
    private array $issues = [];

    /**
     * Record a successful status.
     *
     * @param string $status created, updated, or unchanged.
     */
    public function record_status(string $status): void {
        $this->total++;
        if ($status === 'created') {
            $this->created++;
        } else if ($status === 'updated') {
            $this->updated++;
        } else {
            $this->unchanged++;
        }
    }

    /**
     * Record a conflict without sensitive values.
     *
     * @param array $source Source user payload.
     * @param string $message Safe explanation.
     */
    public function record_conflict(array $source, string $message): void {
        $this->total++;
        $this->conflicts++;
        $this->add_issue('conflict', $source, $message);
    }

    /**
     * Record a failure without sensitive values.
     *
     * @param array $source Source user payload.
     * @param string $message Safe explanation.
     */
    public function record_failure(array $source, string $message): void {
        $this->total++;
        $this->failed++;
        $this->add_issue('failed', $source, $message);
    }

    /**
     * Return issue details for display.
     *
     * @return array<int, array<string, string|int>>
     */
    public function get_issues(): array {
        return $this->issues;
    }

    /**
     * Add one issue, capped to avoid excessive page output.
     */
    private function add_issue(string $type, array $source, string $message): void {
        if (count($this->issues) >= 100) {
            return;
        }

        $this->issues[] = [
            'type' => $type,
            'sourceid' => (int) ($source['source_id'] ?? 0),
            'name' => clean_param((string) ($source['name'] ?? ''), PARAM_TEXT),
            'email' => clean_param((string) ($source['email'] ?? ''), PARAM_EMAIL),
            'message' => clean_param($message, PARAM_TEXT),
        ];
    }
}
