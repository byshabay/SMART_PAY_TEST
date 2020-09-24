<?php
class AomailerAdmin
{
	protected $page;
	protected $capability;
	protected $url;
	protected $functions_page;
	protected $view_path;
	protected $config;
	
	public $settings = [];
	public $history = [];
	public $is_plugin_page = 0;
	
	public function __construct($is_plugin_page)
	{
		$this->page = [
			1=>__('SettingsSMS', 'aomailer'),
			2=>__('SettingsEmail', 'aomailer')
		];
		$this->capability = 'edit_others_pages';
		$this->url = [
			1=>'aomailer-sms',
			2=>'aomailer-email',
		];
		$this->functions_page = [
			1=>'settings_page_sms',
			2=>'settings_page_email',
		];
		
		$this->is_plugin_page = $is_plugin_page;
		$this->view_path =  realpath(AOMP_AOMAILER_DIR) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
	}
	
	/**
	 * wpAdmin()
	 */
	public function wpAdmin()
	{
		add_action('admin_menu', [$this, 'settings_menu']);
		add_action('wp_ajax_aomailer_balance_action', [$this, 'balance_action']);
		add_action('wp_ajax_aomailer_select_template', [$this, 'select_template']);
		add_action('wp_ajax_aomailer_load_form_smssettings', [$this, 'load_form_smssettings']);
		add_action('wp_ajax_aomailer_load_form_smsmailing', [$this, 'load_form_smsmailing']);
		add_action('wp_ajax_aomailer_load_form_smsevents', [$this, 'load_form_smsevents']);
		add_action('wp_ajax_aomailer_load_form_smstemplates', [$this, 'load_form_smstemplates']);
		add_action('wp_ajax_aomailer_load_form_smshistory', [$this, 'load_form_smshistory']);
		add_action('wp_ajax_aomailer_load_form_emailsettings', [$this, 'load_form_emailsettings']);
	}

	/**
	 * settings_menu()
	 */
	public function settings_menu()
	{
		add_submenu_page(
			'woocommerce',
			$this->page[1],
			$this->page[1], 
			$this->capability, 
			$this->url[1],
			[$this, $this->functions_page[1]] 
		);

		add_submenu_page(
			'woocommerce',
			$this->page[2],
			$this->page[2], 
			$this->capability, 
			$this->url[2],
			[$this, $this->functions_page[2]] 
		);
	}

	/**
	 * settings_page_sms()
	 */
	public function settings_page_sms()
	{
		if (!empty($this->is_plugin_page)) {
			
			self::resourceRegistration();
			
			$this->settings = $this->settings + AomailerDB::aomp()->loadSettings('sms');
			$this->settings['error'] = false;

			$this->history = AomailerSMSApi::aomp()->getHistory();	
		}

		if (file_exists($this->view_path.'settings_page_sms.php')) {
			require_once $this->view_path.'settings_page_sms.php';
		}
	}
	
	/**
	 * settings_page_email()
	 */
	public function settings_page_email()
	{
		if (!empty($this->is_plugin_page)) {
			
			self::resourceRegistration();
			
			$this->settings = $this->settings + AomailerDB::aomp()->loadSettings('email');
			$this->settings['error'] = false;
		}

		if (file_exists($this->view_path.'settings_page_email.php')) {
			require_once $this->view_path.'settings_page_email.php';
		}
	}

	/**
	 * balance_action() 
	 */
	public function balance_action() 
	{
		$balance = 0;
		if (!self::validateToken($_POST['token'])) {
			echo $balance;
			wp_die(); 
		}

		$settings = AomailerDB::aomp()->loadSettings('sms');

		if (!empty($_POST['type']) && $_POST['type']==='balance') {
			
			if (!empty($settings['login']) && !empty($settings['passwd'])) {
				$answer = AomailerSMSApi::aomp()->getBalance($settings['login'], $settings['passwd']);

				if (empty($answer['error'])) {
					$balance = AomailerSMSApi::aomp()->getFormat($answer['balance'], 'money');
				}
			}
		}
		
		echo $balance;
		wp_die(); 
	}
	
	/**
	 * select_template() 
	 */
	public function select_template() 
	{
		if (!empty((int) $_GET['id_admin'])) {
			$this->settings['id_admin'] = (int) $_GET['id_admin'];
		}
		
		if (!empty((int) $_GET['id_client'])) {
			$this->settings['id_client'] = (int) $_GET['id_client'];
		}

		$this->settings = $this->settings + AomailerDB::aomp()->loadSettings('sms');
		
		$from_name = AomailerSMSApi::aomp()->getFromName($this->settings['login'], $this->settings['passwd']);
		if (empty($from_name['error'])) {
			$this->settings['array_from_name'] = $from_name['from_name'];
		} elseif (empty($answer['error'])) {
			$this->settings['error'] = self::addError($from_name['error']);
		}
		
		$admin_settings = $this->settings['admin_template_settings'][$this->settings['id_admin']];
		if (!empty($admin_settings) && is_array($admin_settings)) {
			foreach ($admin_settings as $name=>$value) {
				$this->settings[$name] = $value;
			}
		}
		
		$client_settings = $this->settings['client_template_settings'][$this->settings['id_client']];
		if (!empty($client_settings) && is_array($client_settings)) {
			foreach ($client_settings as $name=>$value) {
				$this->settings[$name] = $value;
			}
		}
		
		require_once realpath(AOMP_AOMAILER_DIR) . '/views/sms_page/__sms_templates.php'; 
		wp_die(); 
	}
	
	/**
	 * load_form_smssettings() 
	 */
	public function load_form_smssettings() 
	{
		if (!self::validateToken($_POST['data']['token'])) {
			wp_die(); 
		}
		
		if (!empty($_POST['data']['AomailerSmsSettings'])) {
			
			$data = new AomailerDB;
			$data->attributes($_POST['data']['AomailerSmsSettings']);

			if (!$data->add_data()) {
				$this->settings['error'] = self::addError(__('No Save', 'aomailer'));
			} else {
				$this->settings['success'] =__('ConnectSuccess', 'aomailer');	
			} 

		} else {
			$this->settings['error'] =__('Missing data', 'aomailer');
		}

		$this->settings = $this->settings + AomailerDB::aomp()->loadSettings('sms');

		require_once realpath(AOMP_AOMAILER_DIR) . '/views/sms_page/__sms_settings.php'; 
		wp_die();
	}
	
	/**
	 * load_form_emailsettings() 
	 */
	public function load_form_emailsettings() 
	{
		if (!self::validateToken($_POST['data']['token'])) {
			wp_die(); 
		}

		if (!empty($_POST['data']['AomailerEmailSettings'])) {
			
			$data = new AomailerDB;
			$data->attributes($_POST['data']['AomailerEmailSettings']);

			if (!$data->add_data()) {
				$this->settings['error'] = self::addError(__('No Save', 'aomailer'));
			} else {
				$this->settings['success'] =__('ConnectSuccess', 'aomailer');
			} 
		} else {
			$this->settings['error'] =__('Missing data', 'aomailer');
		}
		
		$this->settings = $this->settings + AomailerDB::aomp()->loadSettings('email');
		
		require_once realpath(AOMP_AOMAILER_DIR) . '/views/email_page/__email_settings.php'; 
		wp_die(); 
	}

	/**
	 * load_form_smsevents() 
	 */
	public function load_form_smsevents() 
	{
		if (!self::validateToken($_POST['data']['token'])) {
			wp_die(); 
		}

		if (!empty($_POST['data']['AomailerSmsEvents'])) {
			
			$data = new AomailerDB;
			$data->attributes($_POST['data']['AomailerSmsEvents']);

			if (!$data->add_data()) {
				$this->settings['error'] = self::addError(__('No Save', 'aomailer'));
			} else {
				$this->settings['success'] =__('ConnectSuccess', 'aomailer');
			} 	
		} else {
			$this->settings['error'] =__('Missing data', 'aomailer');
		}

		$this->settings = $this->settings + AomailerDB::aomp()->loadSettings('sms');

		require_once realpath(AOMP_AOMAILER_DIR) . '/views/sms_page/__sms_events.php'; 
		wp_die(); 
	}
	
	/**
	 * load_form_smstemplates() 
	 */
	public function load_form_smstemplates() 
	{
		if (!self::validateToken($_POST['data']['token'])) {
			wp_die(); 
		}

		if (!empty((int) $_POST['data']['id_admin'])) {
			$this->settings['id_admin'] = (int) $_POST['data']['id_admin'];
		}

		if (!empty((int) $_POST['data']['id_client'])) {
			$this->settings['id_client'] = (int) $_POST['data']['id_client'];
		}

		if (!empty($_POST['data']['AomailerSmsTemplates'])) {

			$data = new AomailerDB;
			$data->attributes($_POST['data']['AomailerSmsTemplates']);

			if (!$data->add_serialize_data()) {
				$this->settings['error'] = self::addError(__('No Save', 'aomailer'));
			} else {
				$this->settings['success'] =__('ConnectSuccess', 'aomailer');
			}	
		} else {
			$this->settings['error'] =__('Missing data', 'aomailer');
		}
		
		$this->settings = $this->settings + AomailerDB::aomp()->loadSettings('sms');
		
		require_once realpath(AOMP_AOMAILER_DIR) . '/views/sms_page/__sms_templates.php'; 
		wp_die();
	}

	/**
	 * load_form_smsmailing() 
	 */
	public function load_form_smsmailing() 
	{
		if (!self::validateToken($_POST['data']['token'])) {
			wp_die(); 
		}
		
		$this->settings = $this->settings + AomailerDB::aomp()->loadSettings('sms');

		if (!empty($_POST['data']['AomailerSmsMailing'])) {
			
			$status = self::getStatusArray();

			if (!empty($status)) {
				if ($status[0]=='all') {
					$status = [];
				}

				$result = AomailerDB::aomp()->getDataUsers($status);	
			}

			if ($_POST['data']['query_type']==='send') {

				$data = new AomailerSMSApi;
				$data->attributes($_POST['data']['AomailerSmsMailing']);
				$array = [
					'template_mailing' => !empty($data->text_sms) ? $data->text_sms : '',
					'date_send' => !empty($data->date_send) ? $data->date_send : date('Y-m-d H:i:s'),
					'used_translit' => !empty($data->used_translit) ? 1 : 0,
					'order' => [],
				];
					
				if (!empty($data->selection_recipients)) {
					$selection = explode(',', (string) preg_replace('/[\s]{1,}/', '', $data->selection_recipients));
				}
		
				if (!empty($result) && is_array($result)) {
					foreach ($result['value'] as $key=>$value) {
						$phone = AomailerSMSApi::aomp()->getFormat($value['_billing_phone'], 'phone');
						if (in_array($phone, $selection)) {
							$selection = array_diff($selection, [$phone]);
							$array['order'][$phone] = $value;
						}
					}
				}

				if (!empty($selection) && is_array($selection)) {
					foreach ($selection as $phone) {
						$array['order'][$phone] = [];	
					}	
				}
				
				$data = self::prepareData($array);

				if (empty($data)) {
					self::addError(__('Missing data', 'aomailer'));
				} else {
					
					$send = AomailerSMSApi::aomp()->send($data);	
					if (!empty($send['send']) && is_array($send['send'])) {
						foreach ($send['send'] as $value) {
							if ($value['value']=='send') {
								continue;
							}
								
							$this->settings['error'] = self::addError($value['value']);
						}
					}

					if (empty($send['error']) && empty($this->settings['error'])) {
						$this->settings['mailing_settings'] = false;
						$this->settings['success'] =__('Message sent successfully', 'aomailer');
					} else {
						if (empty($this->settings['error'])) {
							$this->settings['error'] = self::addError(__('Could not send newsletter', 'aomailer'));
						}
					}
				}
	
			} elseif ($_POST['data']['query_type']==='selected') {

				$phone = [];
				if (!empty($result) && is_array($result)) {
					foreach ($result['value'] as $value) {
						if (!empty($value['_billing_phone'])) {
							$phone[] = AomailerSMSApi::aomp()->getFormat($value['_billing_phone'], 'phone');
						}
					}
				}
					
				$phone = array_unique($phone);
					
				$this->settings['mailing_settings']['selection_recipients'] = implode(', ', $phone);

			} else {
				$this->settings['error'] = self::addError(__('Missing data', 'aomailer'));
			}

		} else {
			$this->settings['error'] =__('Missing data', 'aomailer');
		}

		require_once realpath(AOMP_AOMAILER_DIR) . '/views/sms_page/__sms_mailing.php'; 
		wp_die();
	}

	/**
	 * load_form_smshistory() 
	 */
	public function load_form_smshistory() 
	{
		if (!self::validateToken($_POST['data']['token'])) {
			wp_die(); 
		}
		
		$this->settings = $this->settings + AomailerDB::aomp()->loadSettings('sms');

		if (!empty($_POST['data']['AomailerSmsHistory'])) {
			
			$data = new AomailerSMSApi;
			$data->attributes($_POST['data']['AomailerSmsTemplates']);

			$this->history = $data->getHistory();
			if (empty($this->history) || !empty($this->history['error'])) {
				$this->settings['error'] = self::addError(__('No History', 'aomailer'));
			}	
		}

		require_once realpath(AOMP_AOMAILER_DIR) . '/views/sms_page/__sms_history.php'; 
		wp_die();
	}

	/**
	 * addError($error)
	 */
	public function addError($error='')
	{
		if (!empty($error)) {

			if (empty($this->settings['error'])) {
				
				$this->settings['error'] = $error.'<br>';

			} else {
		
				if (!preg_match('/('.$error.')/i', $this->settings['error'])) {
					$this->settings['error'] .= $error.'<br>';
				}
			}
		}
		
		return $this->settings['error'];
	}
	
	/**
	 * resourceRegistration()
	 */
	public function resourceRegistration() {
	
		wp_enqueue_style('aomp_bootstrap_min_css', plugins_url('assets/css/bootstrap-3.3.7.min.css', dirname(__FILE__)));
		wp_enqueue_style('aomp_bootstrap_switch_min_css', plugins_url('assets/css/bootstrap-switch-3.3.2.min.css', dirname(__FILE__)));
		wp_enqueue_style('aomp_fontawesom_min_css', plugins_url('assets/css/font-awesome-4.7.0.min.css', dirname(__FILE__)));
				
		if (AOMP_DEBUG) {
			wp_enqueue_style('aomp_plugin_css', plugins_url('assets/css/style.css', dirname(__FILE__)));
		} else {
			wp_enqueue_style('aomp_plugin_css', plugins_url('assets/css/style.min.css', dirname(__FILE__)));
		}

		wp_enqueue_script('aomp_bootstrap_min_js', plugins_url('assets/js/bootstrap3.3.7.min.js', dirname(__FILE__)));
		wp_enqueue_script('aomp_bootstrap_switch_min_js', plugins_url('assets/js/bootstrap-switch-3.3.2.min.js', dirname(__FILE__)));
		wp_enqueue_script('aomp_maskedinput_min_js', plugins_url('assets/js/jquery.maskedinput-1.4.1.min.js', dirname(__FILE__)));

		if (AOMP_DEBUG) {
			wp_enqueue_script('aomp_plugin_js', plugins_url('assets/js/script.js', dirname(__FILE__)));
		} else {
			wp_enqueue_script('aomp_plugin_js', plugins_url('assets/js/script.min.js', dirname(__FILE__)));
		}
	}
	
	/**
	 * prepareData($array=[])
	 */
	private function prepareData($array=[])
	{
		$data = [
			'login' => $this->settings['login'],
			'passwd' => $this->settings['passwd'],
		];

		if (empty($array['template_mailing']) || empty($this->settings['from_name'])) {
			return false;
		}

		if (empty($array['order']) && !is_array($array['order'])) {
			return false;
		}	

		$stores_name = get_bloginfo();
		$replace['StoresName'] = !empty($stores_name) ? $stores_name : '';
			
		$inc = 0;
		foreach ($array['order'] as $key=>$value) {

			$replace['OrderID'] = !empty($value['order_id']) ? $value['order_id'] : '';
			$replace['OrderSum'] = (!empty($value['_order_total']) && !empty($value['_order_currency'])) ? $value['_order_total'].' '.$value['_order_currency'] : '';
			$replace['ClientName'] = !empty($value['_billing_first_name']) ? $value['_billing_first_name'] : '';
			$replace['ClientLastName'] = !empty($value['_billing_last_name']) ? $value['_billing_last_name'] : '';
			$replace['OrderStatus'] = !empty($value['status']) ? __($this->settings['status'][$value['status']], 'aomailer') : '';
		
			$replace['AddrOrderDelivery'] = '';
			if (!empty($value['_shipping_city'])) {
				$replace['AddrOrderDelivery'] .= $value['_shipping_city'].' ';
			}
			
			if (!empty($value['_shipping_address_2'])) {
				$replace['AddrOrderDelivery'] .= $value['_shipping_address_2'].' ';
			}
			
			if (!empty($value['_shipping_address_1'])) {
				$replace['AddrOrderDelivery'] .= $value['_shipping_address_1'].' ';
			}
			
			$replace['AddrPayment'] = '';
			if (!empty($value['_billing_city'])) {
				$replace['AddrPayment'] .= $value['_billing_city'].' ';
			}
			
			if (!empty($value['_billing_address_2'])) {
				$replace['AddrPayment'] .= $value['_billing_address_2'].' ';
			}
			
			if (!empty($value['_billing_address_1'])) {
				$replace['AddrPayment'] .= $value['_billing_address_1'].' ';
			}

			$replace['MethodPayment'] = !empty($value['_payment_method_title']) ? $value['_payment_method_title'] : '';

			$data['message'][$inc]['sms_text'] = AomailerSMSApi::aomp()->replaceTag($array['template_mailing'], $replace);
			$data['message'][$inc]['from_name'] = $this->settings['from_name'];
			$data['message'][$inc]['name_delivery'] = 'wordpress_sendmanualsms';

			if (!empty($array['used_translit'])) {
				$data['message'][$inc]['sms_text'] = AomailerSMSApi::aomp()->transliterate($data['message'][$inc]['sms_text']);
			}
			$data['message'][$inc]['abonents'][] = [
				'number' =>  AomailerSMSApi::aomp()->getFormat($key, 'phone'),
				'time_send' => !empty($array['date_send']) ? $array['date_send'] : '',
				'validity_period' => '',
			];
			$inc++;
		}	

		return $data;	
	}

	private function getStatusArray()
	{
		$array_status = [];
		if (!empty($_POST['data']['AomailerSmsMailing']) && is_array($_POST['data']['AomailerSmsMailing'])) {

			foreach ($_POST['data']['AomailerSmsMailing'] as $name=>$value) {

				if (preg_match('/^([a-z]{1,})+(_recipients)$/i', $name)) {

					if (AomailerValidate::validateBoolean($value)) {
						
						$status = preg_replace('/(_recipients)/i', '', $name);
						if ($status=='onhold') {
							$status = preg_replace('/^(on).*?/i', '$1-', $name);
						}
					
						$array_status[] = $status;
						$this->settings['mailing_settings'][$name] = 1;
					} else {
						$this->settings['mailing_settings'][$name] = 0;
					}	
				}
			}
		}
		
		return $array_status;
	}
	
	/**
	 * validateToken()
	 */
	private function validateToken($str='')
	{
		$pattern = '/[a-z 0-9 \%\/]/i';
		if (
			!empty($str) && 
			preg_match($pattern, $str) && 
			$str===wp_get_session_token()
		) {
			return true;
		}

		return false;
	}
	
	/**
	 * install()
	 */
	public static function install()
	{
		/* Detect WooCommerce */
		if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
			die(__('Error Install', 'aomailer'));
		}
		
		AomailerDB::aomp()->create_table();
	}
	
	/**
	 * uninstall()
	 */
	public static function uninstall()
	{
		AomailerDB::aomp()->delete_table();
	}
	
	/**
	 * deactivation()
	 */
	public static function deactivation()
	{
		AomailerDB::aomp()->trincate_table();
	}
	
	/**
	 * aomp($is_plugin_page, $className=__CLASS__)
	 */ 
	public static function aomp($is_plugin_page, $className=__CLASS__)
	{
		return new $className($is_plugin_page);
	}
}
