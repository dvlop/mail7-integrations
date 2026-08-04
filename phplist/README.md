# Mail7 Email Validation - phpList plugin

Validates subscriber email addresses through the [Mail7](https://mail7.net) API, keeping
your lists clean without removing real people.

**Honest by design.** Mail7 returns **Valid**, **Not Valid**, or **Unknown**. The plugin
rejects only **Not Valid** addresses (do not exist / no mail server). **Unknown** addresses
(catch-all, greylisting, disposable) are accepted - so you never wrongly reject a genuine
subscriber. If Mail7 is briefly unreachable, addresses are accepted (fail-open), so subscribe
and import are never blocked by an outage.

It hooks phpList's `validateEmailAddress()` extension point, so it covers the public
subscribe form and imports. Works out of the box on the free anonymous tier; add an API key
for higher volume.

## Install

1. Copy `Mail7Plugin.php` and the `Mail7Plugin/` folder into your phpList `plugins/` directory
   (so you have `plugins/Mail7Plugin.php` and `plugins/Mail7Plugin/Mail7EmailValidator.php`).
2. phpList admin → **Config → Manage Plugins**, enable **Mail7 Email Validation**.
3. **Config → Settings → Mail7** to set your API key and options.

## Settings

- **mail7_api_key** - optional; free anonymous tier without it, a key raises limits/volume.
- **mail7_base_url** - `https://mail7.net/api`.
- **mail7_block_unknown** - off by default; keep it off to avoid rejecting real people.

Requires phpList 3.3+ with the `curl` extension.

## License

MIT
