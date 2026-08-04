"""Official Mail7 email validation client.

Honest classification: ``status`` is "Valid" / "Not Valid" / "Unknown", and ``valid`` is
``None`` when the address could not be verified (catch-all, greylisting, disposable).
Branch on ``status``; do not treat Unknown as invalid.

Zero dependencies (standard library only).
"""

import json
import urllib.error
import urllib.request
from typing import Any, Dict, Optional

__version__ = "0.1.0"
__all__ = ["Mail7", "Mail7Error"]

DEFAULT_BASE_URL = "https://mail7.net/api"


class Mail7Error(Exception):
    """Raised when the Mail7 API returns an error or the request fails."""


class Mail7:
    """Client for the Mail7 email validation API.

    >>> from mail7 import Mail7
    >>> client = Mail7()                       # api_key optional
    >>> result = client.validate("user@example.com")
    >>> result["status"]                       # "Valid" | "Not Valid" | "Unknown"
    """

    def __init__(
        self,
        api_key: Optional[str] = None,
        base_url: str = DEFAULT_BASE_URL,
        timeout: int = 20,
    ) -> None:
        self.api_key = api_key
        self.base_url = base_url.rstrip("/")
        self.timeout = timeout

    def validate(self, email: str) -> Dict[str, Any]:
        """Validate a single email address and return the result dict.

        Keys include ``status``, ``valid`` (bool or None), ``is_disposable``, ``mx_servers``.
        """
        url = f"{self.base_url}/validate-single"
        payload = json.dumps({"email": email}).encode("utf-8")
        headers = {"Content-Type": "application/json"}
        if self.api_key:
            headers["X-API-Key"] = self.api_key
        request = urllib.request.Request(url, data=payload, headers=headers, method="POST")
        try:
            with urllib.request.urlopen(request, timeout=self.timeout) as response:
                return json.loads(response.read().decode("utf-8"))
        except urllib.error.HTTPError as exc:
            body = exc.read().decode("utf-8", "replace")
            raise Mail7Error(f"Mail7 API error {exc.code}: {body}") from exc
        except urllib.error.URLError as exc:
            raise Mail7Error(f"Mail7 request failed: {exc.reason}") from exc
