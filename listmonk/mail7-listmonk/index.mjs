#!/usr/bin/env node
/**
 * mail7-listmonk - validate a listmonk audience with Mail7 and blocklist undeliverable
 * addresses. Honest by design: only Not Valid addresses are blocklisted; Unknown
 * (catch-all, greylisting, disposable) are kept unless --block-unknown. Fails open - a
 * Mail7 hiccup never blocklists anyone.
 *
 * Env: LISTMONK_URL, LISTMONK_USERNAME, LISTMONK_TOKEN, [MAIL7_API_KEY], [MAIL7_BASE_URL]
 * Args: [--list <id>] [--block-unknown] [--dry-run] [--per-page <n>]
 *
 * Requires Node 18+ (global fetch).
 */

const args = process.argv.slice(2);
const hasFlag = (n) => args.includes(n);
const getOpt = (n, d) => {
	const i = args.indexOf(n);
	return i >= 0 && args[i + 1] ? args[i + 1] : d;
};

const listmonkUrl = (process.env.LISTMONK_URL || '').replace(/\/+$/, '');
const username = process.env.LISTMONK_USERNAME || '';
const token = process.env.LISTMONK_TOKEN || '';
const mail7Key = process.env.MAIL7_API_KEY || '';
const mail7Base = (process.env.MAIL7_BASE_URL || 'https://mail7.net/api').replace(/\/+$/, '');

const listId = getOpt('--list', null);
const blockUnknown = hasFlag('--block-unknown');
const dryRun = hasFlag('--dry-run');
const perPage = Math.max(1, parseInt(getOpt('--per-page', '100'), 10) || 100);

if (!listmonkUrl || !username || !token) {
	console.error('Set LISTMONK_URL, LISTMONK_USERNAME and LISTMONK_TOKEN.');
	process.exit(1);
}

const authHeader = 'Basic ' + Buffer.from(`${username}:${token}`).toString('base64');

async function validate(email) {
	const headers = { 'Content-Type': 'application/json' };
	if (mail7Key) headers['X-API-Key'] = mail7Key;
	try {
		const r = await fetch(`${mail7Base}/validate-single`, {
			method: 'POST',
			headers,
			body: JSON.stringify({ email }),
		});
		if (!r.ok) return { status: '' }; // fail open
		return await r.json();
	} catch {
		return { status: '' }; // fail open
	}
}

async function fetchPage(page) {
	const u = new URL(`${listmonkUrl}/api/subscribers`);
	u.searchParams.set('page', String(page));
	u.searchParams.set('per_page', String(perPage));
	if (listId) u.searchParams.append('list_id', String(listId));
	const r = await fetch(u, { headers: { Authorization: authHeader } });
	if (!r.ok) throw new Error(`listmonk fetch failed: HTTP ${r.status}`);
	return (await r.json()).data;
}

async function blocklist(ids) {
	const r = await fetch(`${listmonkUrl}/api/subscribers/blocklist`, {
		method: 'PUT',
		headers: { 'Content-Type': 'application/json', Authorization: authHeader },
		body: JSON.stringify({ ids }),
	});
	if (!r.ok) throw new Error(`listmonk blocklist failed: HTTP ${r.status}`);
}

async function main() {
	let page = 1;
	let total = Infinity;
	let checked = 0;
	const stats = { Valid: 0, 'Not Valid': 0, Unknown: 0, skipped: 0 };
	const toBlock = [];

	while ((page - 1) * perPage < total) {
		const data = await fetchPage(page);
		total = data.total ?? 0;
		const results = data.results ?? [];
		if (results.length === 0) break;

		for (const sub of results) {
			if (sub.status === 'blocklisted' || !sub.email) {
				stats.skipped++;
				continue;
			}
			const res = await validate(sub.email);
			const status = res.status || '';
			if (status in stats) stats[status]++;
			checked++;
			if (status === 'Not Valid' || (status === 'Unknown' && blockUnknown)) {
				toBlock.push(sub.id);
			}
		}
		page++;
	}

	console.log(
		`Checked ${checked} - Valid: ${stats.Valid}, Not Valid: ${stats['Not Valid']}, Unknown: ${stats.Unknown}, skipped: ${stats.skipped}`
	);
	console.log(`To blocklist: ${toBlock.length}${blockUnknown ? ' (incl. Unknown)' : ''}`);

	if (toBlock.length === 0) return;
	if (dryRun) {
		console.log('Dry run - nothing changed.');
		return;
	}
	for (let i = 0; i < toBlock.length; i += 100) {
		await blocklist(toBlock.slice(i, i + 100));
	}
	console.log(`Blocklisted ${toBlock.length} undeliverable subscriber(s).`);
}

main().catch((e) => {
	console.error('Error:', e.message);
	process.exit(1);
});
