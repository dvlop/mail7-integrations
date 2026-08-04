import { PieceAuth } from '@activepieces/pieces-framework';

export const mail7Auth = PieceAuth.SecretText({
	displayName: 'API Key',
	required: false,
	description:
		'Optional Mail7 API key. Raises your rate limit and monthly volume. Leave empty to use the free anonymous tier. Get a key at mail7.net.',
});
