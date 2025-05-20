<?php

require_once __DIR__ . '/../config/Database.php';

class Order
{
    private $db;
    private $table = 'orders';

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->prepare("SELECT order_id, order_reference, email, guest_name, status, total_amount, created_at, updated_at FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById($order_id)
    {
        $stmt = $this->db->prepare("SELECT order_id, order_reference, email, guest_name, status, total_amount, created_at, updated_at FROM {$this->table} WHERE order_id = :order_id");
        $stmt->bindParam(':order_id', $order_id);
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

    public function findByReference($order_reference)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE order_reference = :order_reference");
        $stmt->bindParam(':order_reference', $order_reference);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (order_reference, email, guest_name, status, total_amount, created_at, updated_at)
            VALUES (:order_reference, :email, :guest_name, :status, :total_amount, :created_at, :updated_at)
        ");

        $stmt->bindParam(':order_reference', $data['order_reference']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':guest_name', $data['guest_name']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':total_amount', $data['total_amount']);
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':updated_at', $now);

        $stmt->execute();

        $order_id = $this->db->lastInsertId();

        return $this->findById($order_id);
    }

    public function update($order_id, $data)
    {
        // First check if record exists
        $order = $this->findById($order_id);

        if (!$order) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Build update query dynamically based on provided data
        $fields = [];
        $params = [':order_id' => $order_id, ':updated_at' => $now];

        foreach ($data as $key => $value) {
            if (in_array($key, ['order_reference', 'email', 'guest_name', 'status', 'total_amount'])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return $order; // Nothing to update
        }

        $fields[] = "updated_at = :updated_at";
        $fieldsStr = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$fieldsStr} WHERE order_id = :order_id");
        $stmt->execute($params);

        return $this->findById($order_id);
    }

    public function delete($order_id)
    {
        // First check if record exists
        $order = $this->findById($order_id);

        if (!$order) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE order_id = :order_id");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();

        return true;
    }


    public function softDelete($order_id)
    {
        // First check if record exists
        $order = $this->findById($order_id);
        if (!$order) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status, updated_at = :updated_at WHERE order_id = :order_id");
        $status = 'deleted';
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':updated_at', $now);
        $stmt->execute();

        return true;
    }
}
