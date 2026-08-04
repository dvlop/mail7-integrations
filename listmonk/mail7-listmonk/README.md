# mail7-listmonk

Validate a [listmonk](https://listmonk.app) audience with [Mail7](https://mail7.net) and
blocklist undeliverable addresses. listmonk has no plugin system, so this is a small CLI that
uses listmonk's REST API + Mail7 to clean a list.

**Honest by design.** Only addresses Mail7 reports as **Not Valid** (do not exist / no mail
server) are blocklisted. **Unknown** addresses (catch-all, greylisting, disposable) are kept
unless you pass `--block-unknown` - so you never wrongly remove a real subscriber. Fails open:
a Mail7 hiccup never blocklists anyone.

## Usage

Requires Node 18+.

```bash
export LISTMONK_URL="https://listmonk.example.com"
export LISTMONK_USERNAME="api_user"
export LISTMONK_TOKEN="your-access-token"
export MAIL7_API_KEY="mk_live_..."   # optional (free anonymous tier without it)

node index.mjs --list 3 --dry-run    # preview
node index.mjs --list 3              # blocklist undeliverable
```

### Options

- `--list <id>` - only this list (repeatable filter; omit to check all subscribers).
- `--block-unknown` - also blocklist Unknown addresses (off by default; keeps real people).
- `--dry-run` - report what would happen, change nothing.
- `--per-page <n>` - page size when fetching (default 100).

Run it on a schedule (cron) to keep a list clean.

## How it works

Pages through `GET /api/subscribers`, validates each via Mail7, then blocklists the
undeliverable ones in batches via `PUT /api/subscribers/blocklist`. listmonk auth is HTTP
Basic (`username:token`).

## License

MIT
