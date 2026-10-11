<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\Helper;

/**
 * Helper responsible for merging custom UTM key/value pairs into the
 * standard utmTags array used by Mautic's link-tracking pipeline.
 */
class CustomUtmHelper
{
    public const CUSTOM_KEY_PREFIX = 'customUtm_';

    /**
     * Built-in keys that are already exposed by EmailUtmTagsType.
     * Custom entries must not collide with these.
     *
     * @var list<string>
     */
    public const RESERVED_KEYS = [
        'utmSource',
        'utmMedium',
        'utmCampaign',
        'utmContent',
        'utmTerm',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    /**
     * Merge custom entries (array of ['key' => ..., 'value' => ...]) into the
     * existing utmTags array. Empty keys or values are skipped. Reserved keys
     * are ignored to avoid overriding the built-in fields.
     *
     * @param array<string, mixed>               $utmTags
     * @param list<array{key?: string, value?: string}> $customEntries
     *
     * @return array<string, mixed>
     */
    public function mergeCustomIntoUtmTags(array $utmTags, array $customEntries): array
    {
        foreach ($customEntries as $entry) {
            $key   = isset($entry['key']) ? trim((string) $entry['key']) : '';
            $value = isset($entry['value']) ? trim((string) $entry['value']) : '';

            if ('' === $key || '' === $value) {
                continue;
            }

            // Normalise to the camelCase style used by the core form
            $normalised = $this->normaliseKey($key);

            if ($this->isReservedKey($normalised) || $this->isReservedKey($key)) {
                continue;
            }

            $utmTags[$normalised] = $value;
        }

        return $utmTags;
    }

    /**
     * Extract custom (non-reserved) pairs from a utmTags array so they can be
     * shown in the collection form field.
     *
     * @param array<string, mixed> $utmTags
     *
     * @return list<array{key: string, value: string}>
     */
    public function extractCustomFromUtmTags(array $utmTags): array
    {
        $custom = [];

        foreach ($utmTags as $key => $value) {
            if ($this->isReservedKey((string) $key)) {
                continue;
            }

            if (null === $value || '' === (string) $value) {
                continue;
            }

            $custom[] = [
                'key'   => (string) $key,
                'value' => (string) $value,
            ];
        }

        return $custom;
    }

    /**
     * Convert a user-supplied key into the internal storage form.
     * - Strips leading "utm_" / "utm" if present so that getUtmTagsForUrl
     *   produces a clean query parameter.
     * - Leaves arbitrary keys untouched.
     */
    public function normaliseKey(string $key): string
    {
        $key = trim($key);

        // If the user typed a classic utm_ param, keep the camelCase style
        // that the core form already uses so the conversion stays consistent.
        $map = [
            'utm_source'   => 'utmSource',
            'utm_medium'   => 'utmMedium',
            'utm_campaign' => 'utmCampaign',
            'utm_content'  => 'utmContent',
            'utm_term'     => 'utmTerm',
        ];

        $lower = strtolower($key);
        if (isset($map[$lower])) {
            return $map[$lower];
        }

        return $key;
    }

    public function isReservedKey(string $key): bool
    {
        return in_array($key, self::RESERVED_KEYS, true)
            || in_array(strtolower($key), array_map('strtolower', self::RESERVED_KEYS), true);
    }
}
