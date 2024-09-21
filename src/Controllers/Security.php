<?php

namespace AMDarter\SimplyBackItUp\Controllers;

use AMDarter\SimplyBackItUp\Utils\Scanner;

class Security
{
    /**
     * Verify WordPress core files' integrity by comparing them against official checksums.
     *
     * @return void
     */
    public function verifyWordPressCoreChecksums(): void
    {
        $knownChecksums = Scanner::getChecksumsFromApi();

        if (empty($knownChecksums)) {
            wp_send_json_error(['message' => 'Failed to retrieve checksums from the WordPress API.']);
        }

        $mismatches = Scanner::verifyChecksums($knownChecksums);

        if (!empty($mismatches)) {
            wp_send_json_error(['message' => 'Some core files do not match the official checksums.', 'mismatches' => $mismatches]);
        } else {
            wp_send_json_success(['message' => 'All core files match the official checksums.']);
        }
    }
}
