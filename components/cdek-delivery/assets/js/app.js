/**
 * @var {Object} wWoocommerceCdekDeliveryIntegration
 */

var strings = wWoocommerceCdekDeliveryIntegration.strings;

/**
 *
 * @returns {boolean}
 */
function isDebug() {
    return wWoocommerceCdekDeliveryIntegration.variables.debug;
}

/**
 *
 * @param method
 * @param locality
 * @param geo
 * @param callback
 */
function deliverySearch(method, locality, geo, callback) {
    addLoader(jQuery(".shop_table"));
    addLoader(jQuery("#payment"));

    if (window.cdekSearchAjax && window.cdekSearchAjax.readyState < 4) {
        window.cdekSearchAjax.abort();
    }

    window.cdekSearchAjax = jQuery.ajax({
        url: wWoocommerceCdekDeliveryIntegration.variables.ajaxurl,
        method: 'POST',
        data: {
            action: "wdcd_delivery_search",
            method: method,
            locality: locality,
            geo: geo
        },
        success: function (response) {
            removeLoader(jQuery(".shop_table"));
            removeLoader(jQuery("#payment"));
            if (typeof response.data !== 'undefined') {
                callback(response.data);
            }
        }
    });
}

/**
 *
 * @param {Number|String} id
 * @returns {string}
 */
function getIcon(id) {
    var DELIVERY_CDEK = 51;
    var DELIVERY_POST = 1003390;
    var DELIVERY_POST_1 = 1003375;
    var DELIVERY_BOXBERRY = 106;
    var DELIVERY_PICKPOINT = 107;
    var DELIVERY_PEK = 9;
    var DELIVERY_MAXIPOST = 50;
    var DELYVERY_STRIZH = 48;

    console.log(id, parseInt(id));

    switch (parseInt(id)) {
        case DELIVERY_CDEK:
            return "service-image-cdek";

        case DELIVERY_POST:
            return "service-image-post";

        case DELIVERY_POST_1:
            return "service-image-post";

        case DELIVERY_BOXBERRY:
            return "service-image-boxberry";

        case DELIVERY_PICKPOINT:
            return "service-image-pickpoint";

        case DELIVERY_PEK:
            return "service-image-pek";

        case DELIVERY_MAXIPOST:
            return "service-image-maxipost";

        case DELYVERY_STRIZH:
            return "service-image-strizh";

        default:
            if (isDebug()) {
                console.error("Icon ".concat(id, " not found"));
            }

            return "icon-standard";
    }
}

function deliveryConvertTime(time) {
    time = new Date(Date.parse("11/25/2016 ".concat(time))).toLocaleString("ru-RU", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false
    });
    return time;
}

function getDeliveryDate(array) {
    var deliveryDate = "";
    deliveryDate += convertDate(array[0]);

    if (array[0] !== array[1]) {
        deliveryDate += " - ".concat(convertDate(array[1]));
    }

    return deliveryDate;
}

function convertDate(date) {
    return new Date(date).toLocaleString("ru-RU", {
        month: "long",
        day: "numeric"
    });
}

function getItemData(item, pickupId) {
    if (isDebug()) {
        console.log(item);
    }

    var method = jQuery('[data-type="delivery-method"]').val();
    if (isDebug()) {
        console.log('ItemData:', item);
    }
    var itemData = {
        tariffId: item.tariffId,
        deliveryId: item.delivery.partner.id,
        method: method,
        methodName: "",
        icon: item.delivery.partner.id ? getIcon(item.delivery.partner.id) : null,
        name: item.delivery.partner.name ? item.delivery.partner.name : "",
        deliveryDate: item.delivery.calculatedDeliveryDateMin && item.delivery.calculatedDeliveryDateMax ? getDeliveryDate([item.delivery.calculatedDeliveryDateMin, item.delivery.calculatedDeliveryDateMax]) : null,
        deliveryIntervals: item.delivery.courierSchedule && item.delivery.courierSchedule.schedule ? item.delivery.courierSchedule.schedule : null,
        cost: item.cost.deliveryForCustomer ? item.cost.deliveryForCustomer : 0,
        currency: strings.currency_short,
        address: "",
        pickupId: "",
        schedule: "",
        instruction: ""
    };

    switch (method) {
        case "todoor":
            itemData.methodName = strings.courier;
            break;

        case "pickup":
            itemData.methodName = strings.pickup;
            itemData.pickupId = pickupId || "";

            // if (address && address.addressString) {
            //     itemData.address = address.addressString;
            // }
            //
            // if (instruction) {
            //     itemData.instruction = instruction;
            // }
            //
            // if (schedule) {
            //     itemData.schedule = schedule;
            // }

            break;

        default:
            if (isDebug()) {
                console.error("Method ".concat(method, " not found"));
            }

            break;
    }

    return itemData;
}

function buildDeliveryIntervals(intervals) {
    var deliveryIntervals = "";
    if (!jQuery.isEmptyObject(intervals)) {
        deliveryIntervals += "<ul".concat(intervals.length > 6 ? ' class="columns-2"' : '', ">");
        jQuery.each(intervals, function (i, interval) {
            deliveryIntervals += "<li>" + deliveryConvertTime(interval.timeFrom) + " - " + deliveryConvertTime(interval.timeTo) + "</li>";
        });
        deliveryIntervals += "</ul>";

    }
    return deliveryIntervals;
}

function deliveryBuildHtml(data, pickupId) {
    if (isDebug()) {
        console.log('Deliveries:', data);
        console.log('Pickup point ID:', pickupId);
    }

    var html = "";
    jQuery.each(data, function (i, item) {
        var itemData = getItemData(item, pickupId);

        var deliveryIntervals = buildDeliveryIntervals(itemData.deliveryIntervals);

        html += "<div class=\"delivery-item\" data-item-id=\"" + itemData.tariffId + "\" data-item='" + JSON.stringify(itemData) + "'>" +
            "      <div class=\"delivery-item-col delivery-item-col-1\">" +
            "          <div class=\"delivery-item-image\">" +
            "              <div class=\"service-image " + itemData.icon + "\"></div>" +
            "          </div>" +
            "          <div class=\"delivery-item-title\">" + itemData.name + "</div>" +
            "      </div>" +
            "      <div class=\"delivery-item-col delivery-item-col-2\">" +
            "          <div class=\"delivery-item-date\">" +
            "        <div class=\"delivery-item-date-description\">" + strings.est_delivery_date + "</div>" +
            "          <div class=\"delivery-item-date-value\">" + itemData.deliveryDate + "</div>" +
            "          <div class=\"delivery-item-date-time\">" + deliveryIntervals + "</div>" +
            "        </div>" +
            "          <div class=\"delivery-item-price\">" +
            "              " + itemData.cost + " " + itemData.currency +
            "          </div>  " +
            "          <div class=\"delivery-item-col-auto\">" +
            "            <div class=\"delivery-item-button\">" +
            "                <a class=\"button\" href=\"#\">" + strings.select + "</a>" +
            "            </div>" +
            "        </div>" +
            "      </div>" +
            "  </div>"
    })

    return html;
}

function pvzReplacer(key, value) {
    return (key === 'list_block' || key === 'placeMark') ? undefined : value;
}

function renderDelivery(itemData) {
    jQuery('[data-type="price"]').val(itemData.price);
    jQuery('[data-type="tariff_id"]').val(itemData.tarif);
    if (itemData.id === 'courier') {
        jQuery('[data-type="delivery_method"]').val(itemData.id);
    } else {
        jQuery('[data-type="delivery_method"]').val(itemData.id);
        jQuery('[data-type="pickup_point"]').val(JSON.stringify(itemData.PVZ, pvzReplacer));
    }

    jQuery(".woocommerce-cdek-delivery").remove();

    jQuery(document.body).trigger('update_checkout');
}

function addLoader(element) {
    element.append(jQuery("<div class='loader'><div class=\"blockUI blockOverlay\" style=\"z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; background: rgb(255, 255, 255); opacity: 0.6; cursor: default; position: absolute;\"></div></div>"))
}

function removeLoader(element) {
    element.find(".loader").remove();
}

function selectWooLocality(shipping = false) {
    var languages = {
        errorLoading: function errorLoading() {
            return strings.i18n_searching;
        },
        inputTooLong: function inputTooLong(args) {
            var overChars = args.input.length - args.maximum;

            if (overChars === 1) {
                return strings.i18n_input_too_long_1;
            }

            return strings.i18n_input_too_long_n.replace("%qty%", overChars);
        },
        inputTooShort: function inputTooShort(args) {
            var remainingChars = args.minimum - args.input.length;

            if (remainingChars === 1) {
                return strings.i18n_input_too_short_1;
            }

            return strings.i18n_input_too_short_n.replace("%qty%", remainingChars);
        },
        loadingMore: function loadingMore() {
            return strings.i18n_load_more;
        },
        maximumSelected: function maximumSelected(args) {
            if (args.maximum === 1) {
                return strings.i18n_selection_too_long_1;
            }

            return strings.i18n_selection_too_long_n.replace("%qty%", args.maximum);
        },
        noResults: function noResults() {
            return strings.i18n_no_matches;
        },
        searching: function searching() {
            return strings.i18n_searching;
        }
    };

    var country_field = "";
    var city_field = "";
    var locality = "";
    if (shipping) {
        country_field = '#shipping_country';
        city_field = '#shipping_city';
        locality = '_shipping';
    } else {
        country_field = '#billing_country';
        city_field = '#billing_city';
    }

    if (typeof jQuery(document).selectWoo === 'function') {
        jQuery("[data-type='locality" + locality + "']").selectWoo({
            templateSelection: function templateSelection(container) {
                jQuery('[data-type="geo_id"]').val(container.geo);

                if (container.text) {
                    console.log(container.text);

                    /*var option = jQuery(country_field + " option:contains(" + container.text.split(',').pop() + ")").val();
                    if (typeof option !== 'undefined') {
                        console.log('set ' + option);
                        jQuery(country_field).val(option);
                    }*/
                }

                jQuery(container.element).attr("data-geo", container.geo);
                jQuery(city_field).val(container.text.split(',').shift());
                return container.text;
            },
            ajax: {
                url: wWoocommerceCdekDeliveryIntegration.variables.ajaxurl,
                method: 'POST',
                delay: 500,
                data: function (params) {
                    return {
                        action: "wd-cdek-delivery-city-autocomplete",
                        type: jQuery(this).data("type"),
                        term: params.term
                    };
                },
                processResults: function (data) {
                    var options = [];

                    if (data.data.suggestions) {
                        jQuery.each(data.data.suggestions, function (id, text) {
                            options.push({
                                id: text,
                                text: text,
                                geo: id
                            });
                        });
                    }

                    return {
                        results: options
                    };
                }
            },
            language: languages,
            dropdownAutoWidth: true,
            minimumInputLength: 1
        });
    }
}

function updateData(event) {
    jQuery(document).find('#wdcd_btn_delivery_method').hide();
    jQuery(document).find('.woocommerce-cdek-delivery').remove();

    var locality = '';
    var countryValue = '';
    var cityValue = '';
    var cityInput = '';
    if (jQuery('#ship-to-different-address-checkbox').prop('checked')) {
        cityInput = jQuery("#shipping_city");
        locality = jQuery('[data-type="locality_shipping"]').val();
    } else {
        cityInput = jQuery("#billing_city");
        locality = jQuery('[data-type="locality"]').val();
    }
    countryValue = locality.split(',').pop().trim();
    cityValue = locality.split(',').shift().trim();

    if (cityInput && cityInput[0] && cityInput[0].type && cityInput[0].type.startsWith('select')) {
        cityInput.append(new Option(cityValue, cityValue, true, true));
    } else {
        cityInput.val(cityValue);
    }

    var geo = jQuery('[data-type="geo_id"]').val();

    if (isDebug()) {
        console.log(locality, geo);
    }

    window.cdekWidget = new ISDEKWidjet({
        country: countryValue, // можно выбрать страну, для которой отображать список ПВЗ
        defaultCity: cityValue, //какой город отображается по умолчанию
        cityFrom: wWoocommerceCdekDeliveryIntegration.widget.city_from, // из какого города будет идти доставка
        popup: true,
        path: wWoocommerceCdekDeliveryIntegration.widget.path, //директория с библиотеками
        servicepath: wWoocommerceCdekDeliveryIntegration.widget.servicepath,
        templatepath: wWoocommerceCdekDeliveryIntegration.widget.templatepath,
        apikey: wWoocommerceCdekDeliveryIntegration.widget.maps_api_key,
        goods: [wWoocommerceCdekDeliveryIntegration.widget.goods_sizes],
        onChoose: renderDelivery,
        onChooseProfile: renderDelivery,
        onLoad: function () {
            jQuery(document).find('#wdcd_btn_delivery_method').show();
            jQuery(document).find('#wdcd_widget_loader').hide();
        },
    });

    jQuery('#wdcd_widget_loader').show();
}

jQuery(document)
    .on('change', "[name='shipping_method[0]']", hideNonYDFields)
    .on('update_checkout', hideNonYDFields);

function hideNonYDFields() {
    var method = jQuery("[name='shipping_method[0]']:checked").val();

    var ydElements = [
        '#wdcd_city_field', '#wdcd_ship_city_field'
    ];
    var nativeElements = [
        '#billing_city_field', '#shipping_city_field'
    ];

    var hideOrShow = function (elements, hide) {
        if (hide) {
            elements.forEach(function (el) {
                jQuery(el).hide();
            })
        } else {
            elements.forEach(function (el) {
                jQuery(el).show();
            })
        }
    }

    if (typeof method !== 'string') {
        hideOrShow(nativeElements, true);
    } else if (method.startsWith('wbcr_cdek_delivery')) {
        hideOrShow(ydElements, false);
        hideOrShow(nativeElements, true);
        if (jQuery('#ship-to-different-address-checkbox').prop('checked')) {
            hideOrShow(ydElements.slice(0, 1), true);
            hideOrShow(nativeElements.slice(0, 1), false);
        } else {
            hideOrShow(ydElements.slice(1), true);
            hideOrShow(nativeElements.slice(1), false);
        }

    } else {
        hideOrShow(ydElements, true);
        hideOrShow(nativeElements, false);
    }

    jQuery(document).trigger('addPickPoint');
}

jQuery(document).on('click', '#wdcd_btn_delivery_method', function (e) {
    e.preventDefault();
    window.cdekWidget.open();
});

jQuery(document).ready(function () {

    if (typeof jQuery(document).selectWoo === 'function') {
        selectWooLocality(true);
        selectWooLocality();
        jQuery('#wdcd_city').on('change', updateData);
        jQuery('#wdcd_ship_city').on('change', updateData);
        if (jQuery('#ship-to-different-address-checkbox').prop('checked')) {
            jQuery('#shipping_address_1').on('change', updateData);
            jQuery('#shipping_address_2').on('change', updateData);
        } else {
            jQuery('#billing_address_1').on('change', updateData);
            jQuery('#billing_address_2').on('change', updateData);
        }
    }

    setTimeout(hideNonYDFields, 1000);
});


