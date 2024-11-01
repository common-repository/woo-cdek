var onmessage = function(e) {
    widgetHandler(e);
}

/**
 *
 * @param result = {
 *     address: string, // "414001, Астрахань г., Ленина пл., д.55(Boxberry)"
 *     cityname: string, // "Астрахань"
 *     cod: string, // "98765"
 *     phone: string, // "+7(812)930-09-15"
 *     prepaid: string, // "0"
 *     price: number, // 250
 *     pvz_name: string, //
 *     service: string, // "1"
 *     srok: number, // 3
 *     work_time: string, // "пн-пт:10.00-19.00, сб:10.00-14.00"
 * }
 */
function widgetHandler(result) {
    console.log(result);

    zt.hideOverlay();
    zt.hideContainer();

    document.querySelector('[data-type="zt-data"]').value = JSON.stringify(result.data);
    document.querySelector('[data-type="price"]').value = result.data.price;
    jQuery(document.body).trigger('update_checkout');
}

function openWidget(event) {
    event.preventDefault();

    var info = JSON.parse(document.querySelector('[data-type="info"]').value);

    zt.open(widgetHandler, info.key, info.total, info.insurance, Math.ceil(info.w));
}

var btn = document.getElementById('wdzt_select_pickup_point');

if(btn !== null) {
    btn.onclick = openWidget
}