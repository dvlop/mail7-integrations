# Mail7 - Pipedream components

[Mail7](https://mail7.net) email validation for [Pipedream](https://pipedream.com).

## Action

**Validate Email** - input an `Email`, returns the Mail7 result (honest `status`:
Valid / Not Valid / Unknown; `valid` is `null` when Unknown; `is_disposable`).

The Mail7 **API Key** prop is optional - leave it empty to use the free anonymous tier, or
add a key for higher volume. Get one at [mail7.net](https://mail7.net).

## Publishing

Community components ship via a pull request to the
[PipedreamHQ/pipedream](https://github.com/PipedreamHQ/pipedream) monorepo under
`components/mail7/`. This folder is the component source, ready to drop in.

## License

MIT
