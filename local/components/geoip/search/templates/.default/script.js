$(document).ready(function() {
    let map; // Переменная для карты
    let placemark; // Переменная для метки

    $('#geoip-form').on('submit', function(event) {
        event.preventDefault();
        
        var ip = $('#ip').val();
        
        $.ajax({
            url: componentUrl,
            method: 'POST',
            data: { GEOIP: ip },
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    $('#geoip-result').html('Ошибка: ' + data.error);
                } else {
                    var resultHtml = `
                        <h5>Информация о местоположении</h5>
                        <ul>
                            <li><strong>IP:</strong> ${ip}</li>
                            <li><strong>Страна:</strong> ${data.country.name_ru} (${data.country.name_en})</li>
                            <li><strong>Регион:</strong> ${data.region.name_ru || 'Не указан'} (${data.region.name_en || 'Не указан'})</li>
                            <li><strong>Город:</strong> ${data.city.name_ru || 'Не указан'} (${data.city.name_en || 'Не указан'})</li>
                            <li><strong>Широта:</strong> ${data.city.lat}</li>
                            <li><strong>Долгота:</strong> ${data.city.lon}</li>
                        </ul>
                    `;
                    $('#geoip-result').html(resultHtml);
                    console.log(data);
                }

                var lat = data.city.lat;
                var lon = data.city.lon;

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