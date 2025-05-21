<?php

namespace Services;

require_once __DIR__ . '/../models/Tickets.php';

use Utils\Paginator;
use Ticket;

class TicketsService
{
    private $ticketsModel;
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
        $this->ticketsModel = new Ticket($this->db);
    }

    public function getAllTickets($page, $default)
    {
        $data = $this->ticketsModel->findAll();
        $response = new Paginator($data, $default, $page);
        return $response;
    }

    public function getTicketById($id)
    {
        return $this->ticketsModel->findById($id);
    }

    public function createTicket($data)
    {
        return $this->ticketsModel->create($data);
    }

    public function updateTicket($id, $data)
    {
        return $this->ticketsModel->update($id, $data);
    }

    public function deleteTicket($id)
    {
        return $this->ticketsModel->delete($id);
    }
}
