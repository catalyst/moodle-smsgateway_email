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

/**
 * Tests for the fake email SMS gateway.
 *
 * @package    smsgateway_email
 * @category   test
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \smsgateway_email\gateway
 */
final class gateway_test extends \advanced_testcase {
    public function test_send_emails_known_user(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user([
            'email' => 'smsrecipient@example.com',
        ]);

        $manager = \core\di::get(\core_sms\manager::class);
        $gw = $manager->create_gateway_instance(
            classname: gateway::class,
            name: 'email',
            enabled: true,
            config: (object) [],
        );

        $sink = $this->redirectEmails();

        $message = $manager->send(
            recipientnumber: '+447123456789',
            content: 'Hello, world!',
            component: 'core',
            messagetype: 'test',
            recipientuserid: $user->id,
            async: false,
        );

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertInstanceOf(message::class, $message);
        $this->assertEquals(message_status::GATEWAY_SENT, $message->status);
        $this->assertEquals($gw->id, $message->gatewayid);

        $this->assertCount(1, $messages);
        $this->assertEquals('smsrecipient@example.com', $messages[0]->to);
        $this->assertStringContainsString('+447123456789', quoted_printable_decode($messages[0]->body));
        $this->assertStringContainsString('Hello, world!', quoted_printable_decode($messages[0]->body));
    }

    public function test_send_emails_user_matched_by_mobile(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user([
            'email' => 'mobilematch@example.com',
            'phone2' => '+44 7123 456789',
        ]);

        $manager = \core\di::get(\core_sms\manager::class);
        $gw = $manager->create_gateway_instance(
            classname: gateway::class,
            name: 'email',
            enabled: true,
            config: (object) [],
        );

        $sink = $this->redirectEmails();

        // No recipientuserid is provided, so the gateway must find the user by their mobile number.
        $message = $manager->send(
            recipientnumber: '+447123456789',
            content: 'Hello, world!',
            component: 'core',
            messagetype: 'test',
            recipientuserid: null,
            async: false,
        );

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertEquals(message_status::GATEWAY_SENT, $message->status);
        $this->assertEquals($gw->id, $message->gatewayid);

        $this->assertCount(1, $messages);
        $this->assertEquals('mobilematch@example.com', $messages[0]->to);
    }

    public function test_send_falls_back_to_admin_when_user_not_found(): void {
        $this->resetAfterTest();

        $admin = get_admin();

        $manager = \core\di::get(\core_sms\manager::class);
        $gw = $manager->create_gateway_instance(
            classname: gateway::class,
            name: 'email',
            enabled: true,
            config: (object) [],
        );

        $sink = $this->redirectEmails();

        // No user has this number, and no recipientuserid is provided, so this should fall back to
        // emailing the primary admin instead.
        $message = $manager->send(
            recipientnumber: '+447999999999',
            content: 'Hello, world!',
            component: 'core',
            messagetype: 'test',
            recipientuserid: null,
            async: false,
        );

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertEquals(message_status::GATEWAY_SENT, $message->status);
        $this->assertEquals($gw->id, $message->gatewayid);

        $this->assertCount(1, $messages);
        $this->assertEquals($admin->email, $messages[0]->to);
    }

    public function test_get_send_priority(): void {
        $gw = new gateway(
            enabled: true,
            name: 'email',
            config: json_encode((object) []),
        );

        $message = new message(
            recipientnumber: '+447123456789',
            content: 'Hello, world!',
            component: 'core',
            messagetype: 'test',
            recipientuserid: null,
            issensitive: false,
        );

        $this->assertGreaterThan(0, $gw->get_send_priority($message));
    }
}
