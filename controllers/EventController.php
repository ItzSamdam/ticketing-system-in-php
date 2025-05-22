<?php

namespace Controllers;

use Utils\Request;
use Utils\Response;
use Utils\Validator;
use Services\EventsService;


class EventController
{
    private $eventsService;

    public function __construct()
    {
        $this->eventsService = new EventsService();
    }

    public function index(Request $request)
    {
        $currentPage = intval($request->getQueryParam('page') ?? 1); // Get 'page' from request or default to page 1
        $itemPerPage = intval($request->getQueryParam('limit') ?? 10); // Get 'itemsPerPage' from request or default to 1
        $users = $this->eventsService->getAllEvents($currentPage, $itemPerPage);
        return Response::success([
            'current_page' => $users->getCurrentPage(),
            'total_pages' => $users->getTotalPages(),
            'items' => $users->getPageData()
        ]);
    }

    public function show(Request $request, $params)
    {
        $events = $this->eventsService->getEventById($params['id']);

        if (!$events) {
            return Response::notFound('Event not found');
        }

        return Response::success($events);
    }

    public function create()
    {
        include __DIR__ . '/../views/events/create.php';
    }

    public function store()
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'date' => $_POST['date'] ?? '',
            'location' => $_POST['location'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];
        $this->eventsService->createEvent($data);
        header('Location: /events');
        exit;
    }

    public function edit($id)
    {
        $event = $this->eventsService->getEventById($id);
        include __DIR__ . '/../views/events/edit.php';
    }

    public function update($id)
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'date' => $_POST['date'] ?? '',
            'location' => $_POST['location'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];
        $this->eventsService->updateEvent($id, $data);
        header('Location: /events');
        exit;
    }

    public function delete($id)
    {
        $this->eventsService->deleteEvent($id);
        header('Location: /events');
        exit;
    }
}