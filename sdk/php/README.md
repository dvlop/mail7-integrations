# mail7/mail7 (PHP)

Official [Mail7](https://mail7.net) email validation client.

```bash
composer require mail7/mail7
```

```php
use Mail7\Mail7;

$mail7 = new Mail7($apiKey); // $apiKey optional (free anonymous tier without it)

$result = $mail7->validate('user@example.com');
echo $result['status'];          // "Valid" | "Not Valid" | "Unknown"
var_dump($result['valid']);      // true | false | null (null when Unknown)
var_dump($result['is_disposable']); // true for throwaway addresses
```

## Honest classification

`status` is `Valid`, `Not Valid`, or `Unknown`. `valid` is `null` when the address could not
be verified (catch-all, greylisting, disposable) - so you never wrongly reject a real person.
Branch on `status`; do not treat `Unknown` as invalid.

## API

- `new Mail7(?string $apiKey = null, string $baseUrl = 'https://mail7.net/api', int $timeout = 20)`
- `validate(string $email): array`
- throws `Mail7\Mail7Exception` on API/transport errors.

Requires PHP 7.4+ with the `curl` and `json` extensions.

## License

MIT
