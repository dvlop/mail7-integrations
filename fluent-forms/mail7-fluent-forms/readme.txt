=== Mail7 Email Validation for Fluent Forms ===
Contributors: mail7
Tags: fluent forms, email validation, email verification, form validation, spam
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Real-time email validation for Fluent Forms email fields, powered by Mail7. Blocks only confirmed-invalid addresses and never rejects one it cannot verify.

== Description ==

This add-on validates every Fluent Forms email field the moment the form is submitted, using the [Mail7](https://mail7.net) API (format, MX records and a live SMTP mailbox check).

Unlike most validators, it is honest about uncertainty. Mail7 returns one of three results:

* **Valid** - the mailbox is confirmed. The submission goes through.
* **Not Valid** - confirmed invalid. This is the only result that is blocked.
* **Unknown** - the mailbox could not be confirmed (catch-all domain, greylisting, disposable). Allowed through by default, so real people are never wrongly rejected.

Other features:

* Fail-open: if Mail7 is unreachable, the form submits normally. A validation outage never locks users out.
* Optional strict mode to also block Unknown results (off by default).
* Repeated addresses are cached, so submissions stay fast.
* Settings live under Settings, Mail7 (Fluent Forms).

The Mail7 API key is optional. Single checks work anonymously; add a key to lift the anonymous rate limit for high-traffic forms. You are only charged for definite Valid and Not Valid results; Unknown checks are always free.

== Installation ==

1. Make sure Fluent Forms is installed and active.
2. Upload the plugin to /wp-content/plugins/ or install the zip via Plugins, Add New, Upload Plugin.
3. Activate the plugin.
4. Go to Settings, Mail7 (Fluent Forms) and (optionally) enter your Mail7 API key.

Every email field on every Fluent Form is now validated on submit.

== Frequently Asked Questions ==

= Does it block real users with unusual mailboxes? =

No. Catch-all, greylisted or disposable addresses return Unknown and are allowed through by default, never rejected.

= What happens if Mail7 is unreachable? =

The add-on is fail-open: on any API error the submission proceeds normally.

= Do I need a Mail7 account? =

No. Single checks are free and need no account. A paid plan adds API volume for high-traffic forms.

== Changelog ==

= 1.0.0 =
* Initial release: validate Fluent Forms email fields via Mail7, honest Valid / Not Valid / Unknown, fail-open, optional strict mode.
