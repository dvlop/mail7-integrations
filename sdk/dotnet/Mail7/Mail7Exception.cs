using System;

namespace Mail7
{
    /// <summary>Thrown when the Mail7 API returns an error or the request fails.</summary>
    public class Mail7Exception : Exception
    {
        public Mail7Exception(string message) : base(message)
        {
        }

        public Mail7Exception(string message, Exception inner) : base(message, inner)
        {
        }
    }
}
