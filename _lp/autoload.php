<?php
class Autoload
{
	public static function load($class)
	{
		$class = strtolower($class);
		if (substr($class, 0, 4) == 'core') {
			$class = substr($class, 5);
			require_once(CROOT . str_replace('_', DS, $class . '.php'));
		} else {
			require_once(AROOT . str_replace('_', DS, $class . '.php'));
		}
	}
}
spl_autoload_register(array('Autoload', 'load'));