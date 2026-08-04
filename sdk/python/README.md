# mail7 (Python)

Official [Mail7](https://mail7.net) email validation client. Zero dependencies (stdlib only).

```bash
pip install mail7
```

```python
from mail7 import Mail7

client = Mail7(api_key="...")   # api_key optional (free anonymous tier without it)

result = client.validate("user@example.com")
print(result["status"])         # "Valid" | "Not Valid" | "Unknown"
print(result["valid"])          # True | False | None (None when Unknown)
print(result["is_disposable"])  # True for throwaway addresses
```

## Honest classification

`status` is `Valid`, `Not Valid`, or `Unknown`. `valid` is `None` when the address could not
be verified (catch-all, greylisting, disposable) - so you never wrongly reject a real person.
Branch on `status`; do not treat `Unknown` as invalid.

## API

- `Mail7(api_key=None, base_url="https://mail7.net/api", timeout=20)`
- `validate(email) -> dict`
- raises `Mail7Error` on API/transport errors.

## License

MIT
