<?php
namespace Mainio\C5\Twig;

use Concrete\Core\Application\Application;
use Concrete\Core\Foundation\Service\Provider as ServiceProvider;
use Mainio\C5\Twig\Page\PathResolver;
use Mainio\C5\Twig\Service\Twig as TwigService;
use Package;

/**
 * This class needs to be initialized in the Package's on_start method if this
 * is needed for the package context. If we need this in the Application
 * context, we'll need to initiate this in the Application's bootstrap process.
 *
 * This will provide a context specific
 */
class TwigServiceProvider extends ServiceProvider
{

    protected ?Package $pkg;

    public function __construct(Application $app, Package $pkg = null)
    {
        parent::__construct($app);

        $this->pkg = $pkg;
    }

    public function register()
    {
        $prefix = '';
        if (is_object($this->pkg)) {
            $basePath = $this->pkg->getPackagePath();
            $prefix = $this->pkg->getPackageHandle() . '/';
        } else {
            $basePath = DIR_APPLICATION;
        }

        $twigService = new TwigService($this->pkg);

        // We want the vendor dir from this particular library repository.
        $vendorDir = __DIR__;
        for ($i = 0; $i < 6; $i++) {
            $vendorDir = dirname($vendorDir);
        }

        $paths = ['base' => $basePath, 'lib' => $vendorDir, 'cache' => $twigService->getCacheDirectory()];
        $singletons = [
            'twig' => function () use ($twigService) {
                return $twigService;
            },
            'environment/twig' => function ($app) use ($paths) {
                $translator = $app->make('twig/translator');
                return Factory::createEnvironment($paths, $translator);
            }
        ];

        foreach ($singletons as $key => $value) {
            $this->app->singleton($prefix . $key, $value);
        }

        // These are globally defined without a context prefix because the
        // object instance is always the same regardless of the context.
        // There is no need to have package specific Translator objects for
        // every package.
        $singletons = [
            'twig/translator' => function () {
                return Factory::createTranslator(\Localization::activeLocale());
            },
        ];
        foreach ($singletons as $key => $value) {
            // No need to bind the singleton multiple times if it is already
            // bound. If this is used in multiple packages, the one that binds
            // the instance the first will win.
            if (!$this->app->bound($key)) {
                $this->app->singleton($key, $value);
            }
        }

        // Non-singleton binds in the shared context.
        $binds = [
            'page/path_resolver' => function ($app, array $params) {
                $pkg = isset($params[0]) && is_object($params[0]) ? $params[0] : null;
                $pr = new PathResolver($pkg);
                $pr->addFileExtension('php');
                $pr->addFileExtension('html.twig');
                return $pr;
            },
        ];
        foreach ($binds as $key => $value) {
            if (!$this->app->bound($key)) {
                $this->app->bind($key, $value);
            }
        }
    }

    public function registerCoreOverrides()
    {
        $overrides = [
            \Concrete\Core\Page\Page::class => \Mainio\C5\Twig\Core\Override\Page\Page::class,
            \Concrete\Core\Page\Single::class => \Mainio\C5\Twig\Core\Override\Page\Single::class,
        ];

        foreach ($overrides as $cls => $ovr) {
            class_alias($ovr, $cls);

            // Make sure other aliases defined after this will not affect the
            // loading of these overrides.
            class_exists($cls);
        }
    }

}
