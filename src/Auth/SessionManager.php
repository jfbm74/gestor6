<?php

class SessionManager
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->startSession();
    }

    private function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($username, $role)
    {
        $_SESSION['user'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['login_time'] = time();
    }

    public function logout()
    {
        session_destroy();
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user']);
    }

    public function getUser()
    {
        return $_SESSION['user'] ?? null;
    }

    public function getRole()
    {
        return $_SESSION['role'] ?? '';
    }

    public function hasPermission($permission)
    {
        $role = $this->getRole();

        switch ($permission) {
            case 'read':
                return in_array($role, ['lectura', 'subir', 'super']);
            case 'upload':
                return in_array($role, ['subir', 'super']);
            case 'admin':
                return $role === 'super';
            default:
                return false;
        }
    }

    public function isSessionValid()
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $loginTime = $_SESSION['login_time'] ?? 0;
        $timeout = $this->config['security']['session_timeout'];

        if (time() - $loginTime > $timeout) {
            $this->logout();
            return false;
        }

        return true;
    }
}