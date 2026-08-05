# Mail7 Email Validation for Fluent Forms

A [Fluent Forms](https://fluentforms.com/) add-on that validates email fields in real time with
[Mail7](https://mail7.net). It hooks Fluent Forms' own field validation filter
(`fluentform/validate_input_item_input_email`) and adds a settings page under
**Settings, Mail7 (Fluent Forms)**.

Honest by design: every check returns **Valid**, **Not Valid**, or **Unknown**. Only confirmed
**Not Valid** addresses are blocked. **Unknown** (catch-all, greylisting, disposable) is allowed
through by default, so real people are never wrongly rejected. If Mail7 is unreachable the add-on
fails open and the form submits normally.

## Install

1. Ensure Fluent Forms is active.
2. Upload the `mail7-fluent-forms` folder to `wp-content/plugins/` (or install the zip via
   Plugins, Add New, Upload Plugin) and activate it.
3. Go to **Settings, Mail7 (Fluent Forms)** and optionally enter your Mail7 API key.

Every email field on every Fluent Form is then validated on submit.

## Settings

- **Mail7 API key** - optional. Single checks work anonymously; a key lifts the anonymous rate
  limit for busy forms.
- **Strict mode** - off by default. Turn it on to also block Unknown results.

## Files

- `mail7-fluent-forms.php` - the plugin: Mail7 client (cached, fail-open), the
  `fluentform/validate_input_item_input_email` filter, and the settings page.
- `uninstall.php` - removes settings and cached transients on uninstall.

## API

`POST https://mail7.net/api/validate-single` with `{ "email": "..." }`. See
https://mail7.net/api-docs.html and the OpenAPI spec in
[`../openapi.json`](https://github.com/dvlop/mail7-integrations/blob/main/openapi.json).

## License

GPLv2 or later (matching Fluent Forms and WordPress).
