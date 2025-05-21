<?php

namespace Services;

require_once __DIR__ . '/../models/Ticket_Types.php';

use Utils\Paginator;
use TicketType;

class TicketTypesService
{
    private $ticket_typesModel;
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
        $this->ticket_typesModel = new TicketType($this->db);
    }

    public function getAllTicketTypes($page, $default)
    {
        $data = $this->ticket_typesModel->findAll();
        $response = new Paginator($data, $default, $page);
        return $response;
    }

    public function getTicketTypeById($id)
    {
        return $this->ticket_typesModel->findById($id);
    }

    public function createTicketType($data)
    {
        return $this->ticket_typesModel->create($data);
    }

    public function updateTicketType($id, $data)
    {
        return $this->ticket_typesModel->update($id, $data);
    }

    public function deleteTicketType($id)
    {
        return $this->ticket_typesModel->delete($id);
    }
}
