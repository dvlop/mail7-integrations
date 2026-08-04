import { createAction, Property } from '@activepieces/pieces-framework';
import { httpClient, HttpMethod } from '@activepieces/pieces-common';
import { mail7Auth } from '../common/auth';

export const validateEmail = createAction({
	auth: mail7Auth,
	name: 'validate_email',
	displayName: 'Validate Email',
	description:
		'Check whether an email address is deliverable. Returns an honest verdict: Valid, Not Valid, or Unknown.',
	props: {
		email: Property.ShortText({
			displayName: 'Email',
			description: 'The email address to validate.',
			required: true,
		}),
	},
	async run(context) {
		const apiKey = context.auth as unknown as string | undefined;
		const headers: Record<string, string> = { 'Content-Type': 'application/json' };
		if (apiKey) {
			headers['X-API-Key'] = apiKey;
		}

		const response = await httpClient.sendRequest<Record<string, unknown>>({
			method: HttpMethod.POST,
			url: 'https://mail7.net/api/validate-single',
			headers,
			body: { email: context.propsValue.email },
		});

		return response.body;
	},
});
