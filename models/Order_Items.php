<?php

require_once __DIR__ . '/../config/Database.php';

class OrderItem
{
    private $db;
    private $table = 'order_items';

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->prepare("SELECT item_id, order_id, ticket_type_id, quantity, price_per_unit, created_at, updated_at FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById($item_id)
    {
        $stmt = $this->db->prepare("SELECT item_id, order_id, ticket_type_id, quantity, price_per_unit, created_at, updated_at FROM {$this->table} WHERE item_id = :item_id");
        $stmt->bindParam(':item_id', $item_id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findByOrderId($order_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE order_id = :order_id");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (order_id, ticket_type_id, quantity, price_per_unit, created_at, updated_at)
            VALUES (:order_id, :ticket_type_id, :quantity, :price_per_unit, :created_at, :updated_at)
        ");

        $stmt->bindParam(':order_id', $data['order_id']);
        $stmt->bindParam(':ticket_type_id', $data['ticket_type_id']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':price_per_unit', $data['price_per_unit']);
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':updated_at', $now);

        $stmt->execute();

        $item_id = $this->db->lastInsertId();

        return $this->findById($item_id);
    }

    public function update($item_id, $data)
    {
        // First check if record exists
        $order_item = $this->findById($item_id);

        if (!$order_item) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Build update query dynamically based on provided data
        $fields = [];
        $params = [':item_id' => $item_id, ':updated_at' => $now];

        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'email', 'password'])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return $order_item; // Nothing to update
        }

        $fields[] = "updated_at = :updated_at";
        $fieldsStr = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$fieldsStr} WHERE item_id = :item_id");
        $stmt->execute($params);

        return $this->findById($item_id);
    }

    public function delete($item_id)
    {
        // First check if record exists
        $order_item = $this->findById($item_id);

        if (!$order_item) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE item_id = :item_id");
        $stmt->bindParam(':item_id', $item_id);
        $stmt->execute();

        return true;
    }

//     public function softDelete($item_id)
//     {
//         // First check if record exists
//         $order_item = $this->findById($item_id);
//         if (!$order_item) {
//             return false;
//         }
//         $now = date('Y-m-d H:i:s');
//         $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status, updated_at = :updated_at WHERE item_id = :item_id");
//         $status = 'deleted';
//         $stmt->bindParam(':status', $status);
//         $stmt->bindParam(':updated_at', $now);
//         $stmt->execute();

//         return true;
//     }
}
