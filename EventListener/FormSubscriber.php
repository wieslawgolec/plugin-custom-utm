<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\EventListener;

use MauticPlugin\MauticCustomUtmBundle\Form\Type\CustomUtmCollectionType;
use MauticPlugin\MauticCustomUtmBundle\Helper\CustomUtmHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Injects the custom UTM collection field into the email form and
 * keeps the underlying utmTags array in sync.
 */
class FormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CustomUtmHelper $helper = new CustomUtmHelper(),
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // No global events needed – the Form Type Extension calls us directly.
        return [];
    }

    /**
     * Called from EmailTypeExtension::buildForm().
     */
    public function addCustomUtmField(FormBuilderInterface $builder): void
    {
        if ($builder->has('customUtmTags')) {
            return;
        }

        $builder->add('customUtmTags', CustomUtmCollectionType::class, [
            'label'    => 'mautic.customutm.form.collection',
            'required' => false,
            'mapped'   => false,
            'attr'     => [
                'class' => 'custom-utm-collection',
            ],
        ]);

        $helper = $this->helper;

        // Populate the collection from existing utmTags on load
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($helper): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (!is_object($data) || !method_exists($data, 'getUtmTags')) {
                return;
            }

            $utmTags = $data->getUtmTags() ?? [];
            $custom  = $helper->extractCustomFromUtmTags(is_array($utmTags) ? $utmTags : []);

            if ($form->has('customUtmTags')) {
                $form->get('customUtmTags')->setData($custom);
            }
        });

        // On submit, merge custom entries back into the entity's utmTags
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($helper): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (!is_object($data) || !method_exists($data, 'getUtmTags') || !method_exists($data, 'setUtmTags')) {
                return;
            }

            if (!$form->has('customUtmTags')) {
                return;
            }

            $customEntries = $form->get('customUtmTags')->getData() ?? [];
            if (!is_array($customEntries)) {
                $customEntries = [];
            }

            $utmTags = $data->getUtmTags() ?? [];
            if (!is_array($utmTags)) {
                $utmTags = [];
            }

            // Remove previously stored custom keys so deleted rows disappear
            foreach (array_keys($utmTags) as $key) {
                if (!$helper->isReservedKey((string) $key)) {
                    unset($utmTags[$key]);
                }
            }

            $utmTags = $helper->mergeCustomIntoUtmTags($utmTags, $customEntries);
            $data->setUtmTags($utmTags);
        });
    }
}
