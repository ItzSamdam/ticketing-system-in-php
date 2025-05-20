<?php

require_once __DIR__ . '/../config/Database.php';

class User
{
    private $db;
    private $table = 'events';

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->prepare("SELECT event_id, organizer_id, title, description, image_url, location, event_type, virtual_url, start_datetime, end_datetime, timezone, status, created_at, updated_at FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT event_id, organizer_id, title, description, image_url, location, event_type, virtual_url, start_datetime, end_datetime, timezone, status, created_at, updated_at FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findByOrganizerId($organizer_id)
    {
        $stmt = $this->db->prepare("SELECT event_id, organizer_id, title, description, image_url, location, event_type, virtual_url, start_datetime, end_datetime, timezone, status, created_at, updated_at FROM {$this->table} WHERE organizer_id = :organizer_id");
        $stmt->bindParam(':organizer_id', $organizer_id);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByStatus($status)
    {
        $stmt = $this->db->prepare("SELECT event_id, organizer_id, title, description, image_url, location, event_type, virtual_url, start_datetime, end_datetime, timezone, status, created_at, updated_at FROM {$this->table} WHERE status = :status");
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (organizer_id, title, description, image_url, location, event_type, virtual_url, start_datetime, end_datetime, timezone, status, created_at, updated_at)
            VALUES (:organizer_id, :title, :description, :image_url, :location, :event_type, :virtual_url, :start_datetime, :end_datetime, :timezone, :status, :created_at, :updated_at)
        ");

        $stmt->bindParam(':organizer_id', $data['organizer_id']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':event_type', $data['event_type']);
        $stmt->bindParam(':virtual_url', $data['virtual_url']);
        $stmt->bindParam(':start_datetime', $data['start_datetime']);
        $stmt->bindParam(':end_datetime', $data['end_datetime']);
        $stmt->bindParam(':timezone', $data['timezone']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':updated_at', $now);

        $stmt->execute();

        $id = $this->db->lastInsertId();

        return $this->findById($id);
    }

    public function update($id, $data)
    {
        // First check if record exists
        $user = $this->findById($id);

        if (!$user) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Build update query dynamically based on provided data
        $fields = [];
        $params = [':id' => $id, ':updated_at' => $now];

        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'description', 'image_url', 'location', 'event_type', 'virtual_url', 'start_datetime', 'end_datetime', 'timezone', 'status'])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return $user; // Nothing to update
        }

        $fields[] = "updated_at = :updated_at";
        $fieldsStr = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$fieldsStr} WHERE id = :id");
        $stmt->execute($params);

        return $this->findById($id);
    }

    public function delete($id)
    {
        // First check if record exists
        $user = $this->findById($id);

        if (!$user) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return true;
    }

    public function softDelete($id)
    {
        // First check if record exists
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status, updated_at = :updated_at WHERE event_id = :id");
        $status = 'deleted';
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':updated_at', $now);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return true;
    }
}
