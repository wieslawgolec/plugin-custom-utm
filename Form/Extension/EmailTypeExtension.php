<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\Form\Extension;

use Mautic\EmailBundle\Form\Type\EmailType;
use MauticPlugin\MauticCustomUtmBundle\EventListener\FormSubscriber;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Extends the core EmailType to inject the custom UTM collection field.
 */
class EmailTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly FormSubscriber $formSubscriber,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [EmailType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->formSubscriber->addCustomUtmField($builder);
    }
}
