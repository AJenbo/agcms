<?php namespace AGCMS\Controller\Admin;

use AGCMS\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractAdminController extends AbstractController
{
    /**
     * Basic admin render data.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function basicPageData(Request $request): array
    {
        return [
            'title' => 'Administrator menu',
            'theme' => config('theme', 'default'),
            'hide'  => [
                'activity'    => $request->cookies->get('hideActivity'),
                'binding'     => $request->cookies->get('hidebinding'),
                'categories'  => $request->cookies->get('hidekats'),
                'description' => $request->cookies->get('hidebeskrivelsebox'),
                'indhold'     => $request->cookies->get('hideIndhold'),
                'listbox'     => $request->cookies->get('hidelistbox'),
                'misc'        => $request->cookies->get('hidemiscbox'),
                'prices'      => $request->cookies->get('hidepriser'),
                'suplemanger' => $request->cookies->get('hideSuplemanger'),
                'tilbehor'    => $request->cookies->get('hidetilbehor'),
                'tools'       => $request->cookies->get('hideTools'),
            ],
        ];
    }
}
