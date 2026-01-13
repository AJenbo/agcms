<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\Newsletter;
use App\Services\ConfigService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NewsletterController extends AbstractAdminController
{
    /**
     * Index page for newsletters.
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData($request);
        $data['newsletters'] = app(OrmService::class)->getByQuery(
            Newsletter::class,
            'SELECT * FROM newsmails ORDER BY sendt, id DESC'
        );

        return $this->render('admin/emaillist', $data);
    }

    /**
     * Page for editing or creating a newsletter.
     */
    public function editNewsletter(Request $request, ?int $id = null): Response
    {
        $newsletter = null;
        if (null !== $id) {
            $newsletter = app(OrmService::class)->getOne(Newsletter::class, $id);
            if (!$newsletter) {
                throw new InvalidInput(_('Newsletter not found.'), Response::HTTP_NOT_FOUND);
            }
        }

        $data = [
            'newsletter'     => $newsletter,
            'recipientCount' => $newsletter ? $newsletter->countRecipients() : 0,
            'interests'      => ConfigService::getArray('interests'),
            'textWidth'      => ConfigService::getInt('text_width'),
            'emails'         => array_keys(ConfigService::getEmailConfigs()),
        ] + $this->basicPageData($request);

        return $this->render('admin/viewemail', $data);
    }

    /**
     * Creating a mewsletter.
     */
    public function create(Request $request): JsonResponse
    {
        $html = purifyHTML($request->getRequestString('html') ?? '');
        $newsletter = new Newsletter([
            'from'       => $request->getRequestString('from'),
            'subject'    => $request->getRequestString('subject'),
            'html'       => $html,
            'interests'  => $request->request->all('interests'),
        ]);
        $newsletter->save();

        return new JsonResponse(['id' => $newsletter->getId()]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $newsletter = app(OrmService::class)->getOne(Newsletter::class, $id);
        if (!$newsletter) {
            throw new InvalidInput(_('Newsletter not found.'), Response::HTTP_NOT_FOUND);
        }

        if ($newsletter->isSent()) {
            throw new InvalidInput(_('The newsletter has already been sent.'), Response::HTTP_LOCKED);
        }

        $html = purifyHTML($request->getRequestString('html') ?? '');

        $interests = [];
        foreach ($request->request->all('interests') as $interest) {
            $interests[] = valstring($interest);
        }

        $newsletter->setFrom($request->getRequestString('from') ?? '')
            ->setHtml($html)
            ->setSubject($request->getRequestString('subject') ?? '')
            ->setInterests($interests)
            ->save();

        if ($request->request->getBoolean('send')) {
            $newsletter->send();
        }

        return new JsonResponse([]);
    }

    /**
     * Count recipients for given interests.
     */
    public function countRecipients(Request $request): JsonResponse
    {
        $newsletter = new Newsletter();
        $interests = [];
        foreach ($request->query->all('interests') as $interest) {
            $interests[] = valstring($interest);
        }
        $newsletter->setInterests($interests);

        return new JsonResponse(['count' => $newsletter->countRecipients()]);
    }
}
