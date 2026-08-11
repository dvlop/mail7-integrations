# Mail7 MCP server

Email validation for AI assistants. This is an [MCP](https://modelcontextprotocol.io)
server that gives Claude, Cursor, Windsurf, Cline and any other MCP client four tools:
verify a single address, clean a list, check a domain's SPF record, and audit a
domain's whole mail configuration.

Honest by design: Mail7 answers **Valid**, **Not Valid** or **Unknown**, and the tool
descriptions tell the model in plain words that Unknown means "could not be verified",
not "bad". An assistant using this server will not silently delete addresses it could
not check.

## Install

Node 18+ required. No install step - the server runs through `npx`.

**Claude Code**

```bash
claude mcp add mail7 --env MAIL7_API_KEY=your_key -- npx -y mail7-mcp
```

**Claude Desktop** (`claude_desktop_config.json`), **Cursor** (`~/.cursor/mcp.json`),
and most other clients use the same shape:

```json
{
  "mcpServers": {
    "mail7": {
      "command": "npx",
      "args": ["-y", "mail7-mcp"],
      "env": { "MAIL7_API_KEY": "your_key" }
    }
  }
}
```

Get an API key at [mail7.net/account](https://mail7.net/account/). The server also runs
without one, but anonymous callers are limited to 5 checks per minute and a 25-address
free bulk sample - not enough for real work.

| Variable | Default | Purpose |
|---|---|---|
| `MAIL7_API_KEY` | - | Raises rate limits, unlocks full bulk validation |
| `MAIL7_BASE_URL` | `https://mail7.net/api` | Override the API endpoint |

## Tools

| Tool | What it does |
|---|---|
| `validate_email` | One address: syntax, MX, disposable-domain check, live SMTP mailbox probe |
| `validate_emails` | Up to 50 addresses per call, with a Valid / Not Valid / Unknown summary |
| `check_domain` | Full mail-config audit graded A-F: MX, SPF, DKIM, DMARC, MTA-STS, TLS-RPT, DNSSEC, BIMI |
| `check_spf` | SPF record, mechanisms, lookup count and misconfigurations |

Ask your assistant things like:

- "Is `sales@acme.com` a real mailbox?"
- "Clean this list of signups and tell me which ones bounce."
- "Why do our emails to customers land in spam? Check `ourdomain.com`."

## How results are shaped

`validate_email` and `validate_emails` return structured content alongside the text, so
a client that supports structured tool output gets typed fields:

```jsonc
{
  "email": "user@example.com",
  "status": "Unknown",        // "Valid" | "Not Valid" | "Unknown"
  "valid": null,              // true | false | null (null = Unknown)
  "formatValid": true,
  "mxValid": true,
  "smtpValid": false,
  "is_disposable": false,
  "details": "Server accepts all addresses (catch-all) - status unknown"
}
```

`check_domain` returns a compact graded summary with a fix for every problem. To see the
raw records of one part, call it again with `section` set to `mx`, `spf`, `dkim`,
`dmarc`, `mta_sts`, `tls_rpt`, `dnssec`, `bimi` or `domain`.

## Notes for large lists

Every address is a live SMTP conversation, so a check takes seconds, not milliseconds.
`validate_emails` caps at 50 addresses per call on purpose - call it repeatedly with the
next chunk rather than pushing a whole file through one call, and never run two calls in
parallel (the API serialises one bulk job per client).

## Development

```bash
npm install
npm run build      # -> dist/
node dist/index.js # speaks MCP over stdio
```

## License

MIT. Part of the [Mail7 integrations](https://github.com/dvlop/mail7-integrations).
