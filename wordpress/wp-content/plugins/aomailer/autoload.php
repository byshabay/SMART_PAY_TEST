<?php 

function aomp_autoloader($class_name) {
	if (strpos($class_name, 'Aomailer')!==false) {
		$path = realpath(AOMP_AOMAILER_DIR) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $class_name . '.php';
		if (file_exists($path) && !class_exists($class_name)) {	
			require_once $path;
		}
	}
}

spl_autoload_register('aomp_autoloader');