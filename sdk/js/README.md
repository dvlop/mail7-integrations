# mail7net (JavaScript / TypeScript)

Official [Mail7](https://mail7.net) email validation client.

```bash
npm install mail7net
```

```ts
import { Mail7 } from 'mail7net';

const mail7 = new Mail7({ apiKey: process.env.MAIL7_API_KEY }); // apiKey optional

const r = await mail7.validate('user@example.com');
console.log(r.status);        // "Valid" | "Not Valid" | "Unknown"
console.log(r.valid);         // true | false | null (null when Unknown)
console.log(r.is_disposable); // true for throwaway addresses
```

## Honest classification

`status` is `Valid`, `Not Valid`, or `Unknown`. `valid` is `null` when the address could
not be verified (catch-all, greylisting, disposable) - so you never wrongly reject a real
person. Branch on `status`; do not treat `Unknown` as invalid.

## API

- `new Mail7({ apiKey?, baseUrl?, fetch? })` - `apiKey` is optional (free anonymous tier without it).
- `validate(email): Promise<ValidationResult>`

Requires Node 18+ (global `fetch`) or pass a `fetch` implementation.

## License

MIT
