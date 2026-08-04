# Mail7 Email Validation - Google Sheets add-on

Validate email addresses right inside Google Sheets with [Mail7](https://mail7.net), via
Google Apps Script.

**Honest classification:** results are **Valid**, **Not Valid**, or **Unknown**. Unknown means
the address could not be verified (catch-all, greylisting, disposable) - not the same as
invalid - so you never wrongly flag a real person.

## Two ways to use it

- **Cell formula:** `=MAIL7(A2)` -> `Valid` / `Not Valid` / `Unknown`.
- **Menu:** select a column of emails, then **Mail7 -> Validate selected column**. It writes the
  status (with a `(disposable)` note where relevant) to the column on the right.

The Mail7 **API key** is optional - the free anonymous tier works without one. For validating
many rows, set a key via **Mail7 -> Set API key** to avoid the anonymous rate limit.

## Install (Apps Script)

1. In a Google Sheet: **Extensions -> Apps Script**.
2. Paste `Code.gs` into the editor; set the manifest (`appsscript.json`) via
   Project Settings -> "Show appsscript.json".
3. Reload the sheet - a **Mail7** menu appears; `=MAIL7(...)` works as a formula.

## Publish (optional)

To list it on the Google Workspace Marketplace, package it as a Workspace add-on (add the
Marketplace SDK config + store listing in Google Cloud) - this requires Google's OAuth
verification. The script here is the working core.

## Note

Apps Script runs only inside Google's environment, so this could not be executed locally;
the script is syntax-checked and uses the standard Apps Script APIs (`UrlFetchApp`,
`SpreadsheetApp`, `PropertiesService`). The Mail7 request/response is the stable, tested API.
Try it in Apps Script before publishing.

## License

MIT
