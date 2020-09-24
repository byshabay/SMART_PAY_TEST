<?php
class AomailerDB
{
	/**
	 * Settings attributes
	 */
	public $login;
	public $passwd;
	public $admin_email_used;
	public $from;
	public $email_from_name;
	public $reply_to;
	public $reply_to_name;
	public $from_name;
			
	/**
	 * Events attributes
	 */
	public $admin_event_used;
	public $admin_event_new_order;
	public $admin_event_payment_order;
	public $admin_event_change_order_3;
	public $admin_event_change_order_4;
	public $admin_event_change_order_5;
	public $admin_event_change_order_6;
	public $admin_event_change_order_7;
	public $admin_event_change_order_8;
	public $client_event_used;
	public $client_event_new_order;
	public $client_event_payment_order;
	public $client_event_change_order_3;
	public $client_event_change_order_4;
	public $client_event_change_order_5;
	public $client_event_change_order_6;
	public $client_event_change_order_7;
	public $client_event_change_order_8;
	
    /**
     * Templates attributes
     */	 
	public $admin_events_type;
	public $admin_from_name;
	public $admin_text_sms;
	public $admin_number;
	public $admin_used_translit;
	public $client_events_type;
	public $client_from_name;
	public $client_text_sms;
	public $client_used_translit;
	
	/**
     * tableName()
     */
    public function tableName()
    {
        return 'aomailer_settings';
    }
	
	/**
	 * rules() 
	 */
	public function rules() 
	{
		return [
			['admin_email_used, 
				admin_event_used, 
				admin_event_new_order, 
				admin_event_payment_order, 
				admin_event_change_order_3, 
				admin_event_change_order_4, 
				admin_event_change_order_5, 
				admin_event_change_order_6, 
				admin_event_change_order_7, 
				admin_event_change_order_8, 
				client_event_used, 
				client_event_new_order, 
				client_event_payment_order, 
				client_event_change_order_3, 
				client_event_change_order_4, 
				client_event_change_order_5, 
				client_event_change_order_6, 
				client_event_change_order_7, 
				client_event_change_order_8,
				admin_used_translit,
				client_used_translit',
				'Boolean'],
			['from, reply_to', 'Email'],
			['admin_events_type, client_events_type', 'Integer'],
			['login, passwd, email_from_name, reply_to_name, from_name, admin_from_name, client_from_name', 'Pattern', '/[a-z 0-9 \%\/]/i'],
			['admin_text_sms, client_text_sms', 'function', 'cleaningTextSms'],
			['admin_number', 'function', 'cleaningNumberArray'],
		];
	}
	
	/**
	 * attributes($array=[]) 
	 */
	public function attributes($array=[]) 
	{
		if (!empty($array)) {
			
			$rules = self::rules();
			if (empty($rules) || !is_array($rules)) {
				return false;
			}

			foreach ($array as $key=>$value) {
				
				if (empty($key) || !is_string($key) || !property_exists($this, $key)) {
					continue;
				}

				foreach ($rules as $rule) {

					if (empty($rule[0])) {
						continue;
					}

					$rule[0] = preg_replace('/[\s\r\n]/i', '', $rule[0]);
					$properties = explode(',', $rule[0]);
					
					if (
						empty($properties) || 
						!is_array($properties) || 
						empty($rule[1]) ||
						!is_string($rule[1])
					) {
						continue;
					}

					if (in_array($key, $properties)) {

						if (
							$rule[1]==='function' && 
							!empty($rule[2]) && 
							is_string($rule[2]) && 
							method_exists($this, $rule[2])
						) {
									
							$method = $rule[2];
							$this->$key = $this->$method($value);
		
						} elseif (method_exists('AomailerValidate', 'validate'.$rule[1])) {
			
							$method = 'validate'.$rule[1];
							$param = '';
							if (!empty($rule[2]) && is_string($rule[2])) {
								$param = $rule[2];
							}
							
							if (AomailerValidate::$method($value, $param)) {

								$this->$key = $value;

							} else {
									
								$this->$key = false;
									
							}					
						}	

						break;
					}
				}
			}
		}
	}
	
	/**
	 * create_table()
	 */
	public function create_table()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . $this->tableName();

		if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		   
			$sql = "
				CREATE TABLE IF NOT EXISTS ".$table_name." (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`param_name` VARCHAR(255) NOT NULL,
					`param_value` LONGTEXT NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY (`param_name`)
				)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
			";
		   
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta($sql);
		}
	}
	
	/**
	 * delete_table()
	 */
	public function delete_table()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . $this->tableName();

		if ($wpdb->get_var( "show tables like '$table_name'") == $table_name) {
			$result = $wpdb->query('DROP TABLE IF EXISTS '.$table_name);
			if (!empty($result)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * add_data()
	 */
	public function add_data()
	{
		foreach ($this as $key => $value) {
			
			if (!isset($value)) {
				continue;
			}

			$option = self::select_option(['param_name'=>$key]);
			if (!empty($option)) {
				if (!self::update_table(['param_name'=>$key, 'param_value'=>$value], ['param_name'=>$key])) {
					return false;
				}
			} else {
				if (!self::insert_table(['param_name'=>$key, 'param_value'=>$value])) {
					return false;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * add_serialize_data()
	 */
	public function add_serialize_data()
	{
		$settings = self::loadSettings('db');

		if (isset($this->admin_events_type)) {
			
			$data_admin = [];
			if (!empty($settings['admin_template_settings'])) {
				$data_admin = $settings['admin_template_settings'];
			}

			if (isset($this->admin_from_name)) {
				$data_admin[$this->admin_events_type]['admin_from_name'] = $this->admin_from_name;
			}
			
			if (isset($this->admin_text_sms)) {
				$data_admin[$this->admin_events_type]['admin_text_sms'] = $this->admin_text_sms;
			}
			
			if (isset($this->admin_number) && is_array($this->admin_number)) {
				$data_admin[$this->admin_events_type]['admin_number'] = $this->admin_number;
			}
			
			if (isset($this->admin_used_translit)) {
				$data_admin[$this->admin_events_type]['admin_used_translit'] = $this->admin_used_translit;
			}
			
			foreach ($data_admin as $key=>$value) {
				if (!empty($value['admin_number']) && is_array($value['admin_number'])) {
					$data_admin[$key]['admin_number'] = serialize($value['admin_number']);
				}
			}

			$option = self::select_option(['param_name'=>'admin_template_settings']);
			if (!empty($option)) {
				if (!self::update_table(['param_name'=>'admin_template_settings', 'param_value'=>serialize($data_admin)], ['param_name'=>'admin_template_settings'])) {
					return false;
				}
			} else {
				if (!self::insert_table(['param_name'=>'admin_template_settings', 'param_value'=>serialize($data_admin)])) {
					return false;
				}
			}			
		}
		
		if (isset($this->client_events_type)) {
			
			$data_client = [];
			if (!empty($settings['client_template_settings'])) {
				$data_client = $settings['client_template_settings'];
			}
			
			if (isset($this->client_from_name)) {
				$data_client[$this->client_events_type]['client_from_name'] = $this->client_from_name;
			}
			
			if (isset($this->client_text_sms)) {
				$data_client[$this->client_events_type]['client_text_sms'] = $this->client_text_sms;
			}

			if (isset($this->client_used_translit)) {
				$data_client[$this->client_events_type]['client_used_translit'] = $this->client_used_translit;
			}

			$option = self::select_option(['param_name'=>'client_template_settings']);
			if (!empty($option)) {
				if (!self::update_table(['param_name'=>'client_template_settings', 'param_value'=>serialize($data_client)], ['param_name'=>'client_template_settings'])) {
					return false;
				}
			} else {
				if (!self::insert_table(['param_name'=>'client_template_settings', 'param_value'=>serialize($data_client)])) {
					return false;
				}
			}
		}
		
		if (file_exists(dirname(__FILE__).'/log.log')) {
			unlink(dirname(__FILE__).'/log.log');
		}
		
		return true;
	}
	
	/**
	 * update_table()
	 */
	public function update_table($data=[], $condition=[])
	{
		if (empty($data) || !is_array($data)) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . $this->tableName();
		
		foreach ($data as $key=>$value) {
			if ($value==='false') {
				$value = 0;
			}
			
			if ($value==='true') {
				$value = 1;
			}

			if (is_string($value)) {
				$params[$key] = stripslashes_deep($value);
			} else {
				$params[$key] = $value;
			}
		}

		$wpdb->update($table_name, $params, $condition);
		
		if ($wpdb->last_error) {
			return false;  
		} else {
			return true;
		}
	}
	
	/**
	 * insert_table()
	 */
	public function insert_table($data=[])
	{
		if (empty($data) || !is_array($data)) {
			return false;
		}
		
		global $wpdb;
		$table_name = $wpdb->prefix . $this->tableName();

		foreach ($data as $key=>$value) {
			if ($value==='false') {
				$value = 0;
			}
			
			if ($value==='true') {
				$value = 1;
			}
			
			if (is_string($value)) {
				$params[$key] = stripslashes_deep($value);
			} else {
				$params[$key] = $value;
			}
		}
			
		$wpdb->insert($table_name, $params);
		
		if ($wpdb->last_error) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * trincate_table()
	 */
	public function trincate_table()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . $this->tableName();
		
		if ($wpdb->get_var( "show tables like '$table_name'") == $table_name) {
			$result = $wpdb->query('TRUNCATE TABLE '.$table_name);
			if (!empty($result)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * select_all()
	 */
	public function select_all()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . $this->tableName();
		
		if ($wpdb->get_var( "show tables like '$table_name'") == $table_name) {
			$result = $wpdb->get_results('SELECT * FROM '.$table_name);
			if (!empty($result)) {
				return $result;
			}
		}
		
		return false;
	}
	
	/**
	 * select_option()
	 */
	public function select_option($condition=[])
	{
		if (empty($condition)) {
			return self::select_all();
		}

		global $wpdb;
		$table_name = $wpdb->prefix . $this->tableName();
		
		if ($wpdb->get_var( "show tables like '$table_name'") == $table_name) {
			
			$i=0;
			$sql = 'SELECT * FROM `'.$table_name.'` WHERE ';
			foreach ($condition as $key=>$value) {
				if (empty($i)) {
					$sql .= '
						'.$key.' = "'.$value.'"
					';
				} else {
					$sql .= '
						AND '.$key.' = "'.$value.'"
					';
				}
				$i++;
			}

			$result = $wpdb->get_results($sql);
			if (!empty($result)) {
				return $result;
			}
		}
		
		return false;
	}
	
	/**
	 * getAllBuyUsers($status=[])
	 */
	public function getAllBuyUsers($status=[])
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'posts';
		
		if ($wpdb->get_var( "show tables like '$table_name'") == $table_name) {

			$sql = '
				SELECT 
					MAX(`ID`) as `order_id`,
					`post_author` as `user_id`,
					`post_status` as `status`
				FROM '.$wpdb->prefix . 'posts
				WHERE `post_type` = "shop_order" 
			';
			
			if (!empty($status)) {
				foreach ($status as $value) {
					$implode .= '"wc-'.$value.'",';
				}

				$sql .= '
				    AND `post_status` IN ('.trim($implode, ',').')
				';
			}
			
			$sql .= '
				GROUP BY `post_author`
				ORDER BY `ID` DESC
			';

			$posts = $wpdb->get_results($sql);	
			$query = [];
			if (!empty($posts) && is_array($posts)) {
				foreach ($posts as $id) {
					$data['query'][] = $id->order_id;
					$data['value'][$id->order_id] = [
						'user_id'=>$id->user_id,
						'order_id'=>$id->order_id,
						'status'=>preg_replace('/(wc-)/i', '', $id->status),
					];
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * getDataUsers($query=[])
	 */
	public function getDataUsers($status=[])
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'postmeta';
		
		$meta = [];
		$data = [];
		
		if ($wpdb->get_var( "show tables like '$table_name'") == $table_name) {

			$data = self::aomp()->getAllBuyUsers($status);
			if (is_array($data['query'])) {
				$sql = 'SELECT * FROM '.$wpdb->prefix . 'postmeta WHERE `post_id` IN ('.implode(',', $data['query']).')';
				$meta = $wpdb->get_results($sql);	
			}
		}
		
		if (!empty($meta) && is_array($meta)) {
			foreach ($meta as $value) {
				$data['value'][$value->post_id][$value->meta_key] = $value->meta_value;
			}
		}
		
		return $data;
	}
	
	/**
	 * loadSettings()
	 */
	public function loadSettings($type='sms')
	{
		$config_path = realpath(AOMP_AOMAILER_DIR) . '/config.php';
		if (file_exists($config_path)) {
			$settings= require($config_path);
		}
		
		$settings['login'] = '';
		$settings['passwd'] = '';
		$settings['id_admin'] = 1;
		$settings['id_client'] = 1;
		$settings['logo'] = AOMP_AOMAILER_URL . '/assets/img/logo.png';

		$db_data = self::aomp()->select_all();
		if (!empty($db_data)) {
			foreach ($db_data as $obj) {
				if ($obj->param_name=='admin_template_settings' || $obj->param_name=='client_template_settings') {
					$settings[$obj->param_name] = @unserialize($obj->param_value);
					if (!empty($settings[$obj->param_name]) && is_array($settings[$obj->param_name])) {
						foreach ($settings[$obj->param_name] as $key=>$value) {
							if (!empty($value) && is_array($value)) {
								foreach ($value as $name=>$param) {
									if ($name=='admin_number') {
										if (is_array($param)) {
											$settings[$obj->param_name][$key][$name] = $param;
										} else {
											$settings[$obj->param_name][$key][$name] = @unserialize((string) $param);
										}
									}
								}
							}
						}
					}
				} else {
					$settings[$obj->param_name] = $obj->param_value;
				}
			}
		}

		if (!empty($settings['login']) && !empty($settings['passwd']) && $type=='sms') {

			$balance = AomailerSMSApi::aomp()->getBalance($settings['login'], $settings['passwd']);
			if (empty($balance['error'])) {
				$settings['balance'] = $balance['balance'];
				$settings['connect'] = true;
				$settings['currency'] = $balance['currency'];
			} else {
				$settings['error'] = self::addError($balance['error']);
			}

			$from_name = AomailerSMSApi::aomp()->getFromName($settings['login'], $settings['passwd']);
			if (empty($from_name['error'])) {
				$settings['array_from_name'] = $from_name['from_name'];
			} else {
				$settings['error'] = self::addError($from_name['error']);
			}
			
			/*
			$templates = AomailerSMSApi::aomp()->getTemplates($settings['login'], $settings['passwd']);
			if (empty($templates['error'])) {
				$settings['array_templates'] = $templates['templates'];
			} else {
				$settings['error'] = self::addError($templates['error']);
			}
			*/
		}
		
		return $settings;
	}
	
	/**
	 * addError($error)
	 */
	private function addError($error='')
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
	 * cleaningTextSms()
	 */
	private function cleaningTextSms($str='')
	{
		if (empty($str) || !is_string($str)) {
			return '';
		}

		$str = strip_tags($str);
		$str = htmlspecialchars($str);
		$str = htmlentities($str);
		
		return $str;
	}
	
	/**
	 * cleaningNumberArray($array=[])
	 */
	private function cleaningNumberArray($array=[])
	{
		$data = [];
		if (empty($array) || !is_array($array)) {
			return $data;
		}
		
		foreach ($array as $number) {
			if (empty($number)) {
				continue;
			}
				
			if (preg_match('/^[0-9 \+\-\(\)]{1,}$/i', $number)) {
				
				$number = preg_replace('/[\+\-\(\)\s]/i', '', $number);
				if (strlen($number)>15 || strlen($number)<9) {
					continue;
				}

			} elseif (preg_match('/^[0-9a-z \+\-\(\)]{1,}$/i', $number)) {	
				
				$number = preg_replace('/[\+\-\(\)\s]/i', '', $number);
				if (strlen($number)>11 || strlen($number)<5) {
					continue;
				}
				
			} else {
				continue;
			}
	
			$data[] = $number;
		}


		return $data;
	}

	/**
	 * aomp($className=__CLASS__)
	 */ 
	public static function aomp($className=__CLASS__)
	{
		return new $className;
	}
}