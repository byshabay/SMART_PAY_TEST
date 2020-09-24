<?php
class AomailerValidate
{
	/**
	 * validateEmail()
	 */
	public static function validateEmail($str='')
	{
		if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * validateInt()
	 */
	public static function validateInteger($int=0)
	{
		if (is_numeric($int)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * validateBoolean()
	 */
	public static function validateBoolean($boolean=false)
	{
		if (
			$boolean==='true' || 
			$boolean===true || 
			$boolean===1
		) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * validateUrl()
	 */
	public static function validateUrl($str='')
	{
		if (filter_var($str, FILTER_VALIDATE_URL)) {
			return true;
		}
		
		return false;
	}

	/**
	 * validateStr()
	 */
	public static function validatePattern($str='', $pattern='')
	{
		if (empty($str) || empty($pattern)) {
			return false;
		}

		if (preg_match($pattern, $str)) {
			return true;
		}

		return false;
	}	
}