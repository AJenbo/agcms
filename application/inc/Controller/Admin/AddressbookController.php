<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\Contact;
use AGCMS\Entity\Requirement;
use AGCMS\ORM;
use AGCMS\Render;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AddressbookController extends AbstractAdminController
{
    /**
     * Index page for the addressbook.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData($request);
        $order = $request->get('order');
        if (!in_array($order, ['email', 'tlf1', 'tlf2', 'post', 'adresse'], true)) {
            $order = 'navn';
        }
        $data['contacts'] = ORM::getByQuery(Contact::class, 'SELECT * FROM email ORDER BY ' . $order);
        $content = Render::render('admin/addressbook', $data);

        return new Response($content);
    }

    /**
     * Page for editing or creating a contact.
     *
     * @param Request  $request
     * @param int|null $id
     *
     * @return Response
     */
    public function editContact(Request $request, int $id = null): Response
    {
        $data = $this->basicPageData($request);
        $data['contact'] = $id ? ORM::getOne(Contact::class, $id) : null;
        $data['interests'] = Config::get('interests', []);

        $content = Render::render('admin/editContact', $data);

        return new Response($content);
    }
}
