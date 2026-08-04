# @activepieces/piece-mail7

[Mail7](https://mail7.net) email validation piece for [Activepieces](https://www.activepieces.com).

Validate email addresses in your flows. Mail7 returns an **honest** verdict - **Valid**,
**Not Valid**, or **Unknown** - so you never wrongly reject a real address just because the
mailbox could not be confirmed (catch-all, greylisting, disposable).

## Action

**Validate Email** - input an `Email`, returns the Mail7 result:

```json
{
  "email": "user@example.com",
  "valid": true,
  "status": "Valid",
  "is_disposable": false
}
```

- **status**: `Valid` / `Not Valid` / `Unknown`.
- **valid**: `true` deliverable, `false` does not exist, `null` when Unknown (branch on `status`).

## Auth

The Mail7 API key is **optional** - leave the connection empty to use the free anonymous
tier, or add a key for higher volume. Get one at [mail7.net](https://mail7.net).

## Publishing

Community pieces ship via a pull request to the
[activepieces/activepieces](https://github.com/activepieces/activepieces) monorepo
(`packages/pieces/community/mail7`). This package is the piece source, ready to drop in.

## License

MIT
