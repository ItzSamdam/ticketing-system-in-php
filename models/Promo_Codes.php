<?php

require_once __DIR__ . '/../config/Database.php';

class PromoCode
{
    private $db;
    private $table = 'promo_codes';

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->prepare("SELECT promo_code_id, code, discount_percentage, valid_until, max_uses, times_used, created_at, updated_at FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById($promo_code_id)
    {
        $stmt = $this->db->prepare("SELECT promo_code_id, code, discount_percentage, valid_until, max_uses, times_used, created_at, updated_at FROM {$this->table} WHERE promo_code_id = :promo_code_id");
        $stmt->bindParam(':promo_code_id', $promo_code_id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (code, discount_percentage, valid_until, max_uses, times_used, created_at, updated_at)
            VALUES (:code, :discount_percentage, :valid_until, :max_uses, :times_used, :created_at, :updated_at)
        ");

        $stmt->bindParam(':code', $data['code']);
        $stmt->bindParam(':discount_percentage', $data['discount_percentage']);
        $stmt->bindParam(':valid_until', $data['valid_until']);
        $stmt->bindParam(':max_uses', $data['max_uses']);
        $stmt->bindParam(':times_used', $data['times_used']);        
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':updated_at', $now);

        $stmt->execute();

        $promo_code = $this->db->lastInsertId();

        return $this->findById($promo_code);
    }

    public function update($promo_code_id, $data)
    {
        // First check if record exists
        $promo_code = $this->findById($promo_code_id);

        if (!$promo_code) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Build update query dynamically based on provided data
        $fields = [];
        $params = [':promo_code_id' => $promo_code_id, ':updated_at' => $now];

        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'email', 'password'])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return $promo_code; // Nothing to update
        }

        $fields[] = "updated_at = :updated_at";
        $fieldsStr = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$fieldsStr} WHERE promo_code_id = :promo_code_id");
        $stmt->execute($params);

        return $this->findById($promo_code_id);
    }

    public function delete($promo_code_id)
    {
        // First check if record exists
        $promo_code = $this->findById($promo_code_id);

        if (!$promo_code) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE promo_code_id = :promo_code_id");
        $stmt->bindParam(':promo_code_id', $promo_code_id);
        $stmt->execute();

        return true;
    }
}
