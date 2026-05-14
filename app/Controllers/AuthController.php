<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        $error = Session::flash('login_error');
        echo View::render('auth/login', [
            'error' => $error,
            'email' => (string) Session::flash('login_email'),
        ], layout: 'blank');
    }

    public function login(): void
    {
        $email    = (string) ($_POST['email']    ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            Session::flash('login_error', 'Email and password are required.');
            Session::flash('login_email', $email);
            header('Location: ' . View::url('login'));
            return;
        }

        if (!Auth::attempt($email, $password)) {
            Session::flash('login_error', 'Those credentials don\'t match. If you\'ve been locked out, try again in 5 minutes.');
            Session::flash('login_email', $email);
            header('Location: ' . View::url('login'));
            return;
        }

        header('Location: ' . View::url(''));
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . View::url('login'));
    }

    /**
     * GET /me/password — form for the current user to change their own password.
     */
    public function showPassword(): void
    {
        echo View::render('auth/password', [
            'message' => Session::flash('password_message'),
            'error'   => Session::flash('password_error'),
        ]);
    }

    /**
     * POST /me/password — process the password change.
     */
    public function updatePassword(): void
    {
        $current = (string) ($_POST['current']  ?? '');
        $next    = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm']  ?? '');

        $user = Auth::user();
        if ($user === null) {
            header('Location: ' . View::url('login'));
            return;
        }

        if (!password_verify($current, $user['password_hash'])) {
            Session::flash('password_error', 'Current password is incorrect.');
            header('Location: ' . View::url('me/password'));
            return;
        }

        if (strlen($next) < 8) {
            Session::flash('password_error', 'New password must be at least 8 characters.');
            header('Location: ' . View::url('me/password'));
            return;
        }
        if ($next !== $confirm) {
            Session::flash('password_error', 'New password and confirmation do not match.');
            header('Location: ' . View::url('me/password'));
            return;
        }

        \App\Models\User::updatePassword((int) $user['id'], $next);
        session_regenerate_id(true);
        Session::flash('password_message', 'Password updated.');
        header('Location: ' . View::url('me/password'));
    }
}
