using System.Collections.Generic;
using System.Text.Json.Serialization;

namespace Mail7
{
    /// <summary>
    /// Result of validating an email address.
    /// <para>
    /// Honest classification: <see cref="Status"/> is "Valid", "Not Valid", or "Unknown",
    /// and <see cref="Valid"/> is <c>null</c> when the address could not be verified
    /// (catch-all, greylisting, disposable). Branch on <see cref="Status"/>; do not treat
    /// Unknown as invalid.
    /// </para>
    /// </summary>
    public class ValidationResult
    {
        [JsonPropertyName("email")]
        public string? Email { get; set; }

        /// <summary>true = deliverable, false = does not exist, null = Unknown.</summary>
        [JsonPropertyName("valid")]
        public bool? Valid { get; set; }

        [JsonPropertyName("formatValid")]
        public bool FormatValid { get; set; }

        [JsonPropertyName("mxValid")]
        public bool MxValid { get; set; }

        [JsonPropertyName("smtpValid")]
        public bool SmtpValid { get; set; }

        /// <summary>"Valid", "Not Valid", or "Unknown".</summary>
        [JsonPropertyName("status")]
        public string? Status { get; set; }

        [JsonPropertyName("error")]
        public string? Error { get; set; }

        [JsonPropertyName("details")]
        public string? Details { get; set; }

        [JsonPropertyName("mx_servers")]
        public List<string>? MxServers { get; set; }

        [JsonPropertyName("smtp_message")]
        public string? SmtpMessage { get; set; }

        /// <summary>true for throwaway/temporary addresses.</summary>
        [JsonPropertyName("is_disposable")]
        public bool? IsDisposable { get; set; }
    }
}
