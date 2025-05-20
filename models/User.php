<?php

require_once __DIR__ . '/../config/Database.php';

class User
{
    private $db;
    private $table = 'users';

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->prepare("SELECT user_id, email, first_name, last_name, phone_number, address, verified, status, is_organizer, profile_image_url, created_at, updated_at FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById($user_id)
    {
        $stmt = $this->db->prepare("SELECT  user_id, email, first_name, last_name, phone_number, address, verified, status, is_organizer, profile_image_url, created_at, updated_at FROM {$this->table} WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (email, first_name, last_name, phone_number, address, verified, is_organizer, profile_image_url, password_hash, status, token_version, created_at, updated_at)
            VALUES (:email, :first_name, :last_name, :phone_number, :address, :verified, :is_organizer, :profile_image_url, :password_hash, :status, :token_version, :created_at, :updated_at)
        ");

        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':phone_number', $data['phone_number']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':verified', $data['verified']);
        $stmt->bindParam(':is_organizer', $data['is_organizer']);
        $stmt->bindParam(':profile_image_url', $data['profile_image_url']);
        $stmt->bindParam(':password_hash', $data['password']);
        $stmt->bindParam(':status', 'active'); // Default status
        $stmt->bindParam(':token_version', 0); // Default token version
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':updated_at', $now);

        $stmt->execute();

        $user_id = $this->db->lastInsertId();

        return $this->findById($user_id);
    }

    public function update($user_id, $data)
    {
        // First check if record exists
        $user = $this->findById($user_id);

        if (!$user) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Build update query dynamically based on provided data
        $fields = [];
        $params = [':user_id' => $user_id, ':updated_at' => $now];

        foreach ($data as $key => $value) {
            if (in_array($key, ['first_name', 'last_name', 'phone_number', 'address', 'verified', 'is_organizer', 'profile_image_url',])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return $user; // Nothing to update
        }

        $fields[] = "updated_at = :updated_at";
        $fieldsStr = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$fieldsStr} WHERE user_id = :user_id");
        $stmt->execute($params);

        return $this->findById($user_id);
    }

    public function delete($user_id)
    {
        // First check if record exists
        $user = $this->findById($user_id);

        if (!$user) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return true;
    }


    public function softDelete($user_id)
    {
        // First check if record exists
        $user = $this->findById($user_id);
        if (!$user) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status, updated_at = :updated_at WHERE user_id = :user_id");
        $status = 'deleted';
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':updated_at', $now);
        $stmt->execute();

        return true;
    }
}
