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
    
    private $geoApiUrl = "https://api.sypexgeo.net/json/";

    public function executeComponent()
    {   
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
        && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
        {
            global $APPLICATION;
            $APPLICATION->RestartBuffer();

            $data = $this->handleAjaxRequest();

            header('Content-Type: application/json');
            echo json_encode($data);
            die;
        }
        else
        {
            $this->includeComponentTemplate();
        }
    }

    protected function checkModules()
    {
        if (!Loader::includeModule('highloadblock')) 
        {
            throw new \Exception('Модуль highloadblock не установлен.');
        }
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['HL_BLOCK_NAME'] = isset($arParams['HL_BLOCK_NAME']) ? $arParams['HL_BLOCK_NAME'] : 'SearchGeoIP';
        
        return $arParams;
    }

    private function handleAjaxRequest()
    {
        $ip = $_POST['GEOIP'] ?? null;

        // if (!filter_var($ip, FILTER_VALIDATE_IP)) 
        //     return;

        $geoData = $this->existInHighloadBlock($ip);
        if ($geoData)
        {
            AddMessage2Log('вернули из HL');
            return $geoData;
        }
        else
        {   
            AddMessage2Log('вернули из API');

            $geoData = $this->fetchApiData($ip);
            return $geoData;
        }
    }

    protected function fetchApiData($ip) 
    {
        $httpClient = new HttpClient();
        $url = $this->geoApiUrl.$ip;
        $response = $httpClient->get($url);

        return json_decode($response, true);
    }

    protected function existInHighloadBlock($ip) 
    {
        $hlblock = HLBT::getList([
            'filter' => ['NAME' => $this->arParams['HL_BLOCK_NAME']],
        ])->fetch();

        if (!$hlblock) {
            return false; 
        }

        $entity = HLBT::compileEntity($hlblock);
        $entityDataClass = $entity->getDataClass();

        $result = $entityDataClass::getList([
            'filter' => ['UF_IP' => $ip],
        ]);
        
        if ($data = $result->fetch()) {
            AddMessage2Log($data);
            return $data;
        } else {
            return false;
        }
    }

    protected function saveToHighloadBlock($data) 
    {

    }



}
