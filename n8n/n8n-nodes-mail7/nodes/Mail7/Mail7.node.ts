import {
	IExecuteFunctions,
	INodeExecutionData,
	INodeType,
	INodeTypeDescription,
	NodeOperationError,
} from 'n8n-workflow';

export class Mail7 implements INodeType {
	description: INodeTypeDescription = {
		displayName: 'Mail7',
		name: 'mail7',
		icon: 'file:mail7.svg',
		group: ['transform'],
		version: 1,
		subtitle: '={{$parameter["operation"]}}',
		description:
			'Validate email addresses with Mail7 - honest Valid / Not Valid / Unknown, so real people are never wrongly rejected',
		defaults: {
			name: 'Mail7',
		},
		inputs: ['main'],
		outputs: ['main'],
		credentials: [
			{
				name: 'mail7Api',
				required: false,
			},
		],
		properties: [
			{
				displayName: 'Operation',
				name: 'operation',
				type: 'options',
				noDataExpression: true,
				options: [
					{
						name: 'Validate Email',
						value: 'validate',
						description: 'Check whether a single email address is deliverable',
						action: 'Validate an email address',
					},
				],
				default: 'validate',
			},
			{
				displayName: 'Email',
				name: 'email',
				type: 'string',
				default: '',
				required: true,
				placeholder: 'name@example.com',
				description: 'The email address to validate',
				displayOptions: {
					show: {
						operation: ['validate'],
					},
				},
			},
		],
	};

	async execute(this: IExecuteFunctions): Promise<INodeExecutionData[][]> {
		const items = this.getInputData();
		const returnData: INodeExecutionData[] = [];

		// Credentials are optional: with a key we send X-API-Key, without one we call the
		// free anonymous tier. getCredentials throws when none is configured - that is fine.
		let apiKey = '';
		try {
			const credentials = await this.getCredentials('mail7Api');
			apiKey = ((credentials?.apiKey as string) || '').trim();
		} catch {
			apiKey = '';
		}

		for (let i = 0; i < items.length; i++) {
			try {
				const email = (this.getNodeParameter('email', i) as string).trim();

				const headers: Record<string, string> = { 'Content-Type': 'application/json' };
				if (apiKey) {
					headers['X-API-Key'] = apiKey;
				}

				const response = await this.helpers.httpRequest({
					method: 'POST',
					url: 'https://mail7.net/api/validate-single',
					headers,
					body: { email },
					json: true,
				});

				returnData.push({ json: response, pairedItem: { item: i } });
			} catch (error) {
				if (this.continueOnFail()) {
					returnData.push({
						json: { error: (error as Error).message },
						pairedItem: { item: i },
					});
					continue;
				}
				throw new NodeOperationError(this.getNode(), error as Error, { itemIndex: i });
			}
		}

		return [returnData];
	}
}
