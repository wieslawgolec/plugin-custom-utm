<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\Tests\Unit\Helper;

use MauticPlugin\MauticCustomUtmBundle\Helper\CustomUtmHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Simulates the exact transformation that core RedirectModel::getUtmTagsForUrl
 * + applyUtmTags performs, so we can assert that custom keys survive the
 * pipeline and produce correct query strings.
 */
final class ApplyUtmTagsSimulationTest extends TestCase
{
    private CustomUtmHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CustomUtmHelper();
    }

    /**
     * Replica of RedirectModel::getUtmTagsForUrl (core 7.x).
     *
     * @param array<string, mixed> $rawUtmTags
     *
     * @return array<string, mixed>
     */
    private function getUtmTagsForUrl(array $rawUtmTags): array
    {
        $utmTags = [];
        foreach ($rawUtmTags as $utmTag => $value) {
            $utmTags[str_replace('utm', 'utm_', strtolower((string) $utmTag))] = $value;
        }

        return $utmTags;
    }

    /**
     * Replica of the query-append logic (UrlHelper::appendQueryToUrl simplified).
     */
    private function appendQuery(string $url, string $query): string
    {
        if ('' === $query) {
            return $url;
        }

        $fragment = '';
        if (false !== $pos = strpos($url, '#')) {
            $fragment = substr($url, $pos);
            $url      = substr($url, 0, $pos);
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.$query.$fragment;
    }

    public function applyUtmTags(string $url, array $utmTags): string
    {
        if ([] === $utmTags) {
            return $url;
        }

        $utmTags         = $this->getUtmTagsForUrl($utmTags);
        $appendUtmString = http_build_query($utmTags, '', '&');

        return $this->appendQuery($url, $appendUtmString);
    }

    #[DataProvider('urlProvider')]
    public function testCustomParametersAreAppendedCorrectly(
        string $originalUrl,
        array $builtIn,
        array $customEntries,
        string $expectedUrl,
    ): void {
        $utmTags = $this->helper->mergeCustomIntoUtmTags($builtIn, $customEntries);
        $result  = $this->applyUtmTags($originalUrl, $utmTags);

        self::assertSame($expectedUrl, $result);
    }

    /**
     * @return iterable<string, array{string, array<string,string>, list<array{key:string,value:string}>, string}>
     */
    public static function urlProvider(): iterable
    {
        yield 'simple link + custom' => [
            'https://example.com/promo',
            ['utmSource' => 'newsletter', 'utmMedium' => 'email'],
            [['key' => 'channel', 'value' => 'email'], ['key' => 'ref', 'value' => 'footer']],
            'https://example.com/promo?utm_source=newsletter&utm_medium=email&channel=email&ref=footer',
        ];

        yield 'existing query string' => [
            'https://example.com/cart?foo=bar',
            ['utmSource' => 'mautic'],
            [['key' => 'my_param', 'value' => 'xyz']],
            'https://example.com/cart?foo=bar&utm_source=mautic&my_param=xyz',
        ];

        yield 'hash fragment preserved' => [
            'https://example.com/page#section',
            ['utmSource' => 'src'],
            [['key' => 'extra', 'value' => '1']],
            'https://example.com/page?utm_source=src&extra=1#section',
        ];

        yield 'empty custom list leaves built-ins only' => [
            'https://example.com/',
            ['utmSource' => 'a', 'utmMedium' => 'b'],
            [],
            'https://example.com/?utm_source=a&utm_medium=b',
        ];

        yield 'custom key that looks like utm_ is still treated as custom when not reserved' => [
            'https://example.com/',
            [],
            [['key' => 'utm_foo', 'value' => 'bar']],
            // normaliseKey leaves "utm_foo" as-is because it is not one of the 5 classic keys;
            // getUtmTagsForUrl then turns it into "utm__foo" because of the str_replace.
            // This is acceptable – users should prefer clean names.
            'https://example.com/?utm__foo=bar',
        ];
    }

    public function testNoUtmTagsReturnsOriginalUrl(): void
    {
        $url = 'https://example.com/path?x=1#y';
        self::assertSame($url, $this->applyUtmTags($url, []));
    }
}
