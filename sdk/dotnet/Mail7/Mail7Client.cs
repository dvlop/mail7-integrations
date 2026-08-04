using System;
using System.Net.Http;
using System.Text;
using System.Text.Json;
using System.Threading;
using System.Threading.Tasks;

namespace Mail7
{
    /// <summary>
    /// Client for the Mail7 email validation API.
    /// </summary>
    /// <example>
    /// <code>
    /// var client = new Mail7Client();                    // apiKey optional
    /// var result = await client.ValidateAsync("user@example.com");
    /// // result.Status: "Valid" | "Not Valid" | "Unknown"
    /// </code>
    /// </example>
    public class Mail7Client
    {
        private static readonly JsonSerializerOptions JsonOptions = new JsonSerializerOptions
        {
            PropertyNameCaseInsensitive = true,
        };

        private readonly HttpClient _http;
        private readonly string _baseUrl;
        private readonly string? _apiKey;

        /// <param name="apiKey">Optional. Raises rate limits and monthly volume. Null uses the free anonymous tier.</param>
        /// <param name="baseUrl">API base URL (default https://mail7.net/api).</param>
        /// <param name="httpClient">Optional HttpClient to reuse; one is created if not supplied.</param>
        public Mail7Client(string? apiKey = null, string baseUrl = "https://mail7.net/api", HttpClient? httpClient = null)
        {
            _apiKey = apiKey;
            _baseUrl = (baseUrl ?? "https://mail7.net/api").TrimEnd('/');
            _http = httpClient ?? new HttpClient();
        }

        /// <summary>Validate a single email address.</summary>
        /// <exception cref="Mail7Exception">On an API or transport error.</exception>
        public async Task<ValidationResult> ValidateAsync(string email, CancellationToken cancellationToken = default)
        {
            var payload = JsonSerializer.Serialize(new { email });

            using (var request = new HttpRequestMessage(HttpMethod.Post, _baseUrl + "/validate-single"))
            {
                request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
                if (!string.IsNullOrEmpty(_apiKey))
                {
                    request.Headers.Add("X-API-Key", _apiKey);
                }

                HttpResponseMessage response;
                try
                {
                    response = await _http.SendAsync(request, cancellationToken).ConfigureAwait(false);
                }
                catch (Exception ex)
                {
                    throw new Mail7Exception("Mail7 request failed: " + ex.Message, ex);
                }

                var body = await response.Content.ReadAsStringAsync().ConfigureAwait(false);

                if (!response.IsSuccessStatusCode)
                {
                    throw new Mail7Exception($"Mail7 API error {(int)response.StatusCode}: {body}");
                }

                try
                {
                    var result = JsonSerializer.Deserialize<ValidationResult>(body, JsonOptions);
                    if (result == null)
                    {
                        throw new Mail7Exception("Mail7 returned invalid JSON");
                    }
                    return result;
                }
                catch (JsonException ex)
                {
                    throw new Mail7Exception("Mail7 returned invalid JSON: " + ex.Message, ex);
                }
            }
        }
    }
}
