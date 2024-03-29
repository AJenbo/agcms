<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Handler as ExceptionHandler;
use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\Email;
use App\Models\User;
use App\Services\ConfigService;
use App\Services\DbService;
use App\Services\EmailService;
use App\Services\OrmService;
use App\Services\RenderService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UserController extends AbstractAdminController
{
    /**
     * Index page for users.
     */
    public function index(Request $request): Response
    {
        $users = app(OrmService::class)->getByQuery(
            User::class,
            'SELECT * FROM `users` ORDER BY ' . ($request->get('order') ? 'lastlogin' : 'fullname')
        );

        $data = [
            'title'       => _('Users and Groups'),
            'currentUser' => $request->user(),
            'users'       => $users,
        ] + $this->basicPageData($request);

        return $this->render('admin/users', $data);
    }

    /**
     * Page for creating a new user.
     */
    public function newUser(Request $request): Response
    {
        $request->startSession();
        $session = $request->getSession();
        $message = $session->get('message', '');
        $session->remove('message');
        $session->save();

        return $this->render('admin/newuser', ['message' => $message]);
    }

    /**
     * Create a user.
     *
     * The new user must be verified by an admin.
     */
    public function create(Request $request): RedirectResponse
    {
        $fullname = $request->get('fullname');
        $name = $request->getRequestString('name') ?? '';
        $password = $request->getRequestString('password') ?? '';

        $message = _('Your account has been created. An administrator will evaluate it shortly.');

        try {
            if (!$fullname || !$name || !$password) {
                throw new InvalidInput(_('All fields must be filled.'));
            }
            if ($password !== $request->get('password2')) {
                throw new InvalidInput(_('The passwords do not match.'), Response::HTTP_FORBIDDEN);
            }

            $orm = app(OrmService::class);

            if ($orm->getOneByQuery(User::class, 'SELECT * FROM users WHERE name = ' . app(DbService::class)->quote($name))) {
                throw new InvalidInput(_('Username already taken.'));
            }
            $firstUser = !(bool)$orm->getOneByQuery(User::class, 'SELECT * FROM users WHERE access != 0');

            $user = new User([
                'full_name'     => $fullname,
                'nickname'      => $name,
                'password_hash' => '',
                'access_level'  => $firstUser ? User::ADMINISTRATOR : User::NO_ACCESS,
                'last_login'    => time(),
            ]);
            $user->setPassword($password)->save();

            $emailbody = app(RenderService::class)->render('admin/email/newuser', ['fullname' => $fullname]);

            $emailAddress = ConfigService::getDefaultEmail();
            $email = new Email([
                'subject'          => _('New user'),
                'body'             => $emailbody,
                'senderName'       => ConfigService::getString('site_name'),
                'senderAddress'    => $emailAddress,
                'recipientName'    => ConfigService::getString('site_name'),
                'recipientAddress' => $emailAddress,
            ]);

            try {
                app(EmailService::class)->send($email);
            } catch (Throwable $exception) {
                /** @var ExceptionHandler */
                $handler = app(ExceptionHandler::class);
                $handler->report($exception);
                $email->save();
            }
        } catch (InvalidInput $exception) {
            $message = $exception->getMessage();
        }

        $request->startSession();
        $session = $request->getSession();
        $session->set('message', $message);
        $session->save();

        return redirect('/admin/users/new/', Response::HTTP_SEE_OTHER);
    }

    public function editUser(Request $request, int $id): Response
    {
        $user = app(OrmService::class)->getOne(User::class, $id);
        if (!$user) {
            throw new InvalidInput(_('User not found.'), Response::HTTP_NOT_FOUND);
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

        return $this->render('admin/user', $data);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasAccess(User::ADMINISTRATOR) && $user->getId() !== $id)) {
            throw new InvalidInput(_('You do not have permission to edit users.'), Response::HTTP_FORBIDDEN);
        }

        // Validate access lavel update
        if ($user->getId() === $id
            && $request->request->getInt('access') !== $user->getAccessLevel()
        ) {
            throw new InvalidInput(_('You can\'t change your own access level.'), Response::HTTP_FORBIDDEN);
        }

        $user = app(OrmService::class)->getOne(User::class, $id);
        if (!$user) {
            throw new InvalidInput(_('User not found.'), Response::HTTP_NOT_FOUND);
        }

        // Validate password update
        $newPassword = $request->getRequestString('password_new');
        if ($newPassword) {
            if (!$user->hasAccess(User::ADMINISTRATOR) && $user->getId() !== $id) {
                throw new InvalidInput(
                    _('You do not have the required access level to change the password for this user.'),
                    Response::HTTP_FORBIDDEN
                );
            }

            $password = $request->getRequestString('password');
            if (!$password || ($user->getId() === $id && !$user->validatePassword($password))) {
                throw new InvalidInput(_('Incorrect password.'), Response::HTTP_FORBIDDEN);
            }

            $user->setPassword($newPassword);
        }

        if ($request->request->has('access')) {
            $user->setAccessLevel($request->request->getInt('access'));
        }

        if ($request->request->has('fullname')) {
            $user->setFullName($request->getRequestString('fullname') ?? '');
        }

        $user->save();

        return new JsonResponse([]);
    }

    public function delete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasAccess(User::ADMINISTRATOR)) {
            throw new InvalidInput(_('You do not have permission to edit users.'), Response::HTTP_FORBIDDEN);
        }
        if ($user->getId() === $id) {
            throw new InvalidInput(_('You can\'t delete yourself.'), Response::HTTP_FORBIDDEN);
        }

        $user = app(OrmService::class)->getOne(User::class, $id);
        if ($user) {
            $user->delete();
        }

        return new JsonResponse([]);
    }
}
