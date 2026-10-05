# Fake (email) SMS gateway

`smsgateway_email` is a fake SMS gateway for use in **testing and development
environments only**. It does not send any real SMS messages, and must not be
enabled on a production site.

Instead of sending an SMS, the message content is emailed, so that
developers and testers can see what would have been sent without needing a
real SMS provider account.

## How it works

When a message is sent via this gateway, the recipient is resolved in the
following order:

1. If the message specifies a Moodle user id (`recipientuserid`), that user
   is used.
2. Otherwise, the recipient's mobile number is matched against the `phone1`
   and `phone2` fields of Moodle user accounts (ignoring formatting
   differences such as spaces, dashes, or a leading `+`).
3. If no matching user can be found, the message is instead emailed to the
   site's primary admin user, so that the message is not silently lost.

The email contains the phone number the message would have been sent to, and
the original message content.

## Configuration

No external configuration is required. An optional `priority` can be set in
the gateway instance configuration to control how this gateway is prioritised
relative to other configured SMS gateways.

## Warning

This plugin is intended purely as a development and testing aid. It does not
send real SMS messages, and should never be relied upon or enabled in a
production environment.
