<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

/**
 * UsersController — admin-only team management.
 *
 * Every method except where noted asserts the current user is admin. The
 * single non-admin path is `showPassword` / `updatePassword` for the user's
 * own account (which lives on AuthController).
 */
final class UsersController
{
    public function index(): void
    {
        Auth::assertAdmin();
        echo View::render('admin/users/index', [
            'users'        => User::all(),
            'message'      => Session::flash('users_message'),
            'errorMessage' => Session::flash('users_error'),
        ]);
    }

    public function showCreate(): void
    {
        Auth::assertAdmin();
        echo View::render('admin/users/form', [
            'mode'   => 'create',
            'user'   => null,
            'errors' => Session::flash('users_errors') ?: '',
            'old'    => Session::flash('users_old')    ?: '',
        ]);
    }

    public function create(): void
    {
        Auth::assertAdmin();

        $name     = trim((string) ($_POST['name']     ?? ''));
        $email    = trim((string) ($_POST['email']    ?? ''));
        $password = (string) ($_POST['password']      ?? '');
        $role     = (string) ($_POST['role']          ?? 'viewer');

        $errors = $this->validate($name, $email, $password, $role);

        if ($errors === [] && User::emailExists($email)) {
            $errors['email'] = 'A user with this email already exists.';
        }

        if ($errors !== []) {
            Session::flash('users_errors', json_encode($errors));
            Session::flash('users_old',    json_encode(compact('name', 'email', 'role')));
            header('Location: ' . View::url('admin/users/new'));
            return;
        }

        $id = User::create($name, $email, $password, $role);
        Session::flash('users_message', "User #{$id} created.");
        header('Location: ' . View::url('admin/users'));
    }

    public function showEdit(string $id): void
    {
        Auth::assertAdmin();
        $user = User::find((int) $id);
        if ($user === null) {
            http_response_code(404);
            return;
        }
        echo View::render('admin/users/form', [
            'mode'   => 'edit',
            'user'   => $user,
            'errors' => Session::flash('users_errors') ?: '',
            'old'    => Session::flash('users_old')    ?: '',
        ]);
    }

    public function update(string $id): void
    {
        Auth::assertAdmin();
        $userId = (int) $id;
        $user   = User::find($userId);
        if ($user === null) {
            http_response_code(404);
            return;
        }

        $name     = trim((string) ($_POST['name']  ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $role     = (string) ($_POST['role']       ?? 'viewer');
        $password = (string) ($_POST['password']   ?? '');

        $errors = [];
        if ($name === '')                        $errors['name']  = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
        if (!User::isValidRole($role))           $errors['role']  = 'Invalid role.';
        if ($password !== '' && strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';

        // Don't let the last admin lose their admin role.
        if ($errors === [] && $user['role'] === 'admin' && $role !== 'admin') {
            $admins = array_filter(User::all(), static fn ($u) => $u['role'] === 'admin');
            if (count($admins) <= 1) {
                $errors['role'] = 'You can\'t demote the only admin.';
            }
        }

        if ($errors === [] && User::emailExists($email, $userId)) {
            $errors['email'] = 'Another user already uses this email.';
        }

        if ($errors !== []) {
            Session::flash('users_errors', json_encode($errors));
            Session::flash('users_old',    json_encode(compact('name', 'email', 'role')));
            header('Location: ' . View::url('admin/users/' . $userId));
            return;
        }

        User::updateBasics($userId, $name, $email, $role);
        if ($password !== '') {
            User::updatePassword($userId, $password);
        }

        Session::flash('users_message', "User #{$userId} updated.");
        header('Location: ' . View::url('admin/users'));
    }

    public function delete(string $id): void
    {
        Auth::assertAdmin();
        $userId = (int) $id;

        if ($userId === Auth::id()) {
            Session::flash('users_error', 'You can\'t delete your own account.');
            header('Location: ' . View::url('admin/users'));
            return;
        }

        $target = User::find($userId);
        if ($target === null) {
            http_response_code(404);
            return;
        }

        if ($target['role'] === 'admin') {
            $admins = array_filter(User::all(), static fn ($u) => $u['role'] === 'admin');
            if (count($admins) <= 1) {
                Session::flash('users_error', 'Can\'t delete the only admin. Promote another user first.');
                header('Location: ' . View::url('admin/users'));
                return;
            }
        }

        User::delete($userId);
        Session::flash('users_message', "User #{$userId} deleted.");
        header('Location: ' . View::url('admin/users'));
    }

    /**
     * @return array<string,string>
     */
    private function validate(string $name, string $email, string $password, string $role): array
    {
        $errors = [];
        if ($name === '')                        $errors['name']     = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email']    = 'Valid email is required.';
        if (strlen($password) < 8)               $errors['password'] = 'Password must be at least 8 characters.';
        if (!User::isValidRole($role))           $errors['role']     = 'Invalid role.';
        return $errors;
    }
}
