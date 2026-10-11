<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\Tests\Unit\Helper;

use MauticPlugin\MauticCustomUtmBundle\Helper\CustomUtmHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CustomUtmHelperTest extends TestCase
{
    private CustomUtmHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CustomUtmHelper();
    }

    public function testMergeAddsCustomEntries(): void
    {
        $utmTags = [
            'utmSource'  => 'newsletter',
            'utmMedium'  => 'email',
        ];

        $custom = [
            ['key' => 'utm_term', 'value' => 'summer'],
            ['key' => 'my_param', 'value' => 'abc'],
        ];

        $result = $this->helper->mergeCustomIntoUtmTags($utmTags, $custom);

        self::assertSame('newsletter', $result['utmSource']);
        self::assertSame('email', $result['utmMedium']);
        // utm_term is reserved – must be ignored
        self::assertArrayNotHasKey('utmTerm', $result);
        self::assertArrayNotHasKey('utm_term', $result);
        self::assertSame('abc', $result['my_param']);
    }

    public function testMergeSkipsEmptyKeyOrValue(): void
    {
        $utmTags = ['utmSource' => 'src'];
        $custom  = [
            ['key' => '', 'value' => 'v'],
            ['key' => 'k', 'value' => ''],
            ['key' => '  ', 'value' => 'v'],
            ['key' => 'valid', 'value' => 'ok'],
        ];

        $result = $this->helper->mergeCustomIntoUtmTags($utmTags, $custom);

        self::assertCount(2, $result);
        self::assertSame('ok', $result['valid']);
    }

    public function testMergeDoesNotOverwriteReservedKeys(): void
    {
        $utmTags = [
            'utmSource'   => 'original',
            'utmMedium'   => 'email',
            'utmCampaign' => 'camp',
            'utmContent'  => 'cont',
        ];

        $custom = [
            ['key' => 'utmSource', 'value' => 'hacked'],
            ['key' => 'utm_medium', 'value' => 'hacked'],
            ['key' => 'utmCampaign', 'value' => 'hacked'],
            ['key' => 'utm_content', 'value' => 'hacked'],
            ['key' => 'utmTerm', 'value' => 'hacked'],
        ];

        $result = $this->helper->mergeCustomIntoUtmTags($utmTags, $custom);

        self::assertSame('original', $result['utmSource']);
        self::assertSame('email', $result['utmMedium']);
        self::assertSame('camp', $result['utmCampaign']);
        self::assertSame('cont', $result['utmContent']);
        self::assertArrayNotHasKey('utmTerm', $result);
    }

    public function testExtractReturnsOnlyCustomKeys(): void
    {
        $utmTags = [
            'utmSource'  => 'src',
            'utmMedium'  => 'email',
            'utmCampaign'=> 'c',
            'utmContent' => 'ct',
            'my_param'   => 'val1',
            'another'    => 'val2',
            'utmTerm'    => 'should-be-ignored-as-reserved',
        ];

        $custom = $this->helper->extractCustomFromUtmTags($utmTags);

        self::assertCount(2, $custom);
        $keys = array_column($custom, 'key');
        self::assertContains('my_param', $keys);
        self::assertContains('another', $keys);
    }

    public function testExtractIgnoresEmptyValues(): void
    {
        $utmTags = [
            'utmSource' => 'src',
            'empty'     => '',
            'nullish'   => null,
            'ok'        => 'yes',
        ];

        $custom = $this->helper->extractCustomFromUtmTags($utmTags);

        self::assertCount(1, $custom);
        self::assertSame('ok', $custom[0]['key']);
        self::assertSame('yes', $custom[0]['value']);
    }

    #[DataProvider('normaliseKeyProvider')]
    public function testNormaliseKey(string $input, string $expected): void
    {
        self::assertSame($expected, $this->helper->normaliseKey($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normaliseKeyProvider(): iterable
    {
        yield 'classic utm_source' => ['utm_source', 'utmSource'];
        yield 'classic utm_medium' => ['utm_medium', 'utmMedium'];
        yield 'classic utm_campaign' => ['utm_campaign', 'utmCampaign'];
        yield 'classic utm_content' => ['utm_content', 'utmContent'];
        yield 'classic utm_term' => ['utm_term', 'utmTerm'];
        yield 'already camel' => ['utmSource', 'utmSource'];
        yield 'custom' => ['my_custom_param', 'my_custom_param'];
        yield 'with spaces trimmed' => ['  foo  ', 'foo'];
    }

    public function testIsReservedKey(): void
    {
        self::assertTrue($this->helper->isReservedKey('utmSource'));
        self::assertTrue($this->helper->isReservedKey('utm_source'));
        self::assertTrue($this->helper->isReservedKey('UTM_SOURCE'));
        self::assertTrue($this->helper->isReservedKey('utmTerm'));
        self::assertFalse($this->helper->isReservedKey('my_param'));
        self::assertFalse($this->helper->isReservedKey('channel'));
    }

    public function testRoundTrip(): void
    {
        $original = [
            'utmSource' => 'newsletter',
            'utmMedium' => 'email',
            'channel'   => 'email',
            'ref'       => 'footer',
        ];

        $extracted = $this->helper->extractCustomFromUtmTags($original);
        $merged    = $this->helper->mergeCustomIntoUtmTags(
            [
                'utmSource' => 'newsletter',
                'utmMedium' => 'email',
            ],
            $extracted
        );

        self::assertSame($original, $merged);
    }
}
