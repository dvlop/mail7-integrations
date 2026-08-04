/**
 * Official Mail7 email validation client.
 *
 * Honest classification: `status` is "Valid" | "Not Valid" | "Unknown", and `valid`
 * is `null` when the address could not be verified (catch-all, greylisting, disposable).
 * Branch on `status`; do not treat Unknown as invalid.
 */

export type Mail7Status = 'Valid' | 'Not Valid' | 'Unknown';

export interface ValidationResult {
	email: string;
	/** true = deliverable, false = does not exist, null = Unknown (could not be verified). */
	valid: boolean | null;
	formatValid: boolean;
	mxValid: boolean;
	smtpValid: boolean;
	status: Mail7Status;
	error?: string | null;
	details?: string | null;
	mx_servers?: string[] | null;
	smtp_message?: string | null;
	is_disposable?: boolean | null;
}

export interface Mail7Options {
	/** Optional API key. Raises rate limits and monthly volume. */
	apiKey?: string;
	/** Override the API base URL (default https://mail7.net/api). */
	baseUrl?: string;
	/** Custom fetch implementation (defaults to global fetch, Node 18+). */
	fetch?: typeof fetch;
}

export class Mail7Error extends Error {
	constructor(
		message: string,
		public readonly status?: number,
	) {
		super(message);
		this.name = 'Mail7Error';
	}
}

export class Mail7 {
	private readonly apiKey?: string;
	private readonly baseUrl: string;
	private readonly fetchImpl: typeof fetch;

	constructor(options: Mail7Options = {}) {
		this.apiKey = options.apiKey;
		this.baseUrl = (options.baseUrl ?? 'https://mail7.net/api').replace(/\/+$/, '');
		const f = options.fetch ?? (globalThis.fetch as typeof fetch | undefined);
		if (!f) {
			throw new Mail7Error('No fetch available. Use Node 18+ or pass options.fetch.');
		}
		this.fetchImpl = f;
	}

	/** Validate a single email address. */
	async validate(email: string): Promise<ValidationResult> {
		const headers: Record<string, string> = { 'Content-Type': 'application/json' };
		if (this.apiKey) {
			headers['X-API-Key'] = this.apiKey;
		}
		const res = await this.fetchImpl(`${this.baseUrl}/validate-single`, {
			method: 'POST',
			headers,
			body: JSON.stringify({ email }),
		});
		if (!res.ok) {
			const text = await res.text().catch(() => '');
			throw new Mail7Error(`Mail7 API error ${res.status}: ${text}`, res.status);
		}
		return (await res.json()) as ValidationResult;
	}
}

export default Mail7;
