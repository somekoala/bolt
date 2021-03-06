<?php
namespace Bolt\Provider;

use Bolt\Twig\Handler;
use Bolt\Twig\TwigExtension;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TwigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['twig'])) {
            $app->register(new \Silex\Provider\TwigServiceProvider());
        }

        // Handlers
        $app['twig.handlers'] = $app->share(
            function (Application $app) {
                return new \Pimple(
                    [
                        // @codingStandardsIgnoreStart
                        'admin'  => $app->share(function () use ($app) { return new Handler\AdminHandler($app); }),
                        'array'  => $app->share(function () use ($app) { return new Handler\ArrayHandler($app); }),
                        'html'   => $app->share(function () use ($app) { return new Handler\HtmlHandler($app); }),
                        'image'  => $app->share(function () use ($app) { return new Handler\ImageHandler($app); }),
                        'record' => $app->share(function () use ($app) { return new Handler\RecordHandler($app); }),
                        'text'   => $app->share(function () use ($app) { return new Handler\TextHandler($app); }),
                        'user'   => $app->share(function () use ($app) { return new Handler\UserHandler($app); }),
                        'utils'  => $app->share(function () use ($app) { return new Handler\UtilsHandler($app); }),
                        'widget' => $app->share(function () use ($app) { return new Handler\WidgetHandler($app); }),
                        // @codingStandardsIgnoreEnd
                    ]
                );
            }
        );

        // Add the Bolt Twig Extension.
        $app['twig'] = $app->share(
            $app->extend(
                'twig',
                function (\Twig_Environment $twig, $app) {
                    $twig->addExtension(new TwigExtension($app, $app['twig.handlers'], false));

                    return $twig;
                }
            )
        );

        $app['twig.loader.filesystem'] = $app->share(
            $app->extend(
                'twig.loader.filesystem',
                function ($filesystem, $app) {
                    $filesystem->addPath($app['resources']->getPath('app/view/twig'), 'bolt');

                    // @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
                    $filesystem->addPath($app['resources']->getPath('app/view/twig'));

                    return $filesystem;
                }
            )
        );

        // Twig paths
        $app['twig.path'] = function () use ($app) {
            return $app['config']->getTwigPath();
        };

        // Twig options
        $app['twig.options'] = function () use ($app) {
            // Should we cache or not?
            if ($app['config']->get('general/caching/templates')) {
                $cache = $app['resources']->getPath('cache');
            } else {
                $cache = false;
            }

            return [
                'debug'            => true,
                'cache'            => $cache,
                'strict_variables' => $app['config']->get('general/strict_variables'),
                'autoescape'       => true,
            ];
        };

        $app['safe_twig.bolt_extension'] = function () use ($app) {
            return new TwigExtension($app, $app['twig.handlers'], true);
        };

        $app['safe_twig'] = $app->share(
            function ($app) {
                $loader = new \Twig_Loader_String();
                $twig = new \Twig_Environment($loader);
                $twig->addExtension($app['safe_twig.bolt_extension']);

                return $twig;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
