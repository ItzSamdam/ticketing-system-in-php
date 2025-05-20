<?php

require_once __DIR__ . '/../config/Database.php';

class TicketType
{
    private $db;
    private $table = 'ticket_types';

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->prepare("SELECT ticket_type_id, event_id, name, description, price, quantity_available, quantity_sold, sales_start_datetime, sales_end_datetime, created_at, updated_at FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById($ticket_type_id)
    {
        $stmt = $this->db->prepare("SELECT ticket_type_id, event_id, name, description, price, quantity_available, quantity_sold, sales_start_datetime, sales_end_datetime, created_at, updated_at FROM {$this->table} WHERE ticket_type_id = :ticket_type_id");
        $stmt->bindParam(':ticket_type_id', $ticket_type_id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findByEventId($event_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE event_id = :event_id");
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (event_id, name, description, price, quantity_available, quantity_sold, sales_start_datetime, sales_end_datetime, created_at, updated_at)
            VALUES (:event_id, :name, :description, :price, :quantity_available, :quantity_sold, :sales_start_datetime, :sales_end_datetime, :created_at, :updated_at)
        ");

        $stmt->bindParam(':event_id', $data['event_id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':quantity_available', $data['quantity_available']);
        $stmt->bindParam(':quantity_sold', $data['quantity_sold']);
        $stmt->bindParam(':sales_start_datetime', $data['sales_start_datetime']);
        $stmt->bindParam(':sales_end_datetime', $data['sales_end_datetime']);
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':updated_at', $now);

        $stmt->execute();

        $ticket_type = $this->db->lastInsertId();

        return $this->findById($ticket_type);
    }

    public function update($ticket_type_id, $data)
    {
        // First check if record exists
        $ticket_type = $this->findById($ticket_type_id);

        if (!$ticket_type) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Build update query dynamically based on provided data
        $fields = [];
        $params = [':ticket_type_id' => $ticket_type_id, ':updated_at' => $now];

        foreach ($data as $key => $value) {
            if (in_array($key, ['event_id', 'name', 'description', 'price', 'quantity_available', 'quantity_sold', 'sales_start_datetime', 'sales_end_datetime'])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return $ticket_type; // Nothing to update
        }

        $fields[] = "updated_at = :updated_at";
        $fieldsStr = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$fieldsStr} WHERE ticket_type_id = :ticket_type_id");
        $stmt->execute($params);

        return $this->findById($ticket_type_id);
    }

    public function delete($ticket_type_id)
    {
        // First check if record exists
        $user = $this->findById($ticket_type_id);

        if (!$user) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE ticket_type_id = :ticket_type_id");
        $stmt->bindParam(':ticket_type_id', $ticket_type_id);
        $stmt->execute();

        return true;
    }
}
