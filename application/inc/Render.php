<?php namespace AGCMS;

use Twig_Environment;
use Twig_Extensions_Extension_I18n;
use Twig_Loader_Filesystem;

class Render
{
    /** @var Twig_Environment */
    private $twig;

    public function __construct()
    {
        $templatePath = app()->basePath('/theme');
        $loader = new Twig_Loader_Filesystem('default/', $templatePath);
        $langPath = 'default/' . config('locale', 'C') . '/';
        if (file_exists($templatePath . '/' . $langPath)) {
            $loader->prependPath($langPath);
        }
        if (config('theme')) {
            $loader->prependPath(config('theme') . '/');
            $langPath = config('theme') . '/' . config('locale', 'C') . '/';
            if (file_exists($templatePath . '/' . $langPath)) {
                $loader->prependPath($langPath);
            }
        }

        $this->twig = new Twig_Environment($loader);
        if ('production' === config('enviroment', 'develop')) {
            $this->twig->setCache(app()->basePath('/theme/cache/twig'));
        }
        if ('develop' === config('enviroment', 'develop')) {
            $this->twig->enableDebug();
        }
        $this->twig->addExtension(new Twig_Extensions_Extension_I18n());
    }

    /**
     * Render a template.
     *
     * @param string $template
     * @param array  $data
     *
     * @return string
     */
    public function render(string $template = 'index', array $data = []): string
    {
        return $this->twig->render($template . '.html', $data);
    }
}
