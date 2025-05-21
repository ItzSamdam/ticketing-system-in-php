<?php

namespace Services;

require_once __DIR__ . '/../models/Orders.php';

use Utils\Paginator;
use Order;

class OrdersService
{
    private $ordersModel;
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
        $this->ordersModel = new Order($this->db);
    }

    public function getAllOrders($page, $default)
    {
        $data = $this->ordersModel->findAll();
        $response = new Paginator($data, $default, $page);
        return $response;
    }

    public function getOrderById($id)
    {
        return $this->ordersModel->findById($id);
    }

    public function createOrder($data)
    {
        return $this->ordersModel->create($data);
    }

    public function updateOrder($id, $data)
    {
        return $this->ordersModel->update($id, $data);
    }

    public function deleteOrder($id)
    {
        return $this->ordersModel->delete($id);
    }
}
