<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Collection of custom UTM key/value pairs.
 *
 * @extends AbstractType<list<array{key: string, value: string}>>
 */
class CustomUtmCollectionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type'   => CustomUtmEntryType::class,
            'entry_options'=> [
                'label' => false,
            ],
            'allow_add'    => true,
            'allow_delete' => true,
            'by_reference' => false,
            'prototype'    => true,
            'label'        => 'mautic.customutm.form.collection',
            'attr'         => [
                'class' => 'custom-utm-collection',
            ],
        ]);
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'custom_utm_collection';
    }
}
