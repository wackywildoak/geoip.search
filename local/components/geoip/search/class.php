<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Application;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\Entity;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
Loader::includeModule("highloadblock"); 

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class GeoIpSearchComponent extends CBitrixComponent implements Controllerable, Errorable
{
    protected ErrorCollection $errorCollection;
    private $geoApiUrl = "https://api.sypexgeo.net/json/"; // API к котому производится запрос

    public function executeComponent(): void
    {   
        $this->checkModules();
        $this->includeComponentTemplate();
    }
    
    // обработка параметров
    public function onPrepareComponentParams($arParams): array
    {   
        $this->errorCollection = new ErrorCollection();

        $arParams['HL_BLOCK_NAME'] = isset($arParams['HL_BLOCK_NAME']) ? $arParams['HL_BLOCK_NAME'] : 'SearchGeoIP';
        
        return $arParams;
    }

    public function getErrors(): array
    {
        return $this->errorCollection->toArray();
    }

    public function getErrorByCode($code): Error
    {
        return $this->errorCollection->getErrorByCode($code);
    }

    public function configureActions(): array
    {
        return [
            'send' => [
                'prefilters' => [
                ]
            ]
        ];
    }

    // метод на проверку модулей
    protected function checkModules(): void
    {
        if (!Loader::includeModule('highloadblock')) 
        {
            throw new \Exception('Модуль highloadblock не установлен.');
        }
    }

    public function sendAction(string $ip = ''): array|bool|string
    {   
        if (!filter_var($ip, FILTER_VALIDATE_IP)) 
            return false;

        try {
            $data = $this->existInHighloadBlock($ip);

            if (!$data) 
            {
                $data = $this->fetchApiData($ip);
                $this->saveToHighloadBlock($data);
            }

            AddMessage2Log($data);
            return json_encode($data);

        } catch (Exceptions\EmptyEmail $e) {
            $this->errorCollection[] = new Error($e->getMessage());
            return [
                "result" => "Произошла ошибка",
            ];
        }
    }

    // метод запроса к API
    protected function fetchApiData(string $ip = ''): mixed
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
    protected function getHighloadBlockEntity(): bool|DataManager|string
    {
        $hlBlock = HLBT::getList(['filter' => ['=NAME' => $this->arParams['HL_BLOCK_NAME']]])->fetch();
        if (!$hlBlock) 
            return false;
        
        $entity = HLBT::compileEntity($hlBlock);
        return $entity->getDataClass();
    }

    // проверка и получение данных из HL
    protected function existInHighloadBlock(string $ip = ''): mixed
    {
        if (!$hlBlock = $this->getHighloadBlockEntity())
            return false;

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
    protected function saveToHighloadBlock(array $data): bool
    {
        if (!$hlBlock = $this->getHighloadBlockEntity())
            return false;

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
