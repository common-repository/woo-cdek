=== Woocommerce Cdek Delivery ===
Tags: woocommerce, delivery, post, cdek, сдэк, доставка
Contributors: creativemotion
Requires at least: 4.9
Tested up to: 5.5
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2
WC requires at least: 4.3
WC tested up to: 4.4.1

This is a sample plugin that implements additional delivery functions for the Woocommerce store.

== Description ==

== Translations ==

* English - default, always included
* Russian

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 1.1.0 =
* Change: Изменена структура плагина, код теперь чистый
* Add: Логирование запросов

= 1.0.9 =
* Fix: При оплате при заказе запрашивается полная цена корзины + доставка

= 1.0.7 =
* Добавлены настройки доставки
* Добавлена скидка на доставку при достижении указанной суммы
* Добавлен хук `woocommerce_wdcd_delivery_cost`

= 1.0.6 =
* Мелкие исправления

= 1.0.5 =
* Добавлена настройка: Статус заказа для отправки данных в Сдэк Доставку

= 1.0.4 =
* Small fixes

= 1.0.0 =
* First plugin release
