<?php

namespace Matthiasjg;

use Kirby\Cms\App as Kirby;
use Kirby\Toolkit\F;
use D4L\StaticSiteGenerator as StaticSiteGenerator;

function resolveRelativePath(Kirby $kirby, string $path = null)
{
    if (!$path || strpos($path, '.') !== 0) {
        return realpath($path) ?: $path;
    }

    $path = $kirby->roots()->index() . DS . $path;
    return realpath($path) ?: $path;
}

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
                    'method'  => 'POST',
                    'action'  => function () use ($kirby) {

                        # 1. Build the Website via d4l/static_site_generator
                        $outputFolder = $kirby->option('d4l.static_site_generator.output_folder', './static');
                        $baseUrl = $kirby->option('d4l.static_site_generator.base_url', '/');
                        $preserve = $kirby->option('d4l.static_site_generator.preserve', []);
                        $skipMedia = $kirby->option('d4l.static_site_generator.skip_media', false);
                        $skipTemplates = array_diff($kirby->option('d4l.static_site_generator.skip_templates', []), ['home']);

                        $pages = $kirby->site()->index()->filterBy('intendedTemplate', 'not in', $skipTemplates);
                        $staticSiteGenerator = new StaticSiteGenerator($kirby, null, $pages);
                        $staticSiteGenerator->skipMedia($skipMedia);
                        $fileList = $staticSiteGenerator->generate($outputFolder, $baseUrl, $preserve);

                        # 2. Build the RSS Feed via bnomei/kirby3-feed
                        $posts = $kirby->collection('posts')->limit(10);
                        $feedOptions = [
                            'url'         => $baseUrl,
                            'title'       => $kirby->site()->title() . ' Feed',
                            'description' => 'Latest writing',
                            'link'        => $baseUrl,
                            'datefield'   => 'published',
                            'textfield'   => 'text'
                        ];
                        $outputPath = resolveRelativePath($kirby, $outputFolder);
                        $feeds = [
                            'rss'         => [
                                'filePath'  => $outputPath . '/feed/rss/index.xml',
                                'snippet'   => 'feed/rss'
                            ],
                            'json'         => [
                                'filePath'  => $outputPath . '/feed/json/index.json',
                                'snippet'   => 'feed/json'
                            ]
                        ];
                        foreach ($feeds as $type => $config) {
                            $feedOptions['snippet'] = $config['snippet'];
                            $feedResponse = $posts->feed($feedOptions);
                            F::write($config['filePath'], $feedResponse->body());
                            array_push($fileList, $config['filePath']);
                        }

                        # 3. Return composed result (file list and count), status
                        $fileList = str_replace($outputPath . '/', '', $fileList);
                        $fileList = array_map(function ($file) use ($baseUrl) {
                            return [
                                'text'   => trim($file, '/'),
                                'link'   => $baseUrl . ltrim($file, '/'),
                                'target' => '_blank'
                            ];
                        }, $fileList);
                        $fileCount = count($fileList);
                        return ['success' => true, 'files' => $fileList, 'message' => "$fileCount files generated / copied"];
                    }
                ]
            ];
        }
    ],
    'fields' => [
        'staticSiteComposer' => [
            'props' => [
                'label'    => 'Compose',
                'endpoint' => function () {
                    return $this->kirby()->option('matthiasjg.static_site_composer.endpoint');
                }
            ]
        ]
    ]
]);
