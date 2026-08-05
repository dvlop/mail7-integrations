# Mail7 Email Validation for Gravity Forms

A [Gravity Forms](https://www.gravityforms.com/) add-on that validates email fields in real
time with [Mail7](https://mail7.net). Built on the official Gravity Forms Add-On Framework, so
it hooks `gform_field_validation` and adds a settings tab under **Forms, Settings, Mail7**.

Honest by design: every check returns **Valid**, **Not Valid**, or **Unknown**. Only confirmed
**Not Valid** addresses are blocked. **Unknown** (catch-all, greylisting, disposable) is allowed
through by default, so real people are never wrongly rejected. If Mail7 is unreachable the add-on
fails open and the form submits normally.

## Install

1. Ensure Gravity Forms is active.
2. Upload the `mail7-gravity-forms` folder to `wp-content/plugins/` (or install the zip via
   Plugins, Add New, Upload Plugin) and activate it.
3. Go to **Forms, Settings, Mail7** and optionally enter your Mail7 API key.

Every email field on every form is then validated on submit.

## Settings

- **Mail7 API key** - optional. Single checks work anonymously; a key lifts the anonymous rate
  limit for busy forms.
- **Also block "Unknown" results** - off by default. Leave it off to keep unverifiable-but-possibly-real
  addresses; turn it on only if you want strict rejection.

## Files

- `mail7-gravity-forms.php` - plugin bootstrap; registers the add-on on `gform_loaded`.
- `class-gf-mail7.php` - the `GFAddOn` subclass: settings fields, `gform_field_validation` hook,
  cached fail-open Mail7 client.
- `uninstall.php` - removes settings and cached transients on uninstall.

## API

`POST https://mail7.net/api/validate-single` with `{ "email": "..." }`. See
https://mail7.net/api-docs.html and the OpenAPI spec in
[`../openapi.json`](https://github.com/dvlop/mail7-integrations/blob/main/openapi.json).

## License

GPLv2 or later (matching Gravity Forms and WordPress).
