# Mail7 email validation for Pabbly Connect

Add honest email validation to any [Pabbly Connect](https://www.pabbly.com/connect/)
workflow with [Mail7](https://mail7.net). Every check returns one of three results -
**Valid**, **Not Valid**, or **Unknown** - so an address that simply cannot be confirmed
(catch-all, greylisting, disposable) is never wrongly rejected.

Pabbly Connect does not need a dedicated Mail7 app: you call the Mail7 API directly with the
built-in **API by Pabbly** action module. This works today on any Pabbly Connect account, no
marketplace approval required.

## What you need

- A Pabbly Connect workflow with a trigger that produces an email address (a form submission,
  a new CRM lead, a new row, etc.).
- Optional: a Mail7 API key. Single checks work anonymously; add a key to lift the anonymous
  rate limit for high-volume workflows. You are only charged for definite Valid and Not Valid
  results - Unknown checks are always free.

## Set up the validation step

1. In your workflow, after the trigger, click **+** to add an **Action** step.
2. Search for and select **API by Pabbly**.
3. **Action Event:** choose **Execute API Request** (custom request).
4. **Method:** `POST`
5. **Endpoint URL:** `https://mail7.net/api/validate-single`
6. **Headers:**
   | Key | Value |
   |-----|-------|
   | `Content-Type` | `application/json` |
   | `X-API-Key` | `YOUR_MAIL7_API_KEY` (optional - omit for the free anonymous tier) |
7. **Payload Type:** `JSON`. **Body:** map the email field from your trigger:
   ```json
   { "email": "{{trigger.email}}" }
   ```
8. **Save & Send Test Request.** The response makes these fields available to later steps:

   | Field | Meaning |
   |-------|---------|
   | `status` | `Valid`, `Not Valid`, or `Unknown` - use this to branch |
   | `valid` | `true` / `false`, or `null` when `status` is `Unknown` |
   | `formatValid`, `mxValid`, `smtpValid` | the individual checks behind the result |
   | `is_disposable` | `true` for temporary/disposable domains |

## Branch on the result (the honest way)

Add a **Filter** or **Router** step after the validation step and branch on the `status` field:

- **`Not Valid`** -> route to cleanup (skip, tag, or remove). This is the only result you should
  block.
- **`Unknown`** -> keep it. Unknown means the mailbox could not be confirmed either way, not that
  the person is fake. Rejecting these turns away real people.
- **`Valid`** -> continue as normal.

A simple, safe default: add one Filter with the condition `status` **(Not equal to)** `Not Valid`,
so everything except confirmed-invalid addresses continues down the workflow.

## Notes

- **Fail-open:** if you would rather never block on a validation hiccup, branch only the explicit
  `Not Valid` out and let all other outcomes (including a missing/errored response) pass.
- The same endpoint powers every Mail7 integration. See the full API at
  https://mail7.net/api-docs.html and the OpenAPI spec in
  [`../rapidapi/openapi.json`](https://github.com/dvlop/mail7-integrations/blob/main/openapi.json).

## License

MIT
