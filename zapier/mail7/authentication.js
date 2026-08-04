'use strict';

// Optional API key. Leave empty to use Mail7's free anonymous tier. The test hits the
// public health endpoint, so a connection succeeds with or without a key.
module.exports = {
	type: 'custom',
	fields: [
		{
			key: 'apiKey',
			label: 'Mail7 API Key',
			type: 'string',
			required: false,
			helpText:
				'Optional. Raises your rate limit and monthly volume. Leave empty to use the free anonymous tier. Get a key at [mail7.net](https://mail7.net).',
		},
	],
	test: {
		url: 'https://mail7.net/api/health',
		method: 'GET',
	},
	connectionLabel: 'Mail7',
};
