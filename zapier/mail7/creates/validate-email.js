'use strict';

const perform = async (z, bundle) => {
	const headers = { 'Content-Type': 'application/json' };
	if (bundle.authData && bundle.authData.apiKey) {
		headers['X-API-Key'] = bundle.authData.apiKey;
	}

	const response = await z.request({
		method: 'POST',
		url: 'https://mail7.net/api/validate-single',
		headers,
		body: { email: bundle.inputData.email },
	});

	return response.data;
};

module.exports = {
	key: 'validate_email',
	noun: 'Email',

	display: {
		label: 'Validate Email',
		description:
			'Validate an email address with Mail7 - honest Valid / Not Valid / Unknown, so real people are never wrongly rejected.',
	},

	operation: {
		inputFields: [
			{
				key: 'email',
				label: 'Email',
				type: 'string',
				required: true,
				helpText: 'The email address to validate.',
			},
		],
		perform,
		sample: {
			email: 'user@example.com',
			valid: true,
			formatValid: true,
			mxValid: true,
			smtpValid: true,
			status: 'Valid',
			is_disposable: false,
		},
		outputFields: [
			{ key: 'email', label: 'Email' },
			{ key: 'status', label: 'Status', helpText: 'Valid, Not Valid, or Unknown.' },
			{ key: 'valid', label: 'Valid', type: 'boolean', helpText: 'true / false / empty when Unknown.' },
			{ key: 'is_disposable', label: 'Is Disposable', type: 'boolean' },
		],
	},
};
