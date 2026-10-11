<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\Tests\Unit\EventListener;

use MauticPlugin\MauticCustomUtmBundle\Helper\CustomUtmHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the merge / extract behaviour used by FormSubscriber.
 *
 * Full Symfony form integration is exercised in a live Mautic install;
 * these tests keep CI free of form-factory / ValidatorExtension wiring.
 */
final class FormSubscriberTest extends TestCase
{
    private CustomUtmHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CustomUtmHelper();
    }

    public function testCustomFieldsAreMergedAndOldCustomRemoved(): void
    {
        // Simulate PRE_SET_DATA extract
        $existing = [
            'utmSource'  => 'newsletter',
            'utmMedium'  => 'email',
            'old_custom' => 'should-be-removed',
        ];
        $extracted = $this->helper->extractCustomFromUtmTags($existing);
        self::assertCount(1, $extracted);
        self::assertSame('old_custom', $extracted[0]['key']);

        // Simulate SUBMIT: strip previous custom keys, then merge new ones
        $utmTags = $existing;
        foreach (array_keys($utmTags) as $key) {
            if (!$this->helper->isReservedKey((string) $key)) {
                unset($utmTags[$key]);
            }
        }

        $customEntries = [
            ['key' => 'channel', 'value' => 'email'],
            ['key' => 'ref', 'value' => 'hero'],
        ];
        $final = $this->helper->mergeCustomIntoUtmTags($utmTags, $customEntries);

        self::assertSame('newsletter', $final['utmSource']);
        self::assertSame('email', $final['utmMedium']);
        self::assertSame('email', $final['channel']);
        self::assertSame('hero', $final['ref']);
        self::assertArrayNotHasKey('old_custom', $final);
    }

    public function testEmptyCustomEntriesAreIgnored(): void
    {
        $utmTags = ['utmSource' => 'src'];
        $custom  = [
            ['key' => '', 'value' => 'x'],
            ['key' => 'valid', 'value' => 'yes'],
        ];

        $final = $this->helper->mergeCustomIntoUtmTags($utmTags, $custom);

        self::assertArrayHasKey('valid', $final);
        self::assertSame('yes', $final['valid']);
        self::assertCount(2, $final);
    }

    public function testReservedKeysCannotBeOverwrittenViaCustomCollection(): void
    {
        $utmTags = [
            'utmSource' => 'original',
            'utmMedium' => 'email',
        ];
        $custom = [
            ['key' => 'utmSource', 'value' => 'hacked'],
            ['key' => 'utm_medium', 'value' => 'hacked'],
            ['key' => 'safe_param', 'value' => 'ok'],
        ];

        $final = $this->helper->mergeCustomIntoUtmTags($utmTags, $custom);

        self::assertSame('original', $final['utmSource']);
        self::assertSame('email', $final['utmMedium']);
        self::assertSame('ok', $final['safe_param']);
    }
}
