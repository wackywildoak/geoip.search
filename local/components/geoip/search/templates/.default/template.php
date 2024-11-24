<? 

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); 

/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global CModule */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>


<div class="form-container">
    <h2>GeoIp - Search</h2>
    <form method="POST" id="geoip-form">
        <div class="input-group">
            <label for="name">Enter IP</label>
            <input type="text" id="ip" name="GEOIP" placeholder="8.8.8.8">
        </div>
        <button type="submit" class="submit-btn">Submit</button>
    </form>

    <div id="geoip-result">

    </div>

    <div class="map-container" id="map" style="width: 100%; height: 400px;"></div>
</div>


<script>
    var componentUrl = '<?= CUtil::JSEscape($APPLICATION->GetCurPage()) ?>';
</script>