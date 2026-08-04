# Mail7 Email Validation - Mautic plugin

Validates contact email addresses through the [Mail7](https://mail7.net) API and marks
undeliverable ones as **Do Not Contact**, keeping your contacts clean without excluding real
people. For Mautic 5.x.

**Honest by design.** Mail7 returns **Valid**, **Not Valid**, or **Unknown**. Only **Not
Valid** addresses (do not exist / no mail server) are marked Do Not Contact. **Unknown**
addresses (catch-all, greylisting, disposable) stay contactable - so you never wrongly
exclude a genuine contact. If Mail7 is briefly unreachable, nothing is changed (fail-open).

## How it works

A subscriber on `LeadEvents::LEAD_POST_SAVE` validates the contact's email via Mail7. On a
**Not Valid** result it calls the DoNotContact model to add an email DNC entry (reason
*unsubscribed*), so campaigns skip the contact. Works on the free anonymous tier out of the
box; add an API key for higher volume.

## Install

1. Copy `plugins/MauticMail7Bundle/` into your Mautic `plugins/` directory.
2. Clear the cache: `php bin/console cache:clear`.
3. Mautic → **Settings → Plugins → Install/Upgrade Plugins** (reloads plugin config).

## Configuration

The plugin reads three parameters (defaults in `Config/config.php`):

- `mail7_api_key` - optional; free anonymous tier without it.
- `mail7_base_url` - `https://mail7.net/api`.
- `mail7_block_unknown` - `false` (recommended; keep off to avoid excluding real people).

To set your API key, add it to `app/config/local.php`:

```php
'mail7_api_key' => 'mk_live_xxx',
```

(A settings-page form can be added later; v0.1 uses config parameters.)

## Compatibility note

Built against the Mautic 5.x source (exact `LeadEvents::LEAD_POST_SAVE`, `DoNotContact`
model `addDncForContact()`, `DNC::UNSUBSCRIBED`, `mautic.helper.core_parameters`). The core
validation client is tested; the Symfony wiring could not be booted here, so on install
please verify:

- the DoNotContact model service id `mautic.lead.model.dnc` matches your build,
- the `parameters` are picked up (config cache cleared after install).

## License

MIT
