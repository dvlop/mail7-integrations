import { axios } from "@pipedream/platform";

export default {
  key: "mail7-validate-email",
  name: "Validate Email",
  description:
    "Validate an email address with Mail7 - honest Valid / Not Valid / Unknown, so real people are never wrongly rejected. [See the docs](https://mail7.net/api-docs.html)",
  version: "0.0.1",
  type: "action",
  props: {
    email: {
      type: "string",
      label: "Email",
      description: "The email address to validate.",
    },
    apiKey: {
      type: "string",
      label: "API Key",
      description:
        "Optional Mail7 API key. Raises rate limits and monthly volume. Leave empty to use the free anonymous tier. Get a key at [mail7.net](https://mail7.net).",
      optional: true,
      secret: true,
    },
  },
  async run({ $ }) {
    const headers = { "Content-Type": "application/json" };
    if (this.apiKey) {
      headers["X-API-Key"] = this.apiKey;
    }

    const response = await axios($, {
      method: "POST",
      url: "https://mail7.net/api/validate-single",
      headers,
      data: { email: this.email },
    });

    $.export(
      "$summary",
      `Validated ${this.email}: ${response?.status ?? "done"}`
    );

    return response;
  },
};
