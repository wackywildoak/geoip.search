<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>


<div class="form-container">
    <h2>Обратная связь</h2>
    <form method="POST">
        <div class="input-group">
            <label for="name">Ваше имя</label>
            <input type="text" id="name" name="GEOIP" placeholder="Введите ваше имя">
        </div>
        <button type="submit" class="submit-btn">Отправить</button>
    </form>
</div>



    <pre><?print_r($arResult);?></pre>

