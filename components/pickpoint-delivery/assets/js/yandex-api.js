(function (w) {
    function start() {
        w.removeEventListener('YaDeliveryLoad', start);
        const form = document.getElementById("checkout");
        w.YaDelivery.createWidget({
            containerId: 'yaDeliveryWidget',
            type: 'deliveryCart',
            params: {
                apiKey: wdyd_yandex.api,
                senderId: wdyd_yandex.sender
            }
        }).then(successCallback).catch(failureCallback);

        function successCallback(wgt) {
            window.widget = wgt;

            // Когда пользователь отправит форму выбора условий доставки, нужно сохранить
            // их в куки с помощью метода setOrderInfo, чтобы после оформления заказа вы могли
            // отправить их в Доставку. В аргументе метода нужно передать объект с информацией
            // о заказе. Подробнее об объекте order.
            //form.addEventListener('submit', () => widget.setOrderInfo(order));
        }

        function failureCallback(error) {
            console.log('Error');
            console.log(error);
        }
    }

    w.YaDelivery
        ? start()
        : w.addEventListener('YaDeliveryLoad', start);
})(window);