#!/usr/bin/env node
/**
 * Mail7 MCP server (stdio).
 *
 * Exposes Mail7's email validation and domain diagnostics as MCP tools so an AI
 * assistant can clean a list, check an address before sending, or audit a
 * domain's mail configuration.
 *
 * Config (environment):
 *   MAIL7_API_KEY   optional, strongly recommended - raises rate limits and unlocks bulk
 *   MAIL7_BASE_URL  optional, defaults to https://mail7.net/api
 */

import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { z } from 'zod';

import {
	BATCH_SIZE,
	Mail7Client,
	Mail7Error,
	type DomainCheckResponse,
	type DomainSection,
	type ValidationResult,
} from './client.js';

/** Sections of a domain check. Mirrors _ALLOWED_SECTIONS in src/api/routes.py. */
const SECTIONS = ['domain', 'mx', 'spf', 'dkim', 'dmarc', 'mta_sts', 'tls_rpt', 'dnssec', 'bimi'] as const;

/**
 * Hard cap per call. A model handed a 5000-address file will otherwise try to
 * push it through in one tool call and time out; forcing it into chunks keeps
 * every call inside the client's request timeout and keeps progress visible.
 */
const MAX_EMAILS_PER_CALL = 50;

const HONEST_STATUS_NOTE =
	'Status is one of "Valid", "Not Valid", "Unknown". Unknown means the mailbox could NOT be ' +
	'checked (catch-all domain, greylisting, blocked SMTP port) - it is NOT a bad address. ' +
	'Never delete, reject or report Unknown addresses as invalid; present them separately and ' +
	'let the user decide.';

const client = new Mail7Client({
	apiKey: process.env.MAIL7_API_KEY,
	baseUrl: process.env.MAIL7_BASE_URL,
});

const server = new McpServer({ name: 'mail7', version: '0.1.0' });

const RESULT_SHAPE = {
	email: z.string(),
	status: z.enum(['Valid', 'Not Valid', 'Unknown']),
	valid: z.boolean().nullable().describe('true = deliverable, false = does not exist, null = Unknown'),
	formatValid: z.boolean(),
	mxValid: z.boolean(),
	smtpValid: z.boolean(),
	is_disposable: z.boolean().nullable().optional(),
	details: z.string().nullable().optional(),
};

// --- validate_email ---------------------------------------------------------

server.registerTool(
	'validate_email',
	{
		title: 'Validate one email address',
		description:
			'Check whether a single email address is deliverable: syntax, domain MX records, disposable-domain ' +
			`database and a live SMTP mailbox probe. ${HONEST_STATUS_NOTE} ` +
			'Use validate_emails instead when you have more than one address.',
		inputSchema: {
			email: z.string().describe('The email address to check, e.g. user@example.com'),
		},
		outputSchema: RESULT_SHAPE,
		annotations: { readOnlyHint: true, openWorldHint: true },
	},
	async ({ email }) => {
		const trimmed = email.trim();
		if (!trimmed) {
			return fail('Pass a non-empty email address.');
		}
		try {
			const result = await client.validate(trimmed);
			return {
				content: [{ type: 'text' as const, text: describeResult(result) }],
				structuredContent: compact(result),
			};
		} catch (err) {
			return fail(errorText(err));
		}
	},
);

// --- validate_emails --------------------------------------------------------

server.registerTool(
	'validate_emails',
	{
		title: 'Validate a list of email addresses',
		description:
			`Check up to ${MAX_EMAILS_PER_CALL} email addresses in one call and get a per-address verdict plus a ` +
			`summary. ${HONEST_STATUS_NOTE} ` +
			'Requires MAIL7_API_KEY (anonymous callers get a 25-address free sample only). Each address takes a ' +
			'few seconds of real SMTP work, so this call is slow by nature: for a longer list, split it and call ' +
			`this tool repeatedly with the next ${MAX_EMAILS_PER_CALL} addresses. Never run two calls in parallel - ` +
			'the API serialises one bulk job per client and the second call fails.',
		inputSchema: {
			emails: z
				.array(z.string())
				.min(1)
				.max(MAX_EMAILS_PER_CALL)
				.describe(`The addresses to check, at most ${MAX_EMAILS_PER_CALL} per call`),
		},
		outputSchema: {
			summary: z.object({
				checked: z.number(),
				valid: z.number(),
				not_valid: z.number(),
				unknown: z.number(),
			}),
			results: z.array(z.object(RESULT_SHAPE)),
			partial: z
				.boolean()
				.describe('true when the list was cut short (free anonymous sample or a limit was hit)'),
			note: z.string().optional(),
		},
		annotations: { readOnlyHint: true, openWorldHint: true },
	},
	async ({ emails }) => {
		const list = emails.map((e) => e.trim()).filter(Boolean);
		if (list.length === 0) {
			return fail('Pass at least one non-empty email address.');
		}

		const results: ValidationResult[] = [];
		let note: string | undefined;

		for (let start = 0; start < list.length; start += BATCH_SIZE) {
			const chunk = list.slice(start, start + BATCH_SIZE);
			try {
				const response = await client.validateBatch(chunk, start, list);
				results.push(...response.results);
			} catch (err) {
				// Partial success is worth more to the caller than a bare failure:
				// return what was checked and say why the rest is missing.
				if (results.length === 0) {
					return fail(errorText(err));
				}
				note = `Stopped after ${results.length} of ${list.length} addresses: ${errorText(err)}`;
				break;
			}
		}

		const summary = {
			checked: results.length,
			valid: results.filter((r) => r.status === 'Valid').length,
			not_valid: results.filter((r) => r.status === 'Not Valid').length,
			unknown: results.filter((r) => r.status === 'Unknown').length,
		};
		const partial = results.length < list.length;

		const lines = [
			`Checked ${summary.checked} of ${list.length} addresses: ` +
				`${summary.valid} Valid, ${summary.not_valid} Not Valid, ${summary.unknown} Unknown.`,
			'',
			...results.map((r) => `${r.status.padEnd(9)} ${r.email}${r.details ? ` - ${r.details}` : ''}`),
		];
		if (note) {
			lines.push('', note);
		}
		if (summary.unknown > 0) {
			lines.push(
				'',
				'Unknown addresses could not be verified either way - keep them, do not count them as invalid.',
			);
		}

		return {
			content: [{ type: 'text' as const, text: lines.join('\n') }],
			structuredContent: { summary, results: results.map(compact), partial, ...(note ? { note } : {}) },
		};
	},
);

// --- check_domain -----------------------------------------------------------

server.registerTool(
	'check_domain',
	{
		title: 'Check a domain mail configuration',
		description:
			'Audit a domain\'s email setup in one call: MX records, SPF, DKIM, DMARC, MTA-STS, TLS-RPT, DNSSEC ' +
			'and BIMI, graded A-F with a fix for every problem found. Use this to answer "can this domain send ' +
			'and receive mail" or "why do our messages land in spam". Takes a domain (example.com), not an email ' +
			'address. The default answer summarises every section; pass `section` to get the full records and ' +
			'findings of one of them.',
		inputSchema: {
			domain: z.string().describe('Domain to check, e.g. example.com'),
			section: z
				.enum(SECTIONS)
				.optional()
				.describe('Drill into a single section instead of the whole summary'),
			dkim_selector: z
				.string()
				.optional()
				.describe('Optional DKIM selector to look up, e.g. "google" or "s1". Omit if unknown.'),
		},
		annotations: { readOnlyHint: true, openWorldHint: true },
	},
	async ({ domain, section, dkim_selector }) => {
		try {
			const result = await client.domainCheck(domain.trim().toLowerCase(), dkim_selector, section);
			return {
				content: [
					{ type: 'text' as const, text: section ? describeSection(result) : describeDomain(result) },
				],
			};
		} catch (err) {
			return fail(errorText(err));
		}
	},
);

// --- check_spf --------------------------------------------------------------

server.registerTool(
	'check_spf',
	{
		title: 'Check a domain SPF record',
		description:
			'Fetch and analyse the SPF record of a domain: the raw record, its mechanisms, lookup count and ' +
			'common misconfigurations. Use check_domain instead when the question is about overall ' +
			'deliverability rather than SPF specifically.',
		inputSchema: {
			domain: z.string().describe('Domain to check, e.g. example.com'),
		},
		annotations: { readOnlyHint: true, openWorldHint: true },
	},
	async ({ domain }) => {
		try {
			const result = await client.spfCheck(domain.trim().toLowerCase());
			return { content: [{ type: 'text' as const, text: JSON.stringify(result, null, 2) }] };
		} catch (err) {
			return fail(errorText(err));
		}
	},
);

// --- helpers ----------------------------------------------------------------

function compact(r: ValidationResult) {
	return {
		email: r.email,
		status: r.status,
		valid: r.valid ?? null,
		formatValid: r.formatValid,
		mxValid: r.mxValid,
		smtpValid: r.smtpValid,
		is_disposable: r.is_disposable ?? null,
		details: r.details ?? null,
	};
}

function describeResult(r: ValidationResult): string {
	const lines = [
		`${r.email}: ${r.status}`,
		`  syntax: ${yesNo(r.formatValid)}, MX: ${yesNo(r.mxValid)}, mailbox (SMTP): ${yesNo(r.smtpValid)}`,
	];
	if (r.is_disposable) {
		lines.push('  disposable/throwaway domain');
	}
	if (r.details) {
		lines.push(`  ${r.details}`);
	}
	if (r.status === 'Unknown') {
		lines.push('  Unknown means unverifiable, not bad - do not treat it as invalid.');
	}
	return lines.join('\n');
}

const yesNo = (v: boolean) => (v ? 'ok' : 'no');

/**
 * Summarise a domain check. The raw payload is tens of kilobytes of records the
 * model does not need up front, so only the grade, each section's status and the
 * problems (with their fix) are rendered; `section` fetches the rest on demand.
 */
function describeDomain(r: DomainCheckResponse): string {
	const counts = r.counts ?? {};
	const lines = [
		`${r.domain} - grade ${r.grade ?? '?'} ` +
			`(${counts.pass ?? 0} pass, ${counts.warn ?? 0} warn, ${counts.fail ?? 0} fail)`,
		'',
	];

	for (const section of r.sections ?? []) {
		lines.push(`${section.status.toUpperCase().padEnd(5)} ${section.title}`);
		for (const finding of section.findings ?? []) {
			if (finding.severity === 'pass' || finding.severity === 'info') {
				continue;
			}
			lines.push(`      ${finding.severity}: ${finding.title}${finding.detail ? ` - ${finding.detail}` : ''}`);
			if (finding.fix) {
				lines.push(`      fix: ${finding.fix}`);
			}
		}
	}

	const problems = (r.sections ?? []).filter((s) => s.status === 'fail' || s.status === 'warn');
	lines.push(
		'',
		problems.length > 0
			? `Call check_domain again with section="${problems[0].key}" for the full records of a section.`
			: 'Call check_domain again with a section name for the full records of that section.',
	);
	return lines.join('\n');
}

/** Render one section in full: the records are the point of drilling in. */
function describeSection(r: DomainCheckResponse): string {
	const section: DomainSection | undefined = r.sections?.[0];
	if (!section) {
		return JSON.stringify(r, null, 2);
	}
	const lines = [`${r.domain} - ${section.title}: ${section.status}`];
	if (section.records?.length) {
		lines.push('', 'Records:', ...section.records.map((rec) => `  ${rec}`));
	}
	for (const finding of section.findings ?? []) {
		lines.push('', `${finding.severity}: ${finding.title}`);
		if (finding.detail) lines.push(`  ${finding.detail}`);
		if (finding.why) lines.push(`  why: ${finding.why}`);
		if (finding.fix) lines.push(`  fix: ${finding.fix}`);
	}
	return lines.join('\n');
}

function errorText(err: unknown): string {
	if (err instanceof Mail7Error) {
		return err.message;
	}
	return err instanceof Error ? err.message : String(err);
}

function fail(text: string) {
	return { content: [{ type: 'text' as const, text }], isError: true };
}

// --- boot -------------------------------------------------------------------

async function main() {
	await server.connect(new StdioServerTransport());
}

main().catch((err) => {
	// stdout is the MCP channel - diagnostics must go to stderr.
	console.error('mail7-mcp failed to start:', err);
	process.exit(1);
});
