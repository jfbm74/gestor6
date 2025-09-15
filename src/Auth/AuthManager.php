<?php

class AuthManager
{
    private $config;
    private $sessionManager;

    public function __construct($config, SessionManager $sessionManager)
    {
        $this->config = $config;
        $this->sessionManager = $sessionManager;
    }

    public function authenticate($username, $password)
    {
        $users = $this->config['users'];

        if (!isset($users[$username])) {
            return false;
        }

        if ($users[$username]['password'] !== $password) {
            return false;
        }

        $this->sessionManager->login($username, $users[$username]['role']);
        return true;
    }

    public function logout()
    {
        $this->sessionManager->logout();
    }

    public function isAuthenticated()
    {
        return $this->sessionManager->isSessionValid();
    }

    public function getCurrentUser()
    {
        return $this->sessionManager->getUser();
    }

    public function getCurrentRole()
    {
        return $this->sessionManager->getRole();
    }

    public function hasPermission($permission)
    {
        return $this->sessionManager->hasPermission($permission);
    }

    public function requirePermission($permission)
    {
        if (!$this->hasPermission($permission)) {
            throw new Exception("Acceso denegado. Permisos insuficientes.");
        }
    }
}