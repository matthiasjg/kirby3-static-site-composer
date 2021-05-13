# Kirby 3 Static Site Composer

A Kirby 3 plugin for ~~building~~ composing a static site.

Essentially a wrapper to integrate and trigger suitable community plugins:

1. [Static Site Generator](https://getkirby.com/plugins/d4l-data4life/static-site-generator)
2. [Feed](https://getkirby.com/plugins/bnomei/feed)

**Screenshot of field's initial state in Kirby 3 Panel**

![](screenshot_kirby3_panel_field.png)

**Screenshot of field with results from successfull run**

![](screenshot_kirby3_panel_field_success.png)

## Installation

### Download

Download and copy this repository to `/site/plugins/static-site-composer`.

### Git submodule

```
git submodule add https://github.com/matthiasjg/kirby3-static-site-composer.git site/plugins/static-site-composer
```

### Composer

```sh
composer require matthiasjg/kirby3-static-site-composer
```

## Setup

Instructions on how to configure the plugin, i.e. blueprint setup and config options.

### Blueprint Field

```yml
# site/blueprints/site.yml
fields:
  staticSiteComposer:
    label: Compose
    # help: Click here to compose a static version of the website of pages and feeds.
    # progress: Please wait, composing site...
    # success: Static site successfully composed.
    # error: An error occurred
```

### Config Options

For this plugin to properly work it is mandatory that one also hase a valid and working configuration for the [Static Site Generator](https://getkirby.com/plugins/d4l-data4life/static-site-generator).

Then, this plugin is confgured with the following option:

```php
# site/config/config.php
return [
    'matthiasjg' => [
       'static_site_composer' => [
          'endpoint' => 'compose-static-site' # set to any string like 'compose-static-site' to use the built-in endpoint (mandatory when using the blueprint field)
       ]
    ]
];
```

### API

One can also trigger the endpoint `matthiasjg.static_site_composer.endpoint`, e.g. [like so](https://github.com/matthiasjg/kirby3-static-site-composer/blob/main/index.js#L80).

## License

MIT

## Credits

- [Kirby 3](https://github.com/getkirby)
- [D4L data4life gGmbH](https://github.com/d4l-data4life)
- [Bruno Meilick](https://github.com/bnomei)
