<?php

declare(strict_types=1);

return [
    'name'        => 'Custom UTM Fields',
    'description' => 'Define custom UTM (or arbitrary) query parameters per email that are automatically appended to trackable links, just like the built-in utm_source / utm_medium / utm_campaign / utm_content fields.',
    'version'     => '1.0.0',
    'author'      => 'Custom UTM Contributors',

    'services' => [
        'events' => [
            'mautic.customutm.email_subscriber' => [
                'class'     => \MauticPlugin\MauticCustomUtmBundle\EventListener\EmailSubscriber::class,
                'arguments' => [
                    'mautic.customutm.helper',
                ],
                'tag' => 'kernel.event_subscriber',
            ],
            'mautic.customutm.form_subscriber' => [
                'class'     => \MauticPlugin\MauticCustomUtmBundle\EventListener\FormSubscriber::class,
                'arguments' => [
                    'mautic.customutm.helper',
                ],
                'tag' => 'kernel.event_subscriber',
            ],
        ],
        'forms' => [
            'mautic.form.type.custom_utm_collection' => [
                'class' => \MauticPlugin\MauticCustomUtmBundle\Form\Type\CustomUtmCollectionType::class,
                'alias' => 'custom_utm_collection',
            ],
            'mautic.form.type.custom_utm_entry' => [
                'class' => \MauticPlugin\MauticCustomUtmBundle\Form\Type\CustomUtmEntryType::class,
                'alias' => 'custom_utm_entry',
            ],
            'mautic.form.extension.email_type' => [
                'class'     => \MauticPlugin\MauticCustomUtmBundle\Form\Extension\EmailTypeExtension::class,
                'arguments' => [
                    'mautic.customutm.form_subscriber',
                ],
                'tag' => 'form.type_extension',
            ],
        ],
        'other' => [
            'mautic.customutm.helper' => [
                'class'     => \MauticPlugin\MauticCustomUtmBundle\Helper\CustomUtmHelper::class,
                'arguments' => [],
            ],
        ],
    ],
];
