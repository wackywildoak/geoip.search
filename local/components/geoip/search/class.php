<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Application;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\Entity;
use \Bitrix\Main\Request;
Loader::includeModule("highloadblock"); 

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class GeoIpSearchComponent extends CBitrixComponent
{
    
    private $geoApiUrl = "https://api.sypexgeo.net/json/"; // API к котому производится запрос

    public function executeComponent()
    {   
        $this->checkModules();
        // если ajax, возвращаем данные в json
        if ($this->isAjaxRequest()) 
            $this->handleAjaxRequest();
        else
            $this->includeComponentTemplate();
    }

    // метод на проверку модулей
    protected function checkModules()
    {
        if (!Loader::includeModule('highloadblock')) 
        {
            throw new \Exception('Модуль highloadblock не установлен.');
        }
    }

    // обработка параметров
    public function onPrepareComponentParams($arParams)
    {
        $arParams['HL_BLOCK_NAME'] = isset($arParams['HL_BLOCK_NAME']) ? $arParams['HL_BLOCK_NAME'] : 'SearchGeoIP';
        
        return $arParams;
    }

    // метод проверки на ajax запрос
    protected function isAjaxRequest() 
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    // метод обработки ajax запроса
    private function handleAjaxRequest()
    {
        global $APPLICATION;

        $ip = $_POST['GEOIP'] ?? null;
        // валидация ip
        if (!filter_var($ip, FILTER_VALIDATE_IP)) 
            return;

        $data = $this->existInHighloadBlock($ip); // получаем данные из hl
        
        // если данных нет, делаем запрос к API и сохраняем их в HL
        if (!$data) 
        {
            $data = $this->fetchApiData($ip);
            $this->saveToHighloadBlock($data);
        }

        $APPLICATION->RestartBuffer();

        header('Content-Type: application/json');
        echo json_encode($data);
        die;
    }

    // метод запроса к API
    protected function fetchApiData($ip) 
    {
        $httpClient = new HttpClient();
        $url = $this->geoApiUrl.$ip;
        $response = $httpClient->get($url);
        $statusCode = $httpClient->getStatus();

        if ($statusCode != 200) 
            throw new \Exception("API request failed with status code: $statusCode for IP: $ip");

        $data = json_decode($response, true);

        if (empty($data))
            throw new \Exception("Invalid or empty response from API for IP: $ip");
        
        return $data;
    }

    // создание сущности HL
    protected function getHighloadBlockEntity()
    {
        $hlBlock = HLBT::getList(['filter' => ['=NAME' => $this->arParams['HL_BLOCK_NAME']]])->fetch();
        if (!$hlBlock) 
            return false;
        
        $entity = HLBT::compileEntity($hlBlock);
        return $entity->getDataClass();
    }

    // проверка и получение данных из HL
    protected function existInHighloadBlock($ip) 
    {
        if (!$hlBlock = $this->getHighloadBlockEntity())
            return;

        $result = $hlBlock::getList([
            'filter' => ['UF_IP' => $ip],
        ]);
        
        if ($data = $result->fetch()) 
        {
            return $data;
        }

        return false;
    }

    // сохранение данных в HL
    protected function saveToHighloadBlock($data) 
    {
        if (!$hlBlock = $this->getHighloadBlockEntity())
            return;

        $result = $hlBlock::add([
            'UF_IP' => $data['ip'],
            'UF_LATITUDE' => $data['city']['lat'],
            'UF_LONGITUDE' => $data['city']['lon'],
            'UF_CITY_NAME_RU' => $data['city']['name_ru'],
            'UF_CITY_NAME_EN' => $data['city']['name_en'],
            'UF_REGION_NAME_RU' => $data['region']['name_ru'],
            'UF_REGION_NAME_EN' => $data['region']['name_en'],
            'UF_COUNTRY_NAME_RU' => $data['country']['name_ru'],
            'UF_COUNTRY_NAME_EN' => $data['country']['name_en'],
        ]);

        if (!$result->isSuccess()) {
            throw new \Exception("Failed to save data to Highload block: " . implode(", ", $result->getErrorMessages()));
        }
    
        return true;
    }
}
