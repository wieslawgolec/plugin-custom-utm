<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Single row of a custom UTM key/value pair.
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
            'required'    => true,
            'constraints' => [
                new NotBlank(['message' => 'mautic.customutm.form.key.required']),
                new Regex([
                    'pattern' => '/^[a-zA-Z0-9_\-\.]+$/',
                    'message' => 'mautic.customutm.form.key.invalid',
                ]),
            ],
        ]);

        $builder->add('value', TextType::class, [
            'label'      => 'mautic.customutm.form.value',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.customutm.form.value.placeholder',
            ],
            'required'    => true,
            'constraints' => [
                new NotBlank(['message' => 'mautic.customutm.form.value.required']),
            ],
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
