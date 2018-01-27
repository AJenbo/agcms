<?php namespace AGCMS;

use Twig_Environment;
use Twig_Extensions_Extension_I18n;
use Twig_Loader_Filesystem;

class Render
{
    /**
     * Render a template.
     *
     * @param string $template
     * @param array  $data
     *
     * @return string
     */
    public static function render(string $template = 'index', array $data = []): string
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

        $twig = new Twig_Environment($loader);
        if ('production' === config('enviroment', 'develop')) {
            $twig->setCache(app()->basePath('/theme/cache/twig'));
        }
        if ('develop' === config('enviroment', 'develop')) {
            $twig->enableDebug();
        }
        $twig->addExtension(new Twig_Extensions_Extension_I18n());

        return $twig->render($template . '.html', $data);
    }
}
