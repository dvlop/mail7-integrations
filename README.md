# Mail7 integrations

Official integrations, plugins and SDKs for **[Mail7](https://mail7.net)** email validation.

Honest by design: every integration surfaces Mail7's three-way result - **Valid**, **Not
Valid**, or **Unknown** - so an address that simply cannot be confirmed (catch-all,
greylisting, disposable) is never wrongly rejected as invalid.

## SDKs

| Language | Folder | Package |
|----------|--------|---------|
| JavaScript / TypeScript | [`sdk/js`](sdk/js) | npm `mail7` |
| Python | [`sdk/python`](sdk/python) | PyPI `mail7` |
| PHP | [`sdk/php`](sdk/php) | Packagist `mail7/mail7` |
| .NET | [`sdk/dotnet`](sdk/dotnet) | NuGet `Mail7` |

## Plugins, apps & scripts

| Platform | Folder | What it does |
|----------|--------|--------------|
| WordPress | [`wordpress`](wordpress) | Real-time validation on registration, comments, Contact Form 7, WPForms |
| MailWizz | [`mailwizz`](mailwizz) | Validate subscribers on subscribe/import |
| phpList | [`phplist`](phplist) | Validate on subscribe/import (`validateEmailAddress` hook) |
| Mautic | [`mautic`](mautic) | Validate contacts; Not Valid → Do Not Contact |
| listmonk | [`listmonk`](listmonk) | CLI list-cleaner (fetch → validate → blocklist) |
| Google Sheets | [`google-sheets`](google-sheets) | `=MAIL7()` + "Validate column" menu (Apps Script) |
| n8n | [`n8n`](n8n) | Community node |
| Activepieces | [`activepieces`](activepieces) | Piece (action) |
| Pipedream | [`pipedream`](pipedream) | Component (action) |
| Zapier | [`zapier`](zapier) | Platform app (Validate Email action) |
| Make (Integromat) | [`make`](make) | Custom app config blocks |
| Postman | [`postman`](postman) | Public API collection |

## API

`POST https://mail7.net/api/validate-single` with `{ "email": "..." }`. The `X-API-Key` header
is optional (free anonymous tier without it). See [`openapi.json`](openapi.json).

## License

MIT
