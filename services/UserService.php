<?php

namespace Services;

require_once __DIR__ . '/../models/User.php';

use Config\Config;
use Utils\Paginator;
use Utils\TokenService;
use User;

class UserService
{
    private $userModel;
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
        $this->userModel = new User($this->db);
    }

    public function getAllUsers($page, $default)
    {       
        $data = $this->userModel->findAll();
        $response = new Paginator($data, $default, $page);
        return $response;
    }

    public function getUserById($id)
    {
        return $this->userModel->findById($id);
    }

    public function createUser($data)
    {
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        return $this->userModel->create($data);
    }

    public function updateUser($id, $data)
    {
        // Hash password if it exists
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->userModel->update($id, $data);
    }

    public function deleteUser($id)
    {
        return $this->userModel->delete($id);
    }

    public function login($email, $password)
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        $other_info = [
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'verified' => $user['verified']
        ];
        $token = TokenService::issueTokens($this->db, $user['user_id'], $other_info);

        return [
            'user' => [
                'user_id' => $user['user_id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'profile_image_url' => $user['profile_image_url']
            ],
            'token' => $token,
            'expires_in' => Config::getJwtExpiration()
        ];
    }
}
