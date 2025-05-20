<?php

require_once __DIR__ . '/../config/Database.php';

class Ticket
{
    private $db;
    private $table = 'tickets';

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->prepare("SELECT ticket_id, order_item_id, ticket_code, attendee_name, attendee_email, is_checked_in, checked_in_at, created_at, updated_at FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById($ticket_id)
    {
        $stmt = $this->db->prepare("SELECT ticket_id, order_item_id, ticket_code, attendee_name, attendee_email, is_checked_in, checked_in_at, created_at, updated_at FROM {$this->table} WHERE ticket_id = :ticket_id");
        $stmt->bindParam(':ticket_id', $ticket_id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (order_item_id, ticket_code, attendee_name, attendee_email, is_checked_in, checked_in_at, created_at, updated_at)
            VALUES (:order_item_id, :ticket_code, :attendee_name, :attendee_email, :is_checked_in, :checked_in_at, :created_at, :updated_at)
        ");
        $stmt->bindParam(':order_item_id', $data['order_item_id']);
        $stmt->bindParam(':ticket_code', $data['ticket_code']);
        $stmt->bindParam(':attendee_name', $data['attendee_name']);
        $stmt->bindParam(':attendee_email', $data['attendee_email']);
        $stmt->bindParam(':is_checked_in', $data['is_checked_in']);
        $stmt->bindParam(':checked_in_at', $data['checked_in_at']); // Default token version
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':updated_at', $now);

        $stmt->execute();

        $ticket = $this->db->lastInsertId();

        return $this->findById($ticket);
    }

    public function update($ticket_id, $data)
    {
        // First check if record exists
        $ticket = $this->findById($ticket_id);

        if (!$ticket) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Build update query dynamically based on provided data
        $fields = [];
        $params = [':ticket_id' => $ticket_id, ':updated_at' => $now];

        foreach ($data as $key => $value) {
            if (in_array($key, ['order_item_id', 'ticket_code', 'attendee_name', 'attendee_email', 'is_checked_in', 'checked_in_at',])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return $ticket; // Nothing to update
        }

        $fields[] = "updated_at = :updated_at";
        $fieldsStr = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$fieldsStr} WHERE ticket_id = :ticket_id");
        $stmt->execute($params);

        return $this->findById($ticket_id);
    }

    public function delete($ticket_id)
    {
        // First check if record exists
        $ticket = $this->findById($ticket_id);

        if (!$ticket) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE ticket_id = :ticket_id");
        $stmt->bindParam(':ticket_id', $ticket_id);
        $stmt->execute();

        return true;
    }
}
