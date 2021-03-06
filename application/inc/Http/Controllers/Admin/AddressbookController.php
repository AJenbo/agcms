<?php namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Models\Contact;
use App\Services\EmailService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        /** @var OrmService */
        $orm = app(OrmService::class);

        $data = $this->basicPageData($request);
        $order = $request->get('order');
        if (!in_array($order, ['email', 'tlf1', 'tlf2', 'post', 'adresse'], true)) {
            $order = 'navn';
        }
        $data['contacts'] = $orm->getByQuery(Contact::class, 'SELECT * FROM email ORDER BY ' . $order);

        return $this->render('admin/addressbook', $data);
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
        /** @var OrmService */
        $orm = app(OrmService::class);

        $data = $this->basicPageData($request);
        $data['contact'] = $id ? $orm->getOne(Contact::class, $id) : null;
        $data['interests'] = config('interests', []);

        return $this->render('admin/editContact', $data);
    }

    /**
     * Creating a contact.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $contact = new Contact([
            'name'       => $request->request->get('name', ''),
            'email'      => $request->request->get('email', ''),
            'address'    => $request->request->get('address', ''),
            'country'    => $request->request->get('country', ''),
            'postcode'   => $request->request->get('postcode', ''),
            'city'       => $request->request->get('city', ''),
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
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?Contact */
        $contact = $orm->getOne(Contact::class, $id);
        if (!$contact) {
            throw new InvalidInput(_('Contact not found.'), Response::HTTP_NOT_FOUND);
        }

        $contact->setName($request->request->get('name', ''))
            ->setEmail($request->request->get('email', ''))
            ->setAddress($request->request->get('address', ''))
            ->setCountry($request->request->get('country', ''))
            ->setPostcode($request->request->get('postcode', ''))
            ->setCity($request->request->get('city', ''))
            ->setPhone1($request->request->getAlnum('phone1'))
            ->setPhone2($request->request->getAlnum('phone2'))
            ->setSubscribed($request->request->getBoolean('newsletter'))
            ->setInterests($request->request->get('interests', []))
            ->save();

        return new JsonResponse([]);
    }

    /**
     * Delete a contact.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?Contact */
        $contact = $orm->getOne(Contact::class, $id);
        if ($contact) {
            $contact->delete();
        }

        return new JsonResponse(['id' => 'contact' . $id]);
    }

    /**
     * Check if an email is valid.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function isValidEmail(Request $request): JsonResponse
    {
        $email = $request->get('email', '');

        /** @var EmailService */
        $emailService = app(EmailService::class);
        $isValid = $emailService->valideMail($email);

        return new JsonResponse(['isValid' => $isValid]);
    }
}
