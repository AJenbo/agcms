<?php namespace App\Http\Controllers\Admin;

use App\Exceptions\Handler as ExceptionHandler;
use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\Email;
use App\Models\User;
use App\Services\DbService;
use App\Services\EmailService;
use App\Services\OrmService;
use App\Services\RenderService;
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
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var User[] */
        $users = $orm->getByQuery(
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

        return $this->render('admin/newuser', ['message' => $message]);
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
                throw new InvalidInput(_('The passwords do not match.'), Response::HTTP_FORBIDDEN);
            }

            /** @var DbService */
            $db = app(DbService::class);

            /** @var OrmService */
            $orm = app(OrmService::class);

            if ($orm->getOneByQuery(User::class, 'SELECT * FROM users WHERE name = ' . $db->quote($name))) {
                throw new InvalidInput(_('Username already taken.'));
            }
            $firstUser = !(bool) $orm->getOneByQuery(User::class, 'SELECT * FROM users WHERE access != 0');

            $user = new User([
                'full_name'     => $fullname,
                'nickname'      => $name,
                'password_hash' => '',
                'access_level'  => $firstUser ? User::ADMINISTRATOR : User::NO_ACCESS,
                'last_login'    => time(),
            ]);
            $user->setPassword($password)->save();

            /** @var RenderService */
            $render = app(RenderService::class);

            $emailbody = $render->render('admin/email/newuser', ['fullname' => $fullname]);

            $emailAddress = first(config('emails'))['address'];
            $email = new Email([
                'subject'          => _('New user'),
                'body'             => $emailbody,
                'senderName'       => config('site_name'),
                'senderAddress'    => $emailAddress,
                'recipientName'    => config('site_name'),
                'recipientAddress' => $emailAddress,
            ]);
            /** @var EmailService */
            $emailService = app(EmailService::class);

            try {
                $emailService->send($email);
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
        /** @var SessionInterface */
        $session = $request->getSession();
        $session->set('message', $message);
        $session->save();

        return redirect('/admin/users/new/', Response::HTTP_SEE_OTHER);
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
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?User */
        $user = $orm->getOne(User::class, $id);
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
            throw new InvalidInput(_('You do not have permission to edit users.'), Response::HTTP_FORBIDDEN);
        }

        // Validate access lavel update
        if ($user->getId() === $id
            && $request->request->getInt('access') !== $user->getAccessLevel()
        ) {
            throw new InvalidInput(_('You can\'t change your own access level.'), Response::HTTP_FORBIDDEN);
        }

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?User */
        $user = $orm->getOne(User::class, $id);
        if (!$user) {
            throw new InvalidInput(_('User not found.'), Response::HTTP_NOT_FOUND);
        }

        // Validate password update
        $newPassword = $request->request->get('password_new');
        if ($newPassword) {
            if (!$user->hasAccess(User::ADMINISTRATOR) && $user->getId() !== $id) {
                throw new InvalidInput(
                    _('You do not have the required access level to change the password for this user.'),
                    Response::HTTP_FORBIDDEN
                );
            }

            if ($user->getId() === $id && !$user->validatePassword($request->request->get('password'))) {
                throw new InvalidInput(_('Incorrect password.'), Response::HTTP_FORBIDDEN);
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
            throw new InvalidInput(_('You do not have permission to edit users.'), Response::HTTP_FORBIDDEN);
        }
        if ($user->getId() === $id) {
            throw new InvalidInput(_('You can\'t delete yourself.'), Response::HTTP_FORBIDDEN);
        }

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?User */
        $user = $orm->getOne(User::class, $id);
        if ($user) {
            $user->delete();
        }

        return new JsonResponse([]);
    }
}
