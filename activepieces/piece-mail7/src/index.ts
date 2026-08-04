import { createPiece } from '@activepieces/pieces-framework';
import { mail7Auth } from './lib/common/auth';
import { validateEmail } from './lib/actions/validate-email';

export const mail7 = createPiece({
	displayName: 'Mail7',
	description:
		'Validate email addresses with Mail7 - honest Valid / Not Valid / Unknown, so real people are never wrongly rejected.',
	auth: mail7Auth,
	minimumSupportedRelease: '0.20.0',
	logoUrl: 'https://mail7.net/static/images/apple-touch-icon.png',
	authors: ['mail7'],
	actions: [validateEmail],
	triggers: [],
});
