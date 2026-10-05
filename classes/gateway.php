<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace smsgateway_email;

use core_sms\message;
use core_sms\message_status;
use core_user;
use stdClass;

/**
 * Fake SMS gateway which emails the recipient instead of sending a real SMS.
 *
 * This gateway is intended for testing and development environments only. It does not send any
 * real SMS messages, and should never be enabled on a production site. Instead, the content of
 * the message is sent to the recipient's email address.
 *
 * The recipient is resolved in the following order:
 * - If the message specifies a Moodle user id, that user is used.
 * - Otherwise, the recipient's mobile number is matched against the phone1/phone2 fields of
 *   Moodle user accounts.
 * - If no matching user can be found, the message is instead emailed to the primary admin user.
 *
 * @package    smsgateway_email
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_sms\gateway {
    #[\Override]
    public function send(message $message): message {
        $user = null;
        $usedfallback = false;

        if ($message->recipientuserid) {
            $user = core_user::get_user($message->recipientuserid);
            if (!$user || $user->deleted) {
                $user = null;
            }
        }

        if (!$user) {
            $user = $this->find_user_by_mobile($message->recipientnumber);
        }

        if (!$user) {
            // We could not work out which user this message belongs to from their mobile number.
            // Fall back to emailing the primary admin instead, so that the message is not lost.
            $user = get_admin();
            $usedfallback = true;
        }

        if (!$user) {
            return $message->with(
                status: message_status::GATEWAY_FAILED,
            );
        }

        $subject = get_string(
            $usedfallback ? 'emailsubjectfallback' : 'emailsubject',
            'smsgateway_email',
        );
        $body = get_string('emailbody', 'smsgateway_email', [
            'recipientnumber' => $message->recipientnumber,
            'content' => $message->content,
        ]);

        $sent = email_to_user(
            user: $user,
            from: core_user::get_noreply_user(),
            subject: $subject,
            messagetext: $body,
        );

        return $message->with(
            status: $sent ? message_status::GATEWAY_SENT : message_status::GATEWAY_FAILED,
        );
    }

    #[\Override]
    public function get_send_priority(message $message): int {
        if ($this->config && property_exists($this->config, 'priority')) {
            return (int) $this->config->priority;
        }

        return 1;
    }

    /**
     * Find a Moodle user whose phone1 or phone2 field matches the given mobile number.
     *
     * Numbers are compared after stripping all non-numeric characters, so that differences in
     * formatting (spaces, dashes, a leading +, etc) do not prevent a match.
     *
     * @param string $recipientnumber The mobile number to search for
     * @return stdClass|null The matching user record, or null if none could be found
     */
    protected function find_user_by_mobile(string $recipientnumber): ?stdClass {
        global $DB;

        $target = $this->normalise_number($recipientnumber);
        if ($target === '') {
            return null;
        }

        $candidates = $DB->get_records_select(
            'user',
            "deleted = 0 AND (phone1 <> '' OR phone2 <> '')",
        );

        foreach ($candidates as $candidate) {
            foreach ([$candidate->phone1, $candidate->phone2] as $phone) {
                if ($phone !== '' && $this->normalise_number($phone) === $target) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Strip a phone number down to just its numeric digits, to allow for formatting differences.
     *
     * @param string $number The phone number to normalise
     * @return string The normalised phone number
     */
    protected function normalise_number(string $number): string {
        return preg_replace('/[^0-9]/', '', $number);
    }
}
