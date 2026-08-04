import {
	IAuthenticateGeneric,
	ICredentialType,
	INodeProperties,
} from 'n8n-workflow';

export class Mail7Api implements ICredentialType {
	name = 'mail7Api';

	displayName = 'Mail7 API';

	documentationUrl = 'https://mail7.net/api-docs.html';

	properties: INodeProperties[] = [
		{
			displayName: 'API Key',
			name: 'apiKey',
			type: 'string',
			typeOptions: { password: true },
			default: '',
			description:
				'Optional. Raises your rate limit and monthly volume. Leave empty to use the free anonymous tier. Get a key at mail7.net.',
		},
	];

	// Sent automatically when this credential is attached. An empty key is treated as
	// anonymous by the Mail7 API, so leaving it blank is safe.
	authenticate: IAuthenticateGeneric = {
		type: 'generic',
		properties: {
			headers: {
				'X-API-Key': '={{$credentials.apiKey}}',
			},
		},
	};
}
