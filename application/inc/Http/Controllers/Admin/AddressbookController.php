<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\Contact;
use App\Services\ConfigService;
use App\Services\EmailService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AddressbookController extends AbstractAdminController
{
    /**
     * Index page for the addressbook.
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData($request);
        $order = $request->get('order');
        if (!in_array($order, ['email', 'tlf1', 'tlf2', 'post', 'adresse'], true)) {
            $order = 'navn';
        }
        $data['contacts'] = app(OrmService::class)->getByQuery(Contact::class, 'SELECT * FROM email ORDER BY ' . $order);

        return $this->render('admin/addressbook', $data);
    }

    /**
     * Page for editing or creating a contact.
     */
    public function editContact(Request $request, ?int $id = null): Response
    {
        $data = $this->basicPageData($request);
        $data['contact'] = $id ? app(OrmService::class)->getOne(Contact::class, $id) : null;
        $data['interests'] = ConfigService::getArray('interests');

        return $this->render('admin/editContact', $data);
    }

    /**
     * Creating a contact.
     */
    public function create(Request $request): JsonResponse
    {
        $contact = new Contact([
            'name'       => $request->getRequestString('name') ?? '',
            'email'      => $request->getRequestString('email') ?? '',
            'address'    => $request->getRequestString('address') ?? '',
            'country'    => $request->getRequestString('country') ?? '',
            'postcode'   => $request->getRequestString('postcode') ?? '',
            'city'       => $request->getRequestString('city') ?? '',
            'phone1'     => $request->request->getAlnum('phone1'),
            'phone2'     => $request->request->getAlnum('phone2'),
            'subscribed' => $request->request->getBoolean('newsletter'),
            'interests'  => $request->request->get('interests', []),
            'ip'         => $request->getClientIp(),
        ]);
        $contact->save();

        return new JsonResponse([]);
    }

    /**
     * Update contact.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $contact = app(OrmService::class)->getOne(Contact::class, $id);
        if (!$contact) {
            throw new InvalidInput(_('Contact not found.'), Response::HTTP_NOT_FOUND);
        }

        $interests = $request->request->get('interests');
        if (!is_array($interests)) {
            $interests = [];
        }

        $contact->setName($request->getRequestString('name') ?? '')
            ->setEmail($request->getRequestString('email') ?? '')
            ->setAddress($request->getRequestString('address') ?? '')
            ->setCountry($request->getRequestString('country') ?? '')
            ->setPostcode($request->getRequestString('postcode') ?? '')
            ->setCity($request->getRequestString('city') ?? '')
            ->setPhone1($request->request->getAlnum('phone1'))
            ->setPhone2($request->request->getAlnum('phone2'))
            ->setSubscribed($request->request->getBoolean('newsletter'))
            ->setInterests($interests)
            ->save();

        return new JsonResponse([]);
    }

    /**
     * Delete a contact.
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        $contact = app(OrmService::class)->getOne(Contact::class, $id);
        if ($contact) {
            $contact->delete();
        }

        return new JsonResponse(['id' => 'contact' . $id]);
    }

    /**
     * Check if an email is valid.
     */
    public function isValidEmail(Request $request): JsonResponse
    {
        $email = $request->get('email');
        if (!is_string($email)) {
            $email = '';
        }

        $isValid = app(EmailService::class)->valideMail($email);

        return new JsonResponse(['isValid' => $isValid]);
    }
}
