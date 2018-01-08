<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Newsletter;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Render;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsletterController extends AbstractAdminController
{
    /**
     * Index page for newsletters.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData($request);
        $data['newsletters'] = ORM::getByQuery(Newsletter::class, 'SELECT * FROM newsmails ORDER BY sendt, id DESC');

        $content = Render::render('admin/emaillist', $data);

        return new Response($content);
    }

    /**
     * Page for editing or creating a newsletter.
     *
     * @param Request  $request
     * @param int|null $id
     *
     * @return Response
     */
    public function editNewsletter(Request $request, int $id = null): Response
    {
        $newsletter = null;
        if (null !== $id) {
            /** @var ?Newsletter */
            $newsletter = ORM::getOne(Newsletter::class, $id);
            if (!$newsletter) {
                throw new InvalidInput(_('Newsletter not found.'), 404);
            }
        }

        $data = [
            'newsletter'     => $newsletter,
            'recipientCount' => $newsletter ? $newsletter->countRecipients() : 0,
            'interests'      => config('interests', []),
            'textWidth'      => config('text_width'),
            'emails'         => array_keys(config('emails')),
        ] + $this->basicPageData($request);

        $content = Render::render('admin/viewemail', $data);

        return new Response($content);
    }

    /**
     * Creating a mewsletter.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $newsletter = new Newsletter([
            'from'       => $request->request->get('from'),
            'subject'    => $request->request->get('subject'),
            'html'       => $request->request->get('html'),
            'interests'  => $request->request->get('interests', []),
        ]);
        $newsletter->save();

        return new JsonResponse(['id' => $newsletter->getId()]);
    }

    /**
     * Update newsletter.
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
        /** @var ?Newsletter */
        $newsletter = ORM::getOne(Newsletter::class, $id);
        if (!$newsletter) {
            throw new InvalidInput(_('Newsletter not found.'), 404);
        }

        if ($newsletter->isSent()) {
            throw new InvalidInput(_('The newsletter has already been sent.'), 423);
        }

        $html = purifyHTML($request->get('html'));
        $newsletter->setFrom($request->get('from'))
            ->setHtml($html)
            ->setSubject($request->get('subject'))
            ->setInterests($request->get('interests', []))
            ->save();

        if ($request->request->getBoolean('send')) {
            $newsletter->send();
        }

        return new JsonResponse([]);
    }

    /**
     * Count recipients for given interests.
     *
     * @param Request $request
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function countRecipients(Request $request): JsonResponse
    {
        $newsletter = new Newsletter();
        $newsletter->setInterests($request->get('interests', []));

        return new JsonResponse(['count' => $newsletter->countRecipients()]);
    }
}
