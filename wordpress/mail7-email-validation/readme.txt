=== Mail7 Email Validation ===
Contributors: mail7
Tags: email validation, email verification, spam, registration, contact form 7
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Real-time email validation for registration, comments and forms via the Mail7 API. Blocks fake addresses - and never blocks one it cannot verify.

== Description ==

Mail7 Email Validation checks email addresses in real time as they are entered, using the [Mail7](https://mail7.net/) API. It stops fake and undeliverable addresses from getting into your user base, comments and form submissions - which means fewer bounces, less spam and cleaner lists.

**Honest by design.** Most validators force every address into "valid" or "invalid". Real mail servers are not that simple: catch-all domains, greylisting and disposable providers make some addresses genuinely unverifiable. Mail7 returns three honest verdicts - **Valid**, **Not Valid**, and **Unknown** - and this plugin **does not block Unknown addresses by default**, so you never turn away a real person over an address that simply could not be confirmed.

= What it checks =

* Syntax / format
* Domain MX records (can the domain receive mail at all)
* Live SMTP mailbox check
* Disposable / temporary address detection

= Where it plugs in =

* WordPress user registration
* Comment author emails
* Contact Form 7 email fields
* WPForms email fields

= Reliable by default =

* Results are cached, so the same address is never re-checked (fast, and no wasted API calls).
* If the Mail7 API is briefly unreachable, the plugin fails open - your forms keep working, no one is ever locked out by an outage.

= API key =

Works out of the box on Mail7's free anonymous tier (rate-limited). For higher volume, paste a Mail7 API key in the settings - get one at [mail7.net](https://mail7.net/).

== External services ==

This plugin sends each submitted email address to the Mail7 API (https://mail7.net/api/validate-single) to determine whether it is deliverable. Only the email address being validated is sent. See the Mail7 [Privacy Policy](https://mail7.net/privacy-policy.html) and [Terms of Service](https://mail7.net/terms-of-service.html).

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`, or install it from the Plugins screen.
2. Activate it.
3. Go to **Settings -> Mail7 Validation** to choose where validation runs and, optionally, add your API key.

== Frequently Asked Questions ==

= Do I need a paid account? =
No. It works on the free anonymous tier. A Mail7 API key raises the rate limit and volume.

= Will it block real users? =
Not for unverifiable addresses. By default only addresses Mail7 reports as **Not Valid** (do not exist / no mail server) are blocked. "Unknown" addresses are allowed. You can optionally block Unknown too, but it is off by default on purpose.

= What happens if Mail7 is down? =
The plugin fails open: submissions are allowed. Validation never becomes a single point of failure for your forms.

== Changelog ==

= 1.0.0 =
* Initial release: registration, comments, Contact Form 7 and WPForms validation with honest Valid / Not Valid / Unknown handling, result caching and fail-open behavior.
