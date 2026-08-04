# Mail7 Email Validation - MailWizz extension

Validates subscriber email addresses through the [Mail7](https://mail7.net) API and acts on
the result, keeping your lists clean without removing real people.

**Honest by design.** Mail7 returns **Valid**, **Not Valid**, or **Unknown**. By default only
**Not Valid** addresses (do not exist / no mail server) are acted on. **Unknown** addresses
(catch-all, greylisting, disposable) are kept - so you never wrongly drop a genuine subscriber.

## What it does

On every subscriber save (public subscribe form, customer add/import, backend add, API), the
extension validates the address via Mail7. If the result is **Not Valid** it marks the
subscriber as **unsubscribed** (default, reversible) or **blacklisted**, so campaigns skip it.
Works out of the box on the free anonymous tier; add an API key for higher volume.

- **Fail-open:** if Mail7 is briefly unreachable, the subscribe/import is never blocked.
- **No dependencies:** a small bundled cURL client (same logic as the Mail7 PHP SDK).

## Install

1. Zip the `mail7` folder (so the archive contains `mail7/Mail7Ext.php`, `mail7/common/…`, etc.).
2. MailWizz backend → **Extensions → Upload** the zip, then **Enable** it.
3. Open its **Settings** (Extensions list → Mail7 Email Validation) to add an API key and choose behavior.

## Settings

- **API key** (optional) - free anonymous tier without it; a key raises limits/volume.
- **Act on Not Valid** (recommended, on).
- **Also act on Unknown** (off by default - keep it off to avoid removing real people).
- **Action:** mark unsubscribed (reversible) or blacklisted.

## Compatibility note

MailWizz is commercial software, so this extension could not be run end to end during
development. It is built against the documented extension API (init class + `getOption`/
`setOption`, `hooks()->addAction('<app>_model_listsubscriber_aftersave', …)`, a backend
settings controller) and a reference extension. **Please verify on your MailWizz install**
and, if your version differs, adjust:

- the settings controller base class / helpers (targets MailWizz 2.x `ExtensionController`),
- the subscriber `status` value used to exclude (`unsubscribed` / `blacklisted`),
- the `allowedApps` list if your build rejects any of the values.

The core - validating via Mail7 and reading the honest verdict - is stable; the MailWizz
wiring is the part to confirm.

## License

MIT
