<?php

namespace App\Services;

use App\TwigExtensions;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class RenderService
{
    private Environment $twig;

    public function __construct()
    {
        $app = app();
        $templatePath = $app->basePath('/theme');
        $loader = new FilesystemLoader('default/', $templatePath);
        $langPath = 'default/' . ConfigService::getString('locale', 'C') . '/';
        if (file_exists($templatePath . '/' . $langPath)) {
            $loader->prependPath($langPath);
        }
        $theme = ConfigService::getString('theme');
        if ($theme) {
            $loader->prependPath($theme . '/');
            $langPath = $theme . '/' . ConfigService::getString('locale', 'C') . '/';
            if (file_exists($templatePath . '/' . $langPath)) {
                $loader->prependPath($langPath);
            }
        }

        $this->twig = new Environment($loader);
        if ($app->environment('production')) {
            $this->twig->setCache($app->basePath('/theme/cache/twig'));
        }
        if ($app->environment('develop')) {
            $this->twig->enableDebug();
        }
        $this->twig->addExtension(new TwigExtensions());
    }

    /**
     * Render a template.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template = 'index', array $data = []): string
    {
        return $this->twig->render($template . '.html', $data);
    }
}
