<?php

namespace Sprint\Migration;


class CreateGeoIpHighloadBlock20241124070053 extends Version
{
    protected $author = "admin";

    protected $description = "";

    protected $moduleVersion = "4.15.1";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
    $hlblockId = $helper->Hlblock()->saveHlblock(array (
  'NAME' => 'GeoIpSearch',
  'TABLE_NAME' => 'geo_ip_search',
  'LANG' => 
  array (
    'ru' => 
    array (
      'NAME' => 'Поиск IP',
    ),
    'en' => 
    array (
      'NAME' => 'Search IP',
    ),
  ),
));
    }
}
