<?php

require_once __DIR__ . '/../config/Database.php';

class Payment
{
    private $db;
    private $table = 'payments';

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->prepare("SELECT payment_id, order_id, amount, payment_method, status, transaction_reference, created_at, updated_at FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById($payment_id)
    {
        $stmt = $this->db->prepare("SELECT payment_id, order_id, amount, payment_method, status, transaction_reference, created_at, updated_at FROM {$this->table} WHERE payment_id = :payment_id");
        $stmt->bindParam(':payment_id', $payment_id);
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

    public function findByTransactionReference($transaction_reference)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE transaction_reference = :transaction_reference");
        $stmt->bindParam(':transaction_reference', $transaction_reference);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (order_id, amount, payment_method, status, transaction_reference, created_at, updated_at)
            VALUES (:order_id, :amount, :payment_method, :status, :transaction_reference, :created_at, :updated_at)
        ");

        $stmt->bindParam(':order_id', $data['order_id']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':transaction_reference', $data['transaction_reference']);
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':updated_at', $now);

        $stmt->execute();

        $payment = $this->db->lastInsertId();

        return $this->findById($payment);
    }

    public function update($payment_id, $data)
    {
        // First check if record exists
        $payment = $this->findById($payment_id);

        if (!$payment) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        // Build update query dynamically based on provided data
        $fields = [];
        $params = [':payment_id' => $payment_id, ':updated_at' => $now];

        foreach ($data as $key => $value) {
            if (in_array($key, ['order_id', 'amount', 'payment_method', 'status', 'transaction_reference'])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return $payment; // Nothing to update
        }

        $fields[] = "updated_at = :updated_at";
        $fieldsStr = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$fieldsStr} WHERE payment_id = :payment_id");
        $stmt->execute($params);

        return $this->findById($payment_id);
    }

    public function delete($payment_id)
    {
        // First check if record exists
        $payment = $this->findById($payment_id);

        if (!$payment) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE payment_id = :payment_id");
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->execute();

        return true;
    }
}
