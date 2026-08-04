# Mail7 - Make (Integromat) custom app

An email-validation action for [Make](https://www.make.com). Make apps are built in the
**Make Developer Platform** (Apps Editor) by pasting JSON into each tab; this folder contains
those JSON blocks, ready to paste. Then you publish/submit the app for review.

**Honest classification:** the result's `status` is Valid / Not Valid / Unknown, and `valid`
is empty when Unknown (catch-all, greylisting, disposable) - branch on `status`.

## Build in the Make Developer Platform

1. **Create a new app** (developers.make.com → your app), set name **Mail7**.
2. Paste each file into the matching editor tab:

| File | Editor location |
|------|-----------------|
| `base.json` | **Base** |
| `connection/parameters.json` | Connection → **Parameters** |
| `connection/communication.json` | Connection → **Communication** |
| `modules/validate-email/communication.json` | Module "Validate an Email" (type: Action) → **Communication** |
| `modules/validate-email/parameters.json` | Module → **Mappable parameters** |
| `modules/validate-email/interface.json` | Module → **Interface** |
| `modules/validate-email/samples.json` | Module → **Samples** |

3. The `X-API-Key` header is added from the connection only when a key is set (`omit()` when
   empty), so the app works on the free anonymous tier without a key.
4. Test the module in the editor, then **Publish / request review**.

## Compatibility note

Built to the Make Custom Apps schema (base + api-key connection + action module with IML
`{{connection.apiKey}}` / `{{parameters.email}}`). Make apps can only be run inside the Make
platform, so **verify each tab in the Apps Editor** and adjust field types (`email`/`text`/
`boolean`/`array`) to your Make version if the editor flags anything. The request/response
shape (POST `/validate-single`, JSON body `{email}`) is the stable Mail7 API.

## License

MIT
