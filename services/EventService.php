<?php

namespace Services;

require_once __DIR__ . '/../models/Events.php';

use Utils\Paginator;
use Utils\CloudinaryUploader;
use Events;

class EventsService
{
    private $eventsModel;
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
        $this->eventsModel = new Events($this->db);
    }

    public function getAllEvents($page, $default)
    {
        $data = $this->eventsModel->findAll();
        $response = new Paginator($data, $default, $page);
        return $response;
    }

    public function getEventById($id)
    {
        return $this->eventsModel->findById($id);
    }

    public function createEvent($data)
    {
        if (!empty($_FILES['event_image']['name'])) {
            $fileTmpPath = $_FILES["event_image"]["tmp_name"];
            $fileType = strtolower(pathinfo($_FILES["event_image"]["name"], PATHINFO_EXTENSION));

            if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                try {
                    $uploadedImage = new CloudinaryUploader();
                    $file = $uploadedImage->upload($fileTmpPath, "event_images", 'image');
                    if($file != null){
                        return [false, "Image Upload Failed"];
                    }
                    $data['image_url'] = $uploadedImage['secure_url'];
                } catch (\Exception $e) {
                    return [false, "Cloudinary upload failed: " . $e->getMessage()];
                }
            } else {
                return [false, "Invalid file type!"];
            }
        } else {
            $data['image_url'] = null;
        }
        return $this->eventsModel->create($data);
    }

    public function updateEvent($id, $data)
    {
        // Retrieve the current event data to retain the existing image if no new image is uploaded
        $event = $this->eventsModel->findById($id);

        if (!empty($_FILES['event_image']['name'])) {
            $fileTmpPath = $_FILES["event_image"]["tmp_name"];
            $fileType = strtolower(pathinfo($_FILES["event_image"]["name"], PATHINFO_EXTENSION));

            if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                try {
                    $uploadedImage = new CloudinaryUploader();
                    $file = $uploadedImage->upload($fileTmpPath, "event_images", 'image');
                    if ($file == null) {
                        return [false, "Image Upload Failed"];
                    }
                    $data['image_url'] = $uploadedImage['secure_url'];
                } catch (\Exception $e) {
                    return [false, "Cloudinary upload failed: " . $e->getMessage()];
                }
            } else {
                return [false, "Invalid file type!"];
            }
        } else {
            // Retain the current image if no new image is uploaded
            $data['image_url'] = $event['image_url'] ?? null;
        }

        return $this->eventsModel->update($id, $data);
    }

    public function deleteEvent($id)
    {
        return $this->eventsModel->delete($id);
    }
}
