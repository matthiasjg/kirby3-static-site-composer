<?php

namespace Matthiasjg;

use Kirby\Cms\App as Kirby;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Dir;
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

                        $kirbyBaseUrl = $kirby->url('index');
                        $homeFolder = $kirby->site()->homePageId();

                        # 1. Build the Website via d4l/static_site_generator
                        $outputFolder = $kirby->option('matthiasjg.static_site_composer.output_folder', './static');
                        $baseUrl = $kirby->option('matthiasjg.static_site_composer.base_url', '/');
                        $preserve = $kirby->option('matthiasjg.static_site_composer.preserve', []);
                        $skipMedia = $kirby->option('matthiasjg.static_site_composer.skip_media', false);
                        $skipTemplates = array_diff($kirby->option('matthiasjg.static_site_composer.skip_templates', []), ['home']);
                        $pagesParentHomeRoot = $kirby->option('matthiasjg.static_site_composer.pages_parent_home_root', false);

                        $pages = $kirby->site()->index()->filterBy('intendedTemplate', 'not in', $skipTemplates);
                        $staticSiteGenerator = new StaticSiteGenerator($kirby, null, $pages);
                        $staticSiteGenerator->skipMedia($skipMedia);
                        $fileList = [
                            'pages' => [],
                            'feeds' => []
                        ];
                        $fileList['pages'] = $staticSiteGenerator->generate($outputFolder, $baseUrl, $preserve);

                        if ($pagesParentHomeRoot) {
                            $fileList['pages'] = array_map(function ($page) use ($homeFolder, $outputFolder) {
                                if (strpos($page, $homeFolder)) {
                                    $pageDir = F::dirname($page);
                                    Dir::move($pageDir, $outputFolder . DS . F::name($pageDir));
                                    $page = str_replace($homeFolder . '/', '', $page);
                                }
                                return $page;
                            }, $fileList['pages']);
                            $homeDir = $outputFolder . DS . $homeFolder;
                            if (Dir::exists($homeDir) && Dir::isEmpty($homeDir)) {
                                Dir::remove($homeDir);
                            }
                        }
                        sort($fileList['pages']);

                        # 2. Build the RSS Feed via bnomei/kirby3-feed
                        $feedFormats = $kirby->option('matthiasjg.static_site_composer.feed_formats', ['rss', 'json']);
                        $feedDescription = $kirby->option('matthiasjg.static_site_composer.feed_description', 'Latest posts from the blog');
                        $feedCollection = $kirby->option('matthiasjg.static_site_composer.feed_collection', 'posts');
                        $feedCollectionLimit = $kirby->option('matthiasjg.static_site_composer.feed_collection_limit', 10);
                        $feedCollectionDatefield = $kirby->option('matthiasjg.static_site_composer.feed_collection_datefield', 'date');
                        $feedCollectionTextfield = $kirby->option('matthiasjg.static_site_composer.feed_collection_textfield', 'text');


                        $posts = $kirby->collection($feedCollection)->limit($feedCollectionLimit);
                        $posts = $posts->map(function ($post) use ($pagesParentHomeRoot, $homeFolder, $kirbyBaseUrl, $baseUrl) {
                            if ($post->url()) {
                                $url = $post->url();
                                if ($pagesParentHomeRoot) {
                                    $url = str_replace('/' . $homeFolder . '/', '', $url);
                                }
                                $post->update([ // persist field in page data
                                    'feedurl' => str_replace($kirbyBaseUrl, $baseUrl, $url)
                                ]);
                            }
                            return $post;
                        });
                        $feedOptions = [
                            'url'         => $baseUrl,
                            'title'       => $kirby->site()->title() . ' Feed',
                            'description' => $feedDescription,
                            'link'        => $baseUrl,
                            'datefield'   => $feedCollectionDatefield,
                            'textfield'   => $feedCollectionTextfield,
                            'urlfield'    => 'feedurl'
                        ];
                        $outputPath = resolveRelativePath($kirby, $outputFolder);
                        foreach ($feedFormats as $feedFormat) {
                            $feedOptions['snippet'] = 'feed/' . $feedFormat;
                            $feedOptions['feedurl'] = $baseUrl . 'feed.' . $feedFormat;
                            $feedFilepath = $outputPath . '/feed.' . $feedFormat;
                            $feedResponse = $posts->feed($feedOptions);
                            F::write($feedFilepath, $feedResponse->body());
                            array_push($fileList['feeds'], $feedFilepath);
                        }
                        sort($fileList['feeds']);

                        # 3. Return composed result (file list and count), status
                        foreach (array_keys($fileList) as &$type) {
                            $fileList[$type] = str_replace($outputPath, '', $fileList[$type]);
                            $fileList[$type] = str_replace('//', '/', $fileList[$type]);
                            $fileList[$type] = array_unique($fileList[$type]);
                            $fileList[$type] = array_map(function ($file) use ($baseUrl) {
                                return [
                                    'text'   => trim($file, '/'),
                                    'link'   => $baseUrl . ltrim($file, '/')
                                ];
                            }, $fileList[$type]);
                        }
                        $fileCount = count($fileList['pages']);
                        $feedCount = count($fileList['feeds']);
                        $message = "Generated and copied $fileCount page and $feedCount feed files.";

                        return ['success' => true, 'files' => $fileList, 'message' => $message];
                    }
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
]);
