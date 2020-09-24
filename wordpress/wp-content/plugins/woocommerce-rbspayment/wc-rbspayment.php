<?php
/*
    Plugin Name: Оплата банковской картой
    Plugin URI:
    Description: Позволяет использовать платежный шлюз <strong></strong> с Инструментом электронной торговли WooCommerce.
    Version: 3.3.1
    Author: RBSPayment
    Author URI: http://www.rbspayment.ru/
 */

if (!defined('ABSPATH')) exit;
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(__DIR__ . '/include.php');

add_filter('plugin_row_meta', 'rbs_register_plugin_links', 10, 2);
function rbs_register_plugin_links($links, $file)
{
    $base = plugin_basename(__FILE__);
    if ($file == $base) {
        $links[] = '<a href="admin.php?page=wc-settings&tab=checkout&section=rbspayment">' . __('Settings', 'woocommerce') . '</a>';
    }
    return $links;
}


add_action('plugins_loaded', 'woocommerce_rbspayment', 0);
function woocommerce_rbspayment()
{
    if (!class_exists('WC_Payment_Gateway'))
        return;
    if (class_exists('WC_RBSPAYMENT'))
        return;

    class WC_RBSPAYMENT extends WC_Payment_Gateway
    {
        public $currency_codes = array(
            'USD' => '840',
            'UAH' => '980',
            'RUB' => '643',
            'RON' => '946',
            'KZT' => '398',
            'KGS' => '417',
            'JPY' => '392',
            'GBR' => '826',
            'EUR' => '978',
            'CNY' => '156',
            'BYR' => '974',
            'BYN' => '933'
        );

        public function __construct()
        {

            $this->id = 'rbspayment';
            $icon_path = is_file(__DIR__ . DIRECTORY_SEPARATOR . 'logo.png') ? plugin_dir_url(__FILE__) . 'logo.png' : null;

            $this->method_title       = __( RBS_PAYMENT_TITLE_1, 'woocommerce' );
            $this->method_description = __( 'Интернет эквайринг и обработка платежей.', 'woocommerce' );

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Endpoints
            $this->prod_url = RBS_PROD_URL;
            $this->test_url = RBS_TEST_URL;
            $this->logging = LOGGING;

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->merchant = $this->get_option('merchant');
            $this->password = $this->get_option('password');
            $this->test_mode = $this->get_option('test_mode');
            $this->stage_mode = $this->get_option('stage_mode');
            $this->description = $this->get_option('description');
            $this->order_status = $this->get_option('order_status');
            $this->icon = $icon_path;

            $this->send_order = $this->get_option('send_order');
            $this->tax_system = $this->get_option('tax_system');
            $this->tax_type = $this->get_option('tax_type');

            $this->success_url = $this->get_option('success_url');
            $this->fail_url = $this->get_option('fail_url');
            $this->ffd_version = $this->get_option('ffd_version');
            $this->ffd_paymentMethodType = $this->get_option('ffd_paymentMethodType');
            $this->ffd_paymentObjectType = $this->get_option('ffd_paymentObjectType');
            $this->ffd_paymentObjectType_delivery = $this->get_option('ffd_paymentMethodType_delivery');

            $this->pData = get_plugin_data(__FILE__);
            $this->measurement_name = 'шт.';

            // Actions
            add_action('valid-rbspayment-standard-ipn-reques', array($this, 'successful_request'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

            // Save options
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }

            $this->callb();
        }


        public function callb()
        {

            if (isset($_GET['rbspayment']) AND $_GET['rbspayment'] == 'result') {
                if ($this->test_mode == 'yes') {
                    $action_adr = $this->test_url;
                } else {
                    $action_adr = $this->prod_url;
                }
                $action_adr .= 'getOrderStatusExtended.do';

                $args = array(
                    'userName' => $this->merchant,
                    'password' => $this->password,
                    'orderId' => $_GET['orderId'],
                );

                $rbsCurl = curl_init();
                curl_setopt_array($rbsCurl, array(
                    CURLOPT_URL => $action_adr,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POSTFIELDS => http_build_query($args, '', '&'),
                    CURLOPT_HTTPHEADER => array(
                        'CMS: Wordpress ' . get_bloginfo('version') . " + woocommerce version: " . wpbo_get_woo_version_number(),
                        'Module-Version: ' . $this->pData['Version']
                    ),
                ));

                $response = curl_exec($rbsCurl);
                curl_close($rbsCurl);

                if ($this->logging) {
                    $this->rbs_logger('Request: ' . $action_adr . ': ' . print_r($args, true) . 'Response: ' . $response);
                }
                $response = json_decode($response, true);

                $orderStatus = $response['orderStatus'];
                if ($orderStatus == '1' || $orderStatus == '2') {
                    $order_id = $_GET['order_id'];
                    $order = new WC_Order($order_id);
                    $order->update_status($this->order_status, __('Платеж успешно оплачен', 'woocommerce'));

                    try {
                        wc_reduce_stock_levels($order_id);

                    } catch (Exception $e) {
                        //noop
                    }

                    $order->payment_complete();

                    if (!empty($this->success_url)) {
                        wp_redirect($this->success_url . "?order_id=" . $order_id);
                        exit;
                    }

                    wp_redirect($this->get_return_url($order));

                    exit;
                } else {
                    $order_id = $_GET['order_id'];
                    $order = new WC_Order($order_id);
                    $order->update_status('failed', __('Платеж не оплачен', 'woocommerce'));
                    add_filter('woocommerce_add_to_cart_message', 'my_cart_messages', 99);
                    $order->cancel_order();

                    wc_add_notice(__('Ошибка в проведении оплаты<br/>' . $response['actionCodeDescription'], 'woocommerce'), 'error');

                    if (!empty($this->fail_url)) {
                        wp_redirect($this->fail_url . "?order_id=" . $order_id);
                        exit;
                    }
                    wp_redirect($order->get_cancel_order_url());
                    exit;
                }
            }
        }

        /**
         * Check if this gateway is enabled and available in the user's country
         */
        function is_valid_for_use()
        {
            return true;
        }

        /*
         * Admin Panel Options
         */
        public function admin_options()
        {
            ?>
            <h3><?php _e(RBS_PAYMENT_TITLE_1, 'woocommerce'); ?></h3>
            <p><?php _e(RBS_PAYMENT_TITLE_2, 'woocommerce'); ?></p>

            <?php if ($this->is_valid_for_use()) : ?>
            <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
            </table>
        <?php else : ?>
            <div class="inline error"><p>
                    <strong><?php _e('Ошибка: ', 'woocommerce'); ?></strong>: <?php _e($this->id . ' не поддерживает валюты Вашего магазина.', 'woocommerce'); ?>
                </p></div>
            <?php
        endif;

        }

        /*
         * Initialise Gateway Settings Form Fields
         */
        function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Включить/Выключить', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Включен', 'woocommerce'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Название', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Заголовок, который видит пользователь в процессе оформления заказа.', 'woocommerce'),
                    'default' => __('Оплата картой банка', 'woocommerce'),
                    'desc_tip' => true,
                ),
                'merchant' => array(
                    'title' => __('Логин', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Пожалуйста введите Логин выданный банком.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'password' => array(
                    'title' => __('Пароль', 'woocommerce'),
                    'type' => 'password',
                    'description' => __('Пожалуйста введите Пароль выданный банком.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'test_mode' => array(
                    'title' => __('Тест режим', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Включен', 'woocommerce'),
                    'description' => __('В этом режиме плата за товар не снимается.', 'woocommerce'),
                    'default' => 'no'
                ),
                'stage_mode' => array(
                    'title' => __('Стадийность платежей', 'woocommerce'),
                    'type' => 'select',
                    'class'       => 'wc-enhanced-select',
                    'default' => 'one-stage',
                    'options' => array(
                        'one-stage' => __('Одностадийные платежи', 'woocommerce'),
                        'two-stage' => __('Двухстадийные платежи', 'woocommerce'),
                    ),
                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Описание метода оплаты которое клиент будет видеть на вашем сайте.', 'woocommerce'),

                ),
                'order_status' => array(
                    'title'       => __( 'Статус оплаченного заказа', 'woocommerce'),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Статус заказа при удачном завершении платежа.', 'woocommerce'),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),

                'success_url' => array(
                    'title' => __('success_url', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Страница, на которую будет перенаправлен пользователь в случае <b>успешной оплаты</b>.<br/>Оставьте это поле пустым, если хотите использовать настройки по умолчанию.', 'woocommerce'),
                ),
                'fail_url' => array(
                    'title' => __('fail_url', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Страница, на которую будет перенаправлен пользователь в случае <b>неудачной оплаты</b>.<br/>Оставьте это поле пустым, если хотите использовать настройки по умолчанию.', 'woocommerce'),
                ),
                'send_order' => array(
                    'title' => __('Передача корзины товаров', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Включена', 'woocommerce'),
                    'description' => __('При выборе опции, будет сформирован и отправлен в налоговую и клиенту чек.<br/>Опция платная, за подключением обратитесь в сервисную службу банка. При использовании необходимо настроить НДС продаваемых товаров. НДС рассчитывается согласно законодательству РФ, возможны расхождения в размере НДС с суммой рассчитанной магазином.', 'woocommerce'),
                    'default' => 'no'
                ),
                'tax_system' => array(
                    'title' => __('Система налогообложения', 'woocommerce'),
                    'type' => 'select',
                    'class'       => 'wc-enhanced-select',
                    'default' => '0',
                    'options' => array(
                        '0' => __('Общая', 'woocommerce'),
                        '1' => __('Упрощённая, доход', 'woocommerce'),
                        '2' => __('Упрощённая, доход минус расход', 'woocommerce'),
                        '3' => __('Eдиный налог на вменённый доход', 'woocommerce'),
                        '4' => __('Eдиный сельскохозяйственный налог', 'woocommerce'),
                        '5' => __('Патентная система налогообложения', 'woocommerce'),
                    ),
                ),
                'tax_type' => array(
                    'title' => __('Ставка НДС по умолчанию', 'woocommerce'),
                    'type' => 'select',
                    'class'       => 'wc-enhanced-select',
                    'default' => '0',
                    'options' => array(
                        '0' => __('Без НДС', 'woocommerce'),
                        '1' => __('НДС по ставке 0%', 'woocommerce'),
                        '2' => __('НДС чека по ставке 10%', 'woocommerce'),
                        '3' => __('НДС чека по ставке 18%', 'woocommerce'),
                        '4' => __('НДС чека по расчетной ставке 10/110', 'woocommerce'),
                        '5' => __('НДС чека по расчетной ставке 18/118', 'woocommerce'),
                        '6' => __('НДС чека по расчетной ставке 20%', 'woocommerce'),
                        '7' => __('НДС чека по расчетной ставке 20/120', 'woocommerce'),
                    ),
                ),
                'ffd_version' => array(
                    'title' => __('Формат фискальных документов', 'woocommerce'),
                    'type' => 'select',
                    'class'       => 'wc-enhanced-select',
                    'default' => 'v105',
                    'options' => array(
                        'v10' => __('ФФД 1.0', 'woocommerce'),
                        'v105' => __('ФФД 1.05', 'woocommerce'),
//                        'v11' => __('ФФД 1.1', 'woocommerce'),
                    ),
                    'description' => __('Формат версии требуется указать в личном кабинете банка и в кабинете сервиса фискализации.', 'woocommerce'),
                ),
                'ffd_paymentMethodType' => array(
                    'title' => __('Тип оплаты', 'woocommerce'),
                    'type' => 'select',
                    'class'       => 'wc-enhanced-select',
                    'default' => '1',
                    'options' => array(
                        '1' => __('Полная предварительная оплата до момента передачи предмета расчёта', 'woocommerce'),
                        '2' => __('Частичная предварительная оплата до момента передачи предмета расчёта', 'woocommerce'),
                        '3' => __('Аванс', 'woocommerce'),
                        '4' => __('Полная оплата в момент передачи предмета расчёта', 'woocommerce'),
                        '5' => __('Частичная оплата предмета расчёта в момент его передачи с последующей оплатой в кредит', 'woocommerce'),
                        '6' => __('Передача предмета расчёта без его оплаты в момент его передачи с последующей оплатой в кредит', 'woocommerce'),
                        '7' => __('Оплата предмета расчёта после его передачи с оплатой в кредит', 'woocommerce'),
                    ),
                    'description' => __('Используется в версих ФФД, начиная с 1.05', 'woocommerce'),
                ),
                'ffd_paymentMethodType_delivery' => array(
                    'title' => __('Тип оплаты для доставки', 'woocommerce'),
                    'type' => 'select',
                    'class'       => 'wc-enhanced-select',
                    'default' => '1',
                    'options' => array(
                        '1' => __('Полная предварительная оплата до момента передачи предмета расчёта', 'woocommerce'),
                        '2' => __('Частичная предварительная оплата до момента передачи предмета расчёта', 'woocommerce'),
                        '3' => __('Аванс', 'woocommerce'),
                        '4' => __('Полная оплата в момент передачи предмета расчёта', 'woocommerce'),
                        '5' => __('Частичная оплата предмета расчёта в момент его передачи с последующей оплатой в кредит', 'woocommerce'),
                        '6' => __('Передача предмета расчёта без его оплаты в момент его передачи с последующей оплатой в кредит', 'woocommerce'),
                        '7' => __('Оплата предмета расчёта после его передачи с оплатой в кредит', 'woocommerce'),
                    ),
                    'description' => __('Используется в версих ФФД, начиная с 1.05', 'woocommerce'),
                ),
                'ffd_paymentObjectType' => array(
                    'title' => __('Тип оплачиваемой позиции', 'woocommerce'),
                    'type' => 'select',
                    'class'       => 'wc-enhanced-select',
                    'default' => '1',
                    'options' => array(
                        '1' => __('Товар', 'woocommerce'),
                        '2' => __('Подакцизный товар', 'woocommerce'),
                        '3' => __('Работа', 'woocommerce'),
                        '4' => __('Услуга', 'woocommerce'),
                        '5' => __('Ставка азартной игры', 'woocommerce'),
//                        '6' => __('Выигрыш азартной игры', 'woocommerce'),
                        '7' => __('Лотерейный билет', 'woocommerce'),
//                        '8' => __('Выигрыш лотереи', 'woocommerce'),
                        '9' => __('Предоставление РИД', 'woocommerce'),
                        '10' => __('Платёж', 'woocommerce'),
                        '11' => __('Агентское вознаграждение', 'woocommerce'),
                        '12' => __('Составной предмет расчёта', 'woocommerce'),
                        '13' => __('Иной предмет расчёта', 'woocommerce'),

                    ),
                    'description' => __('Используется в версих ФФД, начиная с 1.05', 'woocommerce'),
                ),

            );
        }

        function get_product_price_with_discount($price, $type, $c_amount, &$order_data)
        {

            switch ($type) {
                case 'percent':
                    $new_price = ceil($price * (1 - $c_amount / 100));

                    // remove this discount from discount_total
                    $order_data['discount_total'] -= ($price - $new_price);
                    break;

//                case 'fixed_cart':
//                    //wrong
//                    $new_price = $price;
//                    break;

                case 'fixed_product':
                    $new_price = $price - $c_amount;

                    // remove this discount from discount_total
                    $order_data['discount_total'] -= $c_amount / 100;
                    break;

                default:
                    $new_price = $price;
            }
            return $new_price;
        }

        /*
         * Generate the dibs button link
         */
        public function generate_form($order_id)
        {
            $order = new WC_Order($order_id);

            $amount = $order->get_total() * 100;

            // COUPONS
            $coupons = array();
            global $woocommerce;
            if (!empty($woocommerce->cart->applied_coupons)) {
                foreach ($woocommerce->cart->applied_coupons as $code) {
                    $coupons[] = new WC_Coupon($code);
                }
            }


            if ($this->test_mode == 'yes') {
                $action_adr = $this->test_url;
            } else {
                $action_adr = $this->prod_url;
            }

            $extra_url_param = '';
            if ($this->stage_mode == 'two-stage') {
                $action_adr .= 'registerPreAuth.do';
            } else if ($this->stage_mode == 'one-stage') {
                $extra_url_param = '&wc-callb=callback_function';
                $action_adr .= 'register.do';
            }

            $order_data = $order->get_data();

            $language = substr(get_bloginfo("language"), 0, 2);

            // todo: fix Gate bug locale2country
            switch ($language) {
                case  ('uk'):
                    $language = 'ua';
                    break;
                case ('be'):
                    $language = 'by';
                    break;
            }


            // prepare args array
            $args = array(
                'userName' => $this->merchant,
                'password' => $this->password,
                'amount' => $amount,
                'language' => $language,
                'returnUrl' => get_option('siteurl') . '?wc-api=WC_RBSPAYMENT&rbspayment=result&order_id=' . $order_id . $extra_url_param,
                'currency' => $this->currency_codes[get_woocommerce_currency()],
                'jsonParams' => json_encode(
                    array(
                        'CMS:' => 'Wordpress ' . get_bloginfo('version') . " + woocommerce version: " . wpbo_get_woo_version_number(),
                        'Module-Version: ' => $this->pData['Version'],
                        'email' => $order_data['billing']['email'],
                        'phone' => $order_data['billing']['phone'],
                    )
                ),

            );


            if ($this->send_order == 'yes') {

                $args['taxSystem'] = $this->tax_system;

                $order_items = $order->get_items();

                $order_timestamp_created = $order_data['date_created']->getTimestamp();
                $order_billing_email = $order_data['billing']['email'];

                $items = array();
                $itemsCnt = 1;

                /* Заполнение массива данных корзины */
                foreach ($order_items as $value) {
                    $item = array();
                    $tax = new WC_Tax();
                    $product_variation_id = $value['variation_id'];

                    if ($product_variation_id) {
                        $product = new WC_Product_Variation($value['variation_id']);
                        $item_code = $itemsCnt . "-" . $value['variation_id'];
                    } else {
                        $product = new WC_Product($value['product_id']);
                        $item_code = $itemsCnt . "-" .$value['product_id'];
                    }

                    $base_tax_rates = $tax->get_base_tax_rates($product->get_tax_class(true));
                    if (!empty($base_tax_rates)) {
                        $rates = array_shift($tax->get_rates($product->get_tax_class()));
                        $item_rate = round(array_shift($rates));
                        if ($item_rate == 20) {
                            $tax_type = 6;
                        } else if ($item_rate == 18) {
                            $tax_type = 3;
                        } else if ($item_rate == 10) {
                            $tax_type = 2;
                        } else if ($item_rate == 0) {
                            $tax_type = 1;
                        } else {
                            $tax_type = 0;
                        }
                    } else {
                        $tax_type = $this->tax_type;
                    }

                    $product_price = round(($product->get_price()) * 100);


                    if ($product->get_type() == 'variation') {
                        //TODO
                    }


                    // if discount (coupon etc)
                    // see DISCOUNT SECTION
//                    foreach ($coupons as $coupon) {
//                        $coupon_amount = $coupon->get_amount() * 100;
//                        $product_price = $this->get_product_price_with_discount($product_price, $coupon->get_discount_type(), $coupon_amount, $order_data );
//                    }

                    $item['positionId'] = $itemsCnt++;
                    $item['name'] = $value['name'];
                    $item['quantity'] = array(
                        'value' => $value['quantity'],
                        'measure' => $this->measurement_name
                    );
                    $item['itemAmount'] = $product_price * $value['quantity'];
                    $item['itemCode'] = $item_code;
                    $item['tax'] = array(
                        'taxType' => $tax_type
                    );
                    $item['itemPrice'] = $product_price;

                    // FFD 1.05 added
                    if ($this->ffd_version == 'v105') {

                        $attributes = array();
                        $attributes[] = array(
                            "name" => "paymentMethod",
                            "value" => $this->ffd_paymentMethodType
                        );
                        $attributes[] = array(
                            "name" => "paymentObject",
                            "value" => $this->ffd_paymentObjectType
                        );

                        $item['itemAttributes']['attributes'] = $attributes;

                    }

                    $items[] = $item;
                }


                // delivery_total
                $shipping_total = $order->get_shipping_total();

                // DISCOUNT
                if (!empty($order_data['discount_total'])) {
                    $discount = ($order_data['discount_total'] + $order_data['discount_tax']) * 100;

                    $new_order_total = 0;

                    // coze delivery will be another position
                    $delivery_sum = ($shipping_total > 0) ? $shipping_total * 100 : 0;

                    foreach ($items as &$i) {

                        $p_discount = intval(round(($i['itemAmount'] / ($amount - $delivery_sum + $discount)) * $discount, 2));

                        $this->correctBundleItem($i, $p_discount);
                        $new_order_total += $i['itemAmount'];
                    }

                    // reset order amount
                    // return delivery_sum into amount
                    $args['amount'] = $new_order_total + $delivery_sum;
                }


                // DELIVERY POSITION
                if ($shipping_total > 0) {
                    $itemShipment['positionId'] = $itemsCnt;
                    $itemShipment['name'] = 'Доставка';
                    $itemShipment['quantity'] = array(
                        'value' => 1,
                        'measure' => $this->measurement_name
                    );
                    $itemShipment['itemAmount'] = $itemShipment['itemPrice'] = $shipping_total * 100;
                    $itemShipment['itemCode'] = 'Delivery';
                    $itemShipment['tax'] = array(
                        'taxType' => $this->tax_type
                    );

                    // FFD 1.05 added
                    if ($this->ffd_version == 'v105') {
                        $attributes = array();
                        $attributes[] = array(
                            "name" => "paymentMethod",
                            "value" => $this->ffd_paymentObjectType_delivery
                        );
                        $attributes[] = array(
                            "name" => "paymentObject",
                            "value" => 4
                        );

                        $itemShipment['itemAttributes']['attributes'] = $attributes;

                    }

                    $items[] = $itemShipment;
                }

                /* Создание и заполнение массива данных заказа для фискализации */
                $order_bundle = array(
                    'orderCreationDate' => $order_timestamp_created,
                    'customerDetails' => array(
                        'email' => $order_billing_email
                    ),
                    'cartItems' => array('items' => $items)
                );


                /* Заполнение массива данных для запроса c фискализацией */
                $args['orderBundle'] = json_encode($order_bundle);
            }

            //$args['orderNumber'] = $order_id . '_' . time();
            $args['orderNumber'] = trim( str_replace( '#', '', $order->get_order_number() ) ) . "_" . time();


            $rbsCurl = curl_init();
            curl_setopt_array($rbsCurl, array(
                CURLOPT_HTTPHEADER => array(
                    'CMS: Wordpress ' . get_bloginfo('version') . " + woocommerce version: " . wpbo_get_woo_version_number(),
                    'Module-Version: ' . $this->pData['Version'],
                ),
                CURLOPT_URL => $action_adr,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($args, '', '&')
            ));

            $response = curl_exec($rbsCurl);
            curl_close($rbsCurl);

            if ($this->logging) {
                $this->rbs_logger('Request: ' . $action_adr . ': ' . print_r($args, true) . 'Response: ' . $response, true);
            }

            $response = json_decode($response, true);

            if (empty($response['errorCode'])) {

                wp_redirect($response['formUrl']);

                //EI: if is error in the headers (already send)
                echo '<p><a class="button cancel" href="'.$response['formUrl'].'">'.__('Оплатить', 'woocommerce').'</a></p>';
                exit;

            } else {
                return '<p>' . __('Ошибка #' . $response['errorCode'] . ': ' . $response['errorMessage'], 'woocommerce') . '</p>' .
                '<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Отказаться от оплаты и вернуться в корзину', 'woocommerce') . '</a>';
            }
        }


        function correctBundleItem(&$item, $discount)
        {

            $item['itemAmount'] -= $discount;
            $item['itemPrice'] = $item['itemAmount'] % $item['quantity']['value'];
            if ($item['itemPrice'] != 0) {
                $item['itemAmount'] += $item['quantity']['value'] - $item['itemPrice'];
            }

            $item['itemPrice'] = $item['itemAmount'] / $item['quantity']['value'];
        }


        /*
         * Process the payment and return the result
         */
        function process_payment($order_id)
        {
            $order = new WC_Order($order_id);

            //if PLUG-2403
            if (!empty($_GET['pay_for_order'])  && $_GET['pay_for_order']== 'true'){
                $this->generate_form($order_id);
                die;
            }

            $pay_now_url = esc_url( $order->get_checkout_payment_url( true) );

            return array(
                'result' => 'success',
                'redirect' => $pay_now_url
            );

        }

        /*
         * Receipt page
         */
        function receipt_page($order)
        {
            echo $this->generate_form($order);
        }


        function rbs_logger($var, $info = false)
        {
            $information = "";
            if ($var) {
                if ($info) {
                    $information = "\n\n";
                    $information .= str_repeat("-=", 64);
                    $information .= "\nDate: " . date('Y-m-d H:i:s');
                    $information .= "\nWordpress version " . get_bloginfo('version') . "; Woocommerce version: " . wpbo_get_woo_version_number() . "\n";
                }

                $result = $var;
                if (is_array($var) || is_object($var)) {
                    $result = "\n" . print_r($var, true);
                }
                $result .= "\n\n";
                $path = dirname(__FILE__) . '/wc_rbspayment.log';
                error_log($information . $result, 3, $path);
                return true;
            }
            return false;
        }

    }


    function add_rbspayment_gateway($methods)
    {
        $methods[] = 'WC_RBSPAYMENT';
        return $methods;
    }

    if (!function_exists('wpbo_get_woo_version_number')) {
        function wpbo_get_woo_version_number()
        {
            // If get_plugins() isn't available, require it
            if (!function_exists('get_plugins'))
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');

            // Create the plugins folder and file variables
            $plugin_folder = get_plugins('/' . 'woocommerce');
            $plugin_file = 'woocommerce.php';

            // If the plugin version number is set, return it
            if (isset($plugin_folder[$plugin_file]['Version'])) {
                return $plugin_folder[$plugin_file]['Version'];

            } else {
                // Otherwise return null
                return NULL;
            }
        }
    }

    add_filter('woocommerce_payment_gateways', 'add_rbspayment_gateway');
}