'use strict';

const authentication = require('./authentication');
const validateEmail = require('./creates/validate-email');

const { version } = require('./package.json');
const platformVersion = require('zapier-platform-core').version;

// Attach the API key header (when present) to every outbound request.
const includeApiKey = (request, z, bundle) => {
	if (bundle.authData && bundle.authData.apiKey) {
		request.headers = request.headers || {};
		request.headers['X-API-Key'] = bundle.authData.apiKey;
	}
	return request;
};

module.exports = {
	version,
	platformVersion,

	authentication,

	beforeRequest: [includeApiKey],

	creates: {
		[validateEmail.key]: validateEmail,
	},

	triggers: {},
	searches: {},
	resources: {},
};
