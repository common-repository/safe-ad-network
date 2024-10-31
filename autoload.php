<?php
/*
 * A function for class autoloading.
 */
function safeadnetwork_autoload($classname) {
	/*
	 * Makes a filepath from $classname following naming convensions defined in PSR-4 standards.
	 */
	$file = __DIR__ . DIRECTORY_SEPARATOR . str_replace ( '\\', DIRECTORY_SEPARATOR, $classname ) . '.php';
	
	/*
	 * If the file exists,
	 */
	if (file_exists ( $file )) {
		/*
		 * Requires it once.
		 */
		require_once $file;
	}
}

/*
 * Enables class autoloading.
 */
spl_autoload_register ( 'safeadnetwork_autoload' );