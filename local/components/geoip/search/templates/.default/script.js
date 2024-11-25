$(document).ready(function() {
    let map; // Переменная для карты
    let placemark; // Переменная для метки

    function transformData(data) {
        if (data.city && data.region && data.country) {
            return {
                ip: data.ip,
                city: {
                    name_ru: data.city.name_ru || 'Не указан',
                    name_en: data.city.name_en || 'Не указан',
                    lat: data.city.lat,
                    lon: data.city.lon
                },
                region: {
                    name_ru: data.region.name_ru || 'Не указан',
                    name_en: data.region.name_en || 'Не указан'
                },
                country: {
                    name_ru: data.country.name_ru || 'Не указан',
                    name_en: data.country.name_en || 'Не указан'
                },
                data_from: 'API'
            };
        }
    
        if (data.UF_IP) {
            return {
                ip: data.UF_IP,
                city: {
                    name_ru: data.UF_CITY_NAME_RU || 'Не указан',
                    name_en: data.UF_CITY_NAME_EN || 'Не указан',
                    lat: data.UF_LATITUDE,
                    lon: data.UF_LONGITUDE
                },
                region: {
                    name_ru: data.UF_REGION_NAME_RU || 'Не указан',
                    name_en: data.UF_REGION_NAME_EN || 'Не указан'
                },
                country: {
                    name_ru: data.UF_COUNTRY_NAME_RU || 'Не указан',
                    name_en: data.UF_COUNTRY_NAME_EN || 'Не указан'
                },
                data_from: 'Highload-Блок'
            };
        }
    
        // Если данные не подходят под ни один из форматов
        return null;
    }


    $('#geoip-form').on('submit', function(event) {
        event.preventDefault();
        
        var ip = $('#ip').val();
        
        $.ajax({
            url: '/bitrix/services/main/ajax.php?mode=class&c=geoip:search&action=send',
            method: 'POST',
            data: { ip: ip },
            dataType: 'json',
            success: function(data) {
                if (!data.data) {
                    $('#geoip-result').html('Данные отсутствуют');
                } else {
                    var transformedData = transformData(JSON.parse(data.data));
                    console.log(data);
                    console.log(transformedData);
                    if (transformedData) {
                        var resultHtml = `
                            <h5>Информация о местоположении</h5>
                            <ul>
                                <li><strong>IP:</strong> ${ip}</li>
                                <li><strong>Страна:</strong> ${transformedData.country.name_ru} (${transformedData.country.name_en})</li>
                                <li><strong>Регион:</strong> ${transformedData.region.name_ru || 'Не указан'} (${transformedData.region.name_en || 'Не указан'})</li>
                                <li><strong>Город:</strong> ${transformedData.city.name_ru || 'Не указан'} (${transformedData.city.name_en || 'Не указан'})</li>
                                <li><strong>Широта:</strong> ${transformedData.city.lat}</li>
                                <li><strong>Долгота:</strong> ${transformedData.city.lon}</li>
                                <li><strong>Данные из:</strong> ${transformedData.data_from}</li>
                            </ul>
                        `;

                        $('#geoip-result').html(resultHtml);
                    }
                }

                var lat = transformedData.city.lat;
                var lon = transformedData.city.lon

                if (!map) {
                    ymaps.ready(function () {
                        map = new ymaps.Map("map", {
                            center: [lat, lon],
                            zoom: 10
                        });

                        placemark = new ymaps.Placemark([lat, lon], {
                            balloonContent: 'Местоположение: ' + data.country.name_en
                        });

                        map.geoObjects.add(placemark);
                    });
                } else {
                    map.setCenter([lat, lon], 10);
                    if (placemark) {
                        map.geoObjects.remove(placemark);
                    }
                    placemark = new ymaps.Placemark([lat, lon], {
                        balloonContent: 'Местоположение: ' + data.country.name_en
                    });
                    map.geoObjects.add(placemark);
                }
            },
            error: function(xhr, status, error) {
                $('#geoip-result').html('Ошибка запроса');
            }
        });
    });
});