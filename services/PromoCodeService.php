<?php

namespace Services;

require_once __DIR__ . '/../models/Promo_Codes.php';

use Utils\Paginator;
use PromoCode;

class PromoCodesService
{
    private $promo_codesModel;
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
        $this->promo_codesModel = new PromoCode($this->db);
    }

    public function getAllPromoCodes($page, $default)
    {
        $data = $this->promo_codesModel->findAll();
        $response = new Paginator($data, $default, $page);
        return $response;
    }

    public function getPromoCodeById($id)
    {
        return $this->promo_codesModel->findById($id);
    }

    public function createPromoCode($data)
    {
        return $this->promo_codesModel->create($data);
    }

    public function updatePromoCode($id, $data)
    {
        return $this->promo_codesModel->update($id, $data);
    }

    public function deletePromoCode($id)
    {
        return $this->promo_codesModel->delete($id);
    }
}
