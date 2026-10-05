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

/**
 * Strings for component smsgateway_email, language 'en'.
 *
 * @package    smsgateway_email
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['emailbody'] = 'This is a test SMS message which would have been sent to {$a->recipientnumber}:

{$a->content}';
$string['emailinformation'] = 'This gateway does not send real SMS messages. Instead, the message content is emailed to the Moodle user matching the recipient\'s mobile number. If no matching user can be found, the message is emailed to the primary admin instead. It is intended for testing and development purposes only and should not be enabled on a production site.';
$string['emailsubject'] = 'Test SMS message';
$string['emailsubjectfallback'] = 'Test SMS message (recipient not found)';
$string['pluginname'] = 'Fake (email)';
$string['privacy:metadata'] = 'The fake email SMS gateway plugin does not store any personal data.';
