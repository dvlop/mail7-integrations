<?php

declare(strict_types=1);

return [
    'name'        => 'Mail7 Email Validation',
    'description' => 'Validate contact emails via Mail7 - honest Valid / Not Valid / Unknown, so real people are never wrongly marked Do Not Contact.',
    'author'      => 'Mail7',
    'version'     => '0.1.0',

    'services' => [
        'events' => [
            'mautic.mail7.subscriber.lead' => [
                'class'     => \MauticPlugin\MauticMail7Bundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.lead.model.dnc',
                    'monolog.logger.mautic',
                ],
            ],
        ],
    ],

    // Config parameters (defaults). Readable via CoreParametersHelper->get().
    'parameters' => [
        'mail7_api_key'       => '',
        'mail7_base_url'      => 'https://mail7.net/api',
        'mail7_block_unknown' => false,
    ],
];
