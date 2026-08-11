/**
 * Thin Mail7 API client used by the MCP server.
 *
 * Bundled on purpose (same pattern as the other Mail7 integrations): the MCP
 * package must run from a single `npx` install with no extra moving parts.
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

export interface BatchInfo {
	batch_start: number;
	batch_end: number;
	total_emails: number;
	has_more: boolean;
	next_batch_start: number | null;
}

export interface BulkResponse {
	results: ValidationResult[];
	total: number;
	batch_info?: BatchInfo;
}

export interface DomainFinding {
	severity: 'pass' | 'warn' | 'fail' | 'info' | string;
	title: string;
	detail?: string;
	fix?: string;
	why?: string;
}

export interface DomainSection {
	key: string;
	title: string;
	status: string;
	records?: string[];
	findings?: DomainFinding[];
}

export interface DomainCheckResponse {
	domain: string;
	grade?: string;
	counts?: Record<string, number>;
	sections?: DomainSection[];
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

/** Server-side batch size. Matches RATE_LIMITS.bulk.batch_size in config.py. */
export const BATCH_SIZE = 25;

const SINGLE_TIMEOUT_MS = 45_000;
const BATCH_TIMEOUT_MS = 100_000;

export class Mail7Client {
	private readonly apiKey?: string;
	private readonly baseUrl: string;

	constructor(options: { apiKey?: string; baseUrl?: string } = {}) {
		this.apiKey = options.apiKey;
		this.baseUrl = (options.baseUrl ?? 'https://mail7.net/api').replace(/\/+$/, '');
	}

	private headers(extra: Record<string, string> = {}): Record<string, string> {
		const headers: Record<string, string> = { Accept: 'application/json', ...extra };
		if (this.apiKey) {
			headers['X-API-Key'] = this.apiKey;
		}
		return headers;
	}

	private async request<T>(path: string, init: RequestInit, timeoutMs: number): Promise<T> {
		let res: Response;
		try {
			res = await fetch(`${this.baseUrl}${path}`, {
				...init,
				signal: AbortSignal.timeout(timeoutMs),
			});
		} catch (err) {
			const reason = err instanceof Error ? err.message : String(err);
			throw new Mail7Error(`Could not reach the Mail7 API: ${reason}`);
		}
		if (!res.ok) {
			throw new Mail7Error(await describeError(res), res.status);
		}
		return (await res.json()) as T;
	}

	/** Validate one address. */
	validate(email: string): Promise<ValidationResult> {
		return this.request<ValidationResult>(
			'/validate-single',
			{
				method: 'POST',
				headers: this.headers({ 'Content-Type': 'application/json' }),
				body: JSON.stringify({ email }),
			},
			SINGLE_TIMEOUT_MS,
		);
	}

	/** Validate one server-side batch of a list. */
	validateBatch(emails: string[], batchStart: number, allEmails: string[]): Promise<BulkResponse> {
		const body = new URLSearchParams({
			batch_start: String(batchStart),
			batch_size: String(emails.length),
			total_emails: String(allEmails.length),
			file_content: allEmails.join('\n'),
			file_type: 'txt',
		});
		return this.request<BulkResponse>(
			'/validate-bulk-batch',
			{
				method: 'POST',
				headers: this.headers({ 'Content-Type': 'application/x-www-form-urlencoded' }),
				body,
			},
			BATCH_TIMEOUT_MS,
		);
	}

	/** SPF record analysis for a domain. */
	spfCheck(domain: string): Promise<Record<string, unknown>> {
		return this.request<Record<string, unknown>>(
			`/spf-check/${encodeURIComponent(domain)}`,
			{ method: 'GET', headers: this.headers() },
			SINGLE_TIMEOUT_MS,
		);
	}

	/** Full mail-configuration check: MX, SPF, DKIM, DMARC, MTA-STS, TLS-RPT, DNSSEC, BIMI. */
	domainCheck(domain: string, dkimSelector?: string, section?: string): Promise<DomainCheckResponse> {
		const params = new URLSearchParams();
		if (dkimSelector) params.set('dkim_selector', dkimSelector);
		if (section) params.set('only', section);
		const query = params.size > 0 ? `?${params}` : '';
		return this.request<DomainCheckResponse>(
			`/domain-check/${encodeURIComponent(domain)}${query}`,
			{ method: 'GET', headers: this.headers() },
			SINGLE_TIMEOUT_MS,
		);
	}
}

/** Turn an HTTP failure into a message that tells the model what to do next. */
async function describeError(res: Response): Promise<string> {
	let detail = '';
	try {
		const body = (await res.json()) as { detail?: unknown };
		detail = typeof body?.detail === 'string' ? body.detail : '';
	} catch {
		detail = '';
	}

	switch (res.status) {
		case 401:
			return `${detail || 'Sign-in required for this call.'} Set MAIL7_API_KEY in the MCP server config (get a key at https://mail7.net/account/). Do not retry without one.`;
		case 402:
			return `${detail || 'Monthly credits exhausted.'} Stop and tell the user to top up at https://mail7.net/pricing.html. Do not retry.`;
		case 403:
			return detail || 'Access denied. Verify the account email, then try again.';
		case 429: {
			const retryAfter = res.headers.get('Retry-After');
			const wait = retryAfter ? `Wait ${retryAfter}s before the next call.` : 'Wait about a minute before the next call.';
			return `${detail || 'Rate limit reached.'} ${wait} Do not retry in a loop; an API key raises the limit substantially.`;
		}
		default:
			return `Mail7 API error ${res.status}${detail ? `: ${detail}` : ''}`;
	}
}
