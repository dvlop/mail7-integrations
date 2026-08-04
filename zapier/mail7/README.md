# Mail7 for Zapier

[Mail7](https://mail7.net) email validation as a Zapier integration (Zapier Platform CLI app).

## Action

**Validate Email** - input an `Email`, returns the Mail7 result: `status`
(Valid / Not Valid / Unknown), `valid` (true / false / empty when Unknown), `is_disposable`,
and the format/MX/SMTP flags.

**Honest classification:** Unknown means the address could not be verified (catch-all,
greylisting, disposable) - branch on `status`, do not treat Unknown as invalid.

## Auth

The Mail7 **API Key** is optional - connect with an empty key to use the free anonymous tier,
or add a key for higher volume. Get one at [mail7.net](https://mail7.net).

## Develop / publish

Built with the [Zapier Platform CLI](https://docs.zapier.com/platform/reference/cli-docs):

```bash
npm install
zapier register "Mail7"   # first time, in your Zapier account
zapier push
```

Then submit for review to publish in the Zapier app directory.

## License

MIT
