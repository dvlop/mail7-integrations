# n8n-nodes-mail7

An [n8n](https://n8n.io) community node for **[Mail7](https://mail7.net) email validation**.

Verify email addresses in your n8n workflows: format, MX records, live SMTP and disposable
detection. Mail7 returns an **honest** verdict - **Valid**, **Not Valid**, or **Unknown** -
so you never wrongly drop a real contact just because their mailbox could not be confirmed
(catch-all, greylisting, disposable).

[Installation](#installation) · [Credentials](#credentials) · [Operations](#operations) · [Output](#output)

## Installation

Community nodes can be installed directly from the n8n UI:

**Settings → Community Nodes → Install**, then enter `n8n-nodes-mail7`.

(Self-hosted n8n only. Requires n8n's community-nodes feature enabled.)

## Credentials

A Mail7 API key is **optional**:

- **No credential** → uses the free anonymous tier (rate-limited).
- **Mail7 API credential** → paste your API key for higher rate limits and monthly volume. Get one at [mail7.net](https://mail7.net).

## Operations

**Validate Email** - takes an `Email` and returns the validation result.

## Output

Each item returns the Mail7 result:

```json
{
  "email": "user@example.com",
  "valid": true,
  "formatValid": true,
  "mxValid": true,
  "smtpValid": true,
  "status": "Valid",
  "mx_servers": ["gmail-smtp-in.l.google.com"],
  "is_disposable": false
}
```

- **status**: `Valid` / `Not Valid` / `Unknown`.
- **valid**: `true` deliverable, `false` does not exist, **`null` when Unknown** (could not be verified - branch on `status`, do not treat Unknown as invalid).
- **is_disposable**: `true` for throwaway/temporary addresses.

### Filtering tip

To keep only deliverable addresses, filter on `status === "Valid"`. To also keep the
unverifiable-but-plausible ones (recommended for lists you do not want to over-prune), keep
`status !== "Not Valid"`.

## License

[MIT](LICENSE)
