<?php
return [
	'balance_link' => 'https://sms.targetsms.ru/ru/cabinet/pay.html',
	'personal_account_link' => 'https://sms.targetsms.ru',
	'instruction_link' => 'https://targetsms.ru/otpravka-sms-iz-wordpress-woocommerce',
	'email_api_url' => 'http://apiagent.ru/api.php',

	'sms_api_url_balance' => 'https://sms.targetsms.ru/xml/balance.php',
	'sms_api_url_fromname' => 'https://sms.targetsms.ru/xml/originator.php',
	'sms_api_url_templates' => 'https://sms.targetsms.ru/xml/list_patterns.php',
	'sms_api_url_history' => 'https://sms.targetsms.ru/xml/stats.php',
	'sms_api_url_send' => 'https://sms.targetsms.ru/xml/',

	'tag' => [
		'StoresName' => 'Название (Магазин)',
		'OrderID' => 'ID (Заказ)',
		'OrderSum' => 'Сумма (Заказ)',
		'ClientName' => 'Имя (Заказ)',
		'ClientLastName' => 'Фамилия (Заказ)',
		'OrderStatus' => 'Статус (Заказ)',
		'AddrOrderDelivery' => 'Адрес доставки (Заказ)',
		'AddrPayment' => 'Адрес оплаты (Заказ)',
		'MethodPayment' => 'Метод оплаты (Заказ)',
		'ClientEmail' => 'Email (Заказ)',
		'ClientPhone' => 'Номер телефона (Заказ)',
	],
	'status' => [
		'pending' => 'WaitingForPayment',
		'processing' => 'Treatment',
		'on-hold' => 'OnHold',
		'completed' => 'Done',
		'cancelled' => 'Canceled',
		'refunded' => 'Returned',
		'failed' => 'Failed',
	],
];