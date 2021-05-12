<?php

namespace Matthiasjg;

use Kirby\Cms\App as Kirby;
use D4L\StaticSiteGenerator as StaticSiteGenerator;

Kirby::plugin('matthiasjg/kirby3-static-site-composer', [
    'options' => [
        'endpoint' => 'compose-static-site'
    ],
    'api' => [
        'routes' => function ($kirby) {
            $endpoint = $kirby->option('matthiasjg.static_site_composer.endpoint');
            if (!$endpoint) {
                return [];
            }

            return [
                [
                    'pattern' => $endpoint,
                    'action' => function () use ($kirby) {
                        $outputFolder = $kirby->option('d4l.static_site_generator.output_folder', './static');
                        $baseUrl = $kirby->option('d4l.static_site_generator.base_url', '/');
                        $preserve = $kirby->option('d4l.static_site_generator.preserve', []);
                        $skipMedia = $kirby->option('d4l.static_site_generator.skip_media', false);
                        $skipTemplates = array_diff($kirby->option('d4l.static_site_generator.skip_templates', []), ['home']);

                        $pages = $kirby->site()->index()->filterBy('intendedTemplate', 'not in', $skipTemplates);
                        $staticSiteGenerator = new StaticSiteGenerator($kirby, null, $pages);
                        $staticSiteGenerator->skipMedia($skipMedia);
                        $list = $staticSiteGenerator->generate($outputFolder, $baseUrl, $preserve);
                        $count = count($list);                        
                        return ['success' => true, 'files' => $list, 'message' => "$count files generated / copied"];
                    },
                    'method' => 'POST'
                ]
            ];
        }
    ],
    'fields' => [
        'staticSiteComposer' => [
            'props' => [
                'endpoint' => function () {
                    return $this->kirby()->option('matthiasjg.static_site_composer.endpoint');
                }
            ]
        ]
    ]
    // plugin magic happens here
]);
