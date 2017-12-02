<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\Email;
use AGCMS\Entity\User;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Request;
use AGCMS\Service\EmailService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Throwable;

class UserController extends AbstractAdminController
{
    /**
     * Index page for users.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        /** @var User[] */
        $users = ORM::getByQuery(
            User::class,
            'SELECT * FROM `users` ORDER BY ' . ($request->get('order') ? 'lastlogin' : 'fullname')
        );

        $data = [
            'title'       => _('Users and Groups'),
            'currentUser' => $request->user(),
            'users'       => $users,
        ] + $this->basicPageData($request);

        $content = Render::render('admin/users', $data);

        return new Response($content);
    }

    /**
     * Page for creating a new user.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function newUser(Request $request): Response
    {
        $request->startSession();
        /** @var SessionInterface */
        $session = $request->getSession();
        $message = $session->get('message', '');
        $session->remove('message');
        $session->save();

        $content = Render::render('admin/newuser', ['message' => $message]);

        return new Response($content);
    }

    /**
     * Create a user.
     *
     * The new user must be verified by an admin.
     *
     * @param Request $request
     *
     * @throws InvalidInput
     *
     * @return RedirectResponse
     */
    public function create(Request $request): RedirectResponse
    {
        $fullname = $request->get('fullname');
        $name = $request->get('name');
        $password = $request->get('password');

        $message = _('Your account has been created. An administrator will evaluate it shortly.');
        try {
            if (!$fullname || !$name || !$password) {
                throw new InvalidInput(_('All fields must be filled.'));
            }
            if ($password !== $request->get('password2')) {
                throw new InvalidInput(_('The passwords do not match.'));
            }
            if (db()->fetchOne('SELECT id FROM users WHERE name = ' . db()->eandq($name))) {
                throw new InvalidInput(_('Username already taken.'));
            }

            $user = new User([
                'full_name'     => $fullname,
                'nickname'      => $name,
                'password_hash' => '',
                'access_level'  => 0,
                'last_login'    => time(),
            ]);
            $user->setPassword($password)->save();

            $emailbody = Render::render('admin/email/newuser', ['fullname' => $fullname]);

            $emailAddress = first(Config::get('emails'))['address'];
            $email = new Email([
                'subject'          => _('New user'),
                'body'             => $emailbody,
                'senderName'       => Config::get('site_name'),
                'senderAddress'    => $emailAddress,
                'recipientName'    => Config::get('site_name'),
                'recipientAddress' => $emailAddress,
            ]);
            $emailService = new EmailService();
            try {
                $emailService->send($email);
            } catch (Throwable $exception) {
                app()->logException($exception);
                $email->save();
            }
        } catch (InvalidInput $exception) {
            $message = $exception->getMessage();
        }

        $request->startSession();
        /** @var SessionInterface */
        $session = $request->getSession();
        $session->set('message', $message);
        $session->save();

        return $this->redirect($request, '/admin/users/new/');
    }

    /**
     * Page for editing a user.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return Response
     */
    public function editUser(Request $request, int $id): Response
    {
        /** @var ?User */
        $user = ORM::getOne(User::class, $id);
        if (!$user) {
            throw new InvalidInput(_('User not found'));
        }

        $data = [
            'title'        => _('Edit') . ' ' . $user->getFullName(),
            'currentUser'  => $request->user(),
            'user'         => $user,
            'accessLevels' => [
                User::NO_ACCESS     => _('No access'),
                User::ADMINISTRATOR => _('Administrator'),
                User::MANAGER       => _('Manager'),
                User::CLERK         => _('Clerk'),
            ],
        ] + $this->basicPageData($request);

        $content = Render::render('admin/user', $data);

        return new Response($content);
    }

    /**
     * Update user.
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
        /** @var User */
        $user = $request->user();
        if (!$user->hasAccess(User::ADMINISTRATOR) && $user->getId() !== $id) {
            throw new InvalidInput(_('You do not have the requred access level to change other users.'));
        }

        // Validate access lavel update
        if ($user->getId() === $id
            && $request->request->getInt('access') !== $user->getAccessLevel()
        ) {
            throw new InvalidInput(_('You can\'t change your own access level'));
        }

        /** @var ?User */
        $user = ORM::getOne(User::class, $id);
        if (!$user) {
            throw new InvalidInput(_('User not found'));
        }

        // Validate password update
        $newPassword = $request->request->get('password_new');
        if ($newPassword) {
            if (!$user->hasAccess(User::ADMINISTRATOR) && $user->getId() !== $id) {
                throw new InvalidInput(
                    _('You do not have the requred access level to change the password for this users.')
                );
            }

            if ($user->getId() === $id && !$user->validatePassword($request->request->get('password'))) {
                throw new InvalidInput(_('Incorrect password.'));
            }

            $user->setPassword($newPassword);
        }

        if ($request->request->has('access')) {
            $user->setAccessLevel($request->request->getInt('access'));
        }

        if ($request->request->has('fullname')) {
            $user->setFullName($request->request->get('fullname', ''));
        }

        $user->save();

        return new JsonResponse([]);
    }

    /**
     * Delete a user.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        /** @var User */
        $user = $request->user();
        if (!$user->hasAccess(User::ADMINISTRATOR)) {
            throw new InvalidInput(_('You do not have permissions to edit users.'));
        }
        if ($user->getId() === $id) {
            throw new InvalidInput(_('You can\'t delete yourself.'));
        }

        /** @var ?User */
        $user = ORM::getOne(User::class, $id);
        if ($user) {
            $user->delete();
        }

        return new JsonResponse([]);
    }
}
