<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\Tests\Unit\EventListener;

use MauticPlugin\MauticCustomUtmBundle\EventListener\FormSubscriber;
use MauticPlugin\MauticCustomUtmBundle\Form\Type\CustomUtmCollectionType;
use MauticPlugin\MauticCustomUtmBundle\Form\Type\CustomUtmEntryType;
use MauticPlugin\MauticCustomUtmBundle\Helper\CustomUtmHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

/**
 * Builds a real Symfony form with the subscriber's listeners and verifies
 * the round-trip of custom UTM data.
 */
final class FormSubscriberTest extends TestCase
{
    private FormFactoryInterface $factory;
    private FormSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypes([
                new CustomUtmEntryType(),
                new CustomUtmCollectionType(),
            ])
            ->getFormFactory();

        $this->subscriber = new FormSubscriber(new CustomUtmHelper());
    }

    public function testCustomFieldsAreMergedOnSubmit(): void
    {
        $emailStub = new class {
            /** @var array<string, mixed> */
            private array $utmTags = [
                'utmSource'  => 'newsletter',
                'utmMedium'  => 'email',
                'old_custom' => 'should-be-removed',
            ];

            public function getUtmTags(): array
            {
                return $this->utmTags;
            }

            public function setUtmTags(array $tags): void
            {
                $this->utmTags = $tags;
            }
        };

        $builder = $this->factory->createBuilder(FormType::class, $emailStub);
        $builder->add('utmTags', FormType::class, ['mapped' => false]);

        $this->subscriber->addCustomUtmField($builder);

        $form = $builder->getForm();
        $form->setData($emailStub);

        $customData = $form->get('customUtmTags')->getData();
        self::assertIsArray($customData);
        self::assertCount(1, $customData);
        self::assertSame('old_custom', $customData[0]['key']);

        $form->submit([
            'customUtmTags' => [
                ['key' => 'channel', 'value' => 'email'],
                ['key' => 'ref', 'value' => 'hero'],
            ],
        ]);

        self::assertTrue($form->isSynchronized());

        $finalTags = $emailStub->getUtmTags();
        self::assertSame('newsletter', $finalTags['utmSource']);
        self::assertSame('email', $finalTags['utmMedium']);
        self::assertSame('email', $finalTags['channel']);
        self::assertSame('hero', $finalTags['ref']);
        self::assertArrayNotHasKey('old_custom', $finalTags);
    }

    public function testEmptyCustomEntriesAreIgnored(): void
    {
        $emailStub = new class {
            private array $utmTags = ['utmSource' => 'src'];

            public function getUtmTags(): array
            {
                return $this->utmTags;
            }

            public function setUtmTags(array $tags): void
            {
                $this->utmTags = $tags;
            }
        };

        $builder = $this->factory->createBuilder(FormType::class, $emailStub);
        $builder->add('utmTags', FormType::class, ['mapped' => false]);
        $this->subscriber->addCustomUtmField($builder);
        $form = $builder->getForm();
        $form->setData($emailStub);

        $form->submit([
            'customUtmTags' => [
                ['key' => '', 'value' => 'x'],
                ['key' => 'valid', 'value' => 'yes'],
            ],
        ]);

        $final = $emailStub->getUtmTags();
        self::assertArrayHasKey('valid', $final);
        self::assertSame('yes', $final['valid']);
        self::assertCount(2, $final);
    }

    public function testReservedKeysCannotBeOverwrittenViaCustomCollection(): void
    {
        $emailStub = new class {
            private array $utmTags = [
                'utmSource' => 'original',
                'utmMedium' => 'email',
            ];

            public function getUtmTags(): array
            {
                return $this->utmTags;
            }

            public function setUtmTags(array $tags): void
            {
                $this->utmTags = $tags;
            }
        };

        $builder = $this->factory->createBuilder(FormType::class, $emailStub);
        $builder->add('utmTags', FormType::class, ['mapped' => false]);
        $this->subscriber->addCustomUtmField($builder);
        $form = $builder->getForm();
        $form->setData($emailStub);

        $form->submit([
            'customUtmTags' => [
                ['key' => 'utmSource', 'value' => 'hacked'],
                ['key' => 'utm_medium', 'value' => 'hacked'],
                ['key' => 'safe_param', 'value' => 'ok'],
            ],
        ]);

        $final = $emailStub->getUtmTags();
        self::assertSame('original', $final['utmSource']);
        self::assertSame('email', $final['utmMedium']);
        self::assertSame('ok', $final['safe_param']);
    }
}
