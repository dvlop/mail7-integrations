# Mail7 (.NET)

Official [Mail7](https://mail7.net) email validation client for .NET (netstandard2.0).

```bash
dotnet add package Mail7
```

```csharp
using Mail7;

var client = new Mail7Client(apiKey: "..."); // apiKey optional (free anonymous tier without it)

var result = await client.ValidateAsync("user@example.com");
Console.WriteLine(result.Status);       // "Valid" | "Not Valid" | "Unknown"
Console.WriteLine(result.Valid);        // true | false | null (null when Unknown)
Console.WriteLine(result.IsDisposable); // true for throwaway addresses
```

## Honest classification

`Status` is `Valid`, `Not Valid`, or `Unknown`. `Valid` is `null` when the address could not
be verified (catch-all, greylisting, disposable) - so you never wrongly reject a real person.
Branch on `Status`; do not treat `Unknown` as invalid.

## API

- `new Mail7Client(string? apiKey = null, string baseUrl = "https://mail7.net/api", HttpClient? httpClient = null)`
- `Task<ValidationResult> ValidateAsync(string email, CancellationToken cancellationToken = default)`
- throws `Mail7Exception` on API/transport errors.

## License

MIT
