# Mautic Custom UTM Bundle

Community plugin for **Mautic 7.2+** that lets you define **custom UTM (or arbitrary query) parameters on a per-email basis**.

They are stored together with the four built-in UTM fields (`utm_source`, `utm_medium`, `utm_campaign`, `utm_content`) and are automatically appended to every trackable link in the email — exactly the same way the core fields are handled.

| | |
|--|--|
| **Version** | 1.0.0 |
| **PHP** | 8.2+ (CI: 8.2, 8.5, 8.6) |
| **Mautic** | 7.2+ |
| **License** | GPL-3.0-or-later |
| **PHPUnit** | 11.5 |

---

## Table of contents

1. [Why this plugin?](#why-this-plugin)
2. [Features](#features)
3. [Install in Mautic](#install-in-mautic)
4. [Usage](#usage)
5. [Technical notes](#technical-notes)
6. [Development & tests](#development--tests)
7. [Support the project](#support-the-project)
8. [License](#license)

---

## Why this plugin?

Mautic ships with four fixed UTM fields in the email editor.  
Many marketing teams need extra parameters such as:

- `utm_term`
- `utm_id`
- campaign-specific tracking IDs
- A/B test identifiers
- partner / affiliate codes
- any other query parameter required by analytics platforms

This plugin adds a dynamic key/value collection right next to the standard UTM fields in the email create/edit UI.  
Values are merged into the existing `utmTags` array on the `Email` entity, so the whole core tracking pipeline (`TrackableModel` → `RedirectModel::applyUtmTags`) continues to work unchanged.

---

## Features

- **UI in email add/edit form** — collection field with add/remove rows
- **Per-email configuration** — each email can have its own set of custom parameters
- **Safe merging** — built-in UTM keys cannot be overwritten by custom entries
- **Full compatibility** with Mautic’s link tracking, URL shortening and click-through mechanism
- **No schema migration** — uses the existing `utm_tags` column
- **Unit tests** covering helper logic, form round-trip and URL generation edge cases
- **GitHub Actions** matrix testing on PHP 8.2 / 8.5 / 8.6 with PHPUnit 11.5

---

## Install in Mautic

### Requirements

- Mautic **7.2+**
- PHP **8.2+** with extensions used by Mautic (`json`, `mbstring`, `curl`, etc.)

### Option A — Clone into `plugins/`

From your Mautic root:

```bash
cd /path/to/mautic
git clone https://github.com/wieslawgolec/plugin-custom-utm.git plugins/MauticCustomUtmBundle
```

The directory name must be **`MauticCustomUtmBundle`** so Mautic discovers the bundle.

### Option B — Download ZIP

Download the release ZIP, extract it as `plugins/MauticCustomUtmBundle`.

### Enable the plugin

```bash
php bin/console cache:clear
php bin/console mautic:plugins:reload
# or: Settings → Plugins → Install/Upgrade Plugins in the UI
```

The new **Custom UTM / query parameters** section appears in the email editor (right-hand panel, below the standard UTM tags).

---

## Usage

1. Open or create an email.
2. Expand the **UTM tags** section (core fields) if needed.
3. In the **Custom UTM / query parameters** collection:
   - Click “Add” to create a new row.
   - Enter a parameter name (e.g. `utm_term`, `channel`, `ref`, `partner_id`).
   - Enter the value.
4. Save the email.
5. When the email is sent or previewed, every trackable link receives both the standard UTM parameters **and** your custom ones.

### Example resulting URL

```
https://example.com/promo?utm_source=newsletter&utm_medium=email&utm_campaign=spring_sale&channel=email&ref=hero&partner_id=42
```

---

## Technical notes

### Storage

Custom parameters are stored inside the same `utm_tags` JSON/array column that already exists on the `emails` table.  
No schema migration is required.

Reserved keys (the four core fields + `utm_term`) are protected and cannot be overwritten by the custom collection.

### Form integration

The plugin uses a Symfony Form Type Extension (`EmailTypeExtension`) that injects a `CollectionType` into the core `EmailType`.  
`PRE_SET_DATA` and `SUBMIT` listeners keep the collection and the entity’s `utmTags` array in sync.

### URL generation

Core code path (unchanged):

```
Email → utmTags array
      → TrackableModel::generateTrackableUrl(..., $utmTags)
      → RedirectModel::applyUtmTags($url, $utmTags)
      → getUtmTagsForUrl() normalises keys (utmSource → utm_source)
      → http_build_query + UrlHelper::appendQueryToUrl
```

Because custom keys live in the same array they automatically participate in this pipeline.

### Architecture

```
plugins/MauticCustomUtmBundle/
├── Config/config.php
├── EventListener/
│   ├── EmailSubscriber.php
│   └── FormSubscriber.php
├── Form/
│   ├── Extension/EmailTypeExtension.php
│   └── Type/
│       ├── CustomUtmCollectionType.php
│       └── CustomUtmEntryType.php
├── Helper/CustomUtmHelper.php
├── Resources/translations/en_US/
├── Tests/Unit/
└── .github/workflows/tests.yml
```

---

## Development & tests

### Requirements

- PHP ≥ 8.2
- Composer
- PHPUnit 11.x

### Running the tests locally

```bash
cd plugins/MauticCustomUtmBundle   # or the directory where you cloned the plugin
composer update --ignore-platform-reqs
vendor/bin/phpunit --configuration phpunit.xml.dist
```

### CI

GitHub Actions runs the full PHPUnit suite on:

| PHP version | Status   |
|-------------|----------|
| 8.2         | tested   |
| 8.5         | tested   |
| 8.6         | tested   |

PHPUnit **11.5**.

---

## Support the project

If this plugin saves you time, you can support development:

- **GitHub Sponsors:** [github.com/sponsors/wieslawgolec](https://github.com/sponsors/wieslawgolec)
- **Buy Me a Coffee:** [buymeacoffee.com/wieslawgolec](https://buymeacoffee.com/wieslawgolec)

---

## License

GPL-3.0-or-later (same family as Mautic).
