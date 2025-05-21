<?php

namespace Services;

require_once __DIR__ . '/../models/Payments.php';

use Utils\Paginator;
use Payment;

class PaymentsService
{
    private $paymentsModel;
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
        $this->paymentsModel = new Payment($this->db);
    }

    public function getAllPayments($page, $default)
    {
        $data = $this->paymentsModel->findAll();
        $response = new Paginator($data, $default, $page);
        return $response;
    }

    public function getPaymentById($id)
    {
        return $this->paymentsModel->findById($id);
    }

    public function createPayment($data)
    {
        // Generate a unique transaction reference
        $data['transaction_reference'] = $this->generateTransactionReference();
        return $this->paymentsModel->create($data);
    }

    public function updatePayment($id, $data)
    {
        return $this->paymentsModel->update($id, $data);
    }

    public function deletePayment($id)
    {
        return $this->paymentsModel->delete($id);
    }

    public function generateTransactionReference()
    {
        return bin2hex(random_bytes(16));
    }
}
