<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Application;
use Bitrix\Main\Web\HttpClient;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class GeoIpSearchComponent extends CBitrixComponent
{

    private $geoApiUrl = "https://api.sypexgeo.net/json/";

    public function executeComponent()
    {   
        if ($_POST['GEOIP'])
            $data = $this->fetchApiData($_POST['GEOIP']);

        $this->arResult = $data;

        $this->includeComponentTemplate();
    }

    protected function fetchApiData($ip) {
        $httpClient = new HttpClient();

        $url = $this->geoApiUrl.$ip;

        $response = $httpClient->get($url);

        $data = json_decode($response, true);

        return $data;
    }

}
