<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Session;
use App\Core\CsrfManager;
use App\Models\UserModel;
use App\Models\LoginAttemptModel;

class AuthController extends BaseController
{
    public function redirectDashboard(): void
    {
        if (!empty($_SESSION['user_id']) && Session::checkIdle()) {
            Response::redirect('/dashboard');
        }
        Response::redirect('/login');
    }

    public function showLogin(): void
    {
        if (!empty($_SESSION['user_id'])) {
            Response::redirect('/dashboard');
        }
        $flash  = Session::getFlash('success');
        $reason = $this->request->query('reason');
        $this->render('pages.login', [
            'layout'    => 'layouts.auth',
            'pageTitle' => 'Login — TraceOn',
            'csrf'      => CsrfManager::generate(),
            'flash'     => $flash,
            'reason'    => $reason,
        ]);
    }

    public function showRegister(): void
    {
        if (!empty($_SESSION['user_id'])) {
            Response::redirect('/dashboard');
        }
        $this->render('pages.register', [
            'layout'    => 'layouts.auth',
            'pageTitle' => 'Daftar — TraceOn',
            'csrf'      => CsrfManager::generate(),
        ]);
    }

    public function login(): void
    {
        $this->requireCsrf();

        $email    = trim((string)$this->request->input('email', ''));
        $password = (string)$this->request->input('password', '');
        $ip       = $this->request->ip();

        if ($email === '' || $password === '') {
            Response::error('INVALID_CREDENTIALS', 'Kredensial tidak valid', 401);
        }

        // Check active IP block
        $block = LoginAttemptModel::findActiveBlock($ip, 'login');
        if ($block) {
            $mins = (int)ceil(max(1, (int)$block['seconds_remaining']) / 60);
            Response::error('RATE_LIMITED', 'Terlalu banyak percobaan. Coba lagi dalam ' . $mins . ' menit.', 429);
        }

        // Lazy cleanup expired blocks
        LoginAttemptModel::purgeExpiredBlocks();

        $user = UserModel::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            LoginAttemptModel::registerFailure($ip, 'login');

            $newBlock = LoginAttemptModel::findActiveBlock($ip, 'login');
            if ($newBlock) {
                $mins = (int)ceil(max(1, (int)$newBlock['seconds_remaining']) / 60);
                Response::error('RATE_LIMITED', 'Terlalu banyak percobaan. Coba lagi dalam ' . $mins . ' menit.', 429);
            }

            Response::error('INVALID_CREDENTIALS', 'Kredensial tidak valid', 401);
        }

        // Successful login
        LoginAttemptModel::reset($ip, 'login');
        session_regenerate_id(true);

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_name']     = $user['name'];
        $_SESSION['user_email']    = $user['email'];
        $_SESSION['user_avatar']   = $user['avatar_path'];
        $_SESSION['last_activity'] = time();

        CsrfManager::rotate();

        Response::success(['redirect' => '/dashboard'], 'Login berhasil');
    }

    public function register(): void
    {
        $this->requireCsrf();

        // Honeypot: if filled → silent reject (fools bots)
        if ((string)$this->request->input('website', '') !== '') {
            Response::success(['redirect' => '/login'], 'Registrasi berhasil. Silakan masuk.');
        }

        $name     = trim((string)$this->request->input('name', ''));
        $email    = trim((string)$this->request->input('email', ''));
        $password = (string)$this->request->input('password', '');
        $confirm  = (string)$this->request->input('confirm_password', '');

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Nama tidak boleh kosong';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Nama maksimal 100 karakter';
        }

        if ($email === '') {
            $errors['email'] = 'Email tidak boleh kosong';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid';
        } elseif (mb_strlen($email) > 100) {
            $errors['email'] = 'Email maksimal 100 karakter';
        } elseif (empty($errors['email']) && UserModel::existsByEmail($email)) {
            Response::error('EMAIL_TAKEN', 'Email sudah digunakan', 422);
        }

        if ($password === '') {
            $errors['password'] = 'Password tidak boleh kosong';
        } elseif (mb_strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter';
        } elseif (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password harus mengandung minimal 1 huruf dan 1 angka';
        }

        if ($confirm !== $password) {
            $errors['confirm_password'] = 'Konfirmasi password tidak cocok';
        }

        if (!empty($errors)) {
            Response::json(['success' => false, 'error' => 'VALIDATION_ERROR', 'errors' => $errors], 422);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        UserModel::create($name, $email, $hash);

        Session::flash('success', 'Registrasi berhasil. Silakan masuk.');
        Response::success(['redirect' => '/login'], 'Registrasi berhasil. Silakan masuk.');
    }

    public function logout(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $isProduction = (APP_ENV === 'production');

        Session::destroy();

        setcookie(SESSION_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $isProduction,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        Session::start();
        CsrfManager::rotate();

        Response::success(['redirect' => '/login'], 'Berhasil keluar');
    }
}
