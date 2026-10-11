<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Single row of a custom UTM key/value pair.
 *
 * Empty keys/values are filtered out by CustomUtmHelper on submit;
 * no Symfony Validator constraints are declared so the type works in
 * minimal unit-test form factories without ValidatorExtension.
 *
 * @extends AbstractType<array{key: string, value: string}>
 */
class CustomUtmEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('key', TextType::class, [
            'label'      => 'mautic.customutm.form.key',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.customutm.form.key.placeholder',
            ],
            'required' => false,
        ]);

        $builder->add('value', TextType::class, [
            'label'      => 'mautic.customutm.form.value',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.customutm.form.value.placeholder',
            ],
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'custom_utm_entry';
    }
}
