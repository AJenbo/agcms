<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Controller\AbstractController;
use AGCMS\Entity\Page;
use AGCMS\Render;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAdminController extends AbstractController
{
    /**
     * Admin index page.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData();

        $content = Render::render('admin/index', $data);

        return new Response($content);
    }

    /**
     * Basic admin render data.
     *
     * @return array
     */
    protected function basicPageData(): array
    {
        return [
            'title' => 'Administrator menu',
            'theme' => Config::get('theme', 'default'),
            'hide'  => [
                'activity'    => request()->cookies->get('hideActivity'),
                'binding'     => request()->cookies->get('hidebinding'),
                'categories'  => request()->cookies->get('hidekats'),
                'description' => request()->cookies->get('hidebeskrivelsebox'),
                'indhold'     => request()->cookies->get('hideIndhold'),
                'listbox'     => request()->cookies->get('hidelistbox'),
                'misc'        => request()->cookies->get('hidemiscbox'),
                'prices'      => request()->cookies->get('hidepriser'),
                'suplemanger' => request()->cookies->get('hideSuplemanger'),
                'tilbehor'    => request()->cookies->get('hidetilbehor'),
                'tools'       => request()->cookies->get('hideTools'),
            ],
        ];
    }
}
