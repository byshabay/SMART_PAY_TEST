<?php
/**
 * URL API платежного шлюза
 */

define('RBS_PAYMENT_NAME', 'Сбербанк');

define('RBS_PROD_URL' , 'https://securepayments.sberbank.ru/payment/rest/');
define('RBS_TEST_URL' , 'https://3dsec.sberbank.ru/payment/rest/');

/**
 * Логирование
 */
define('LOGGING', true);


/**
 * Настройки отображения
 */

// Заголовки в админке плагина [WooCommerce -> Настройки -> Оплата -> *Плагин*]
define('RBS_PAYMENT_TITLE_1', RBS_PAYMENT_NAME );
define('RBS_PAYMENT_TITLE_2', 'Настройка приема электронных платежей через ' . RBS_PAYMENT_NAME);
