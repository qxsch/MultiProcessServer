<?php

spl_autoload_register(function ($className) {
	$pathMap = array(
		'QXS\MultiProcessServer' => 'src',
		'QXS\Tests\MultiProcessServer' => 'tests'
	);
	foreach ($pathMap as $namespace => $directory) {
		$strLen = strlen($namespace);
		if ($strLen >= $className && substr($className, 0, $strLen) === $namespace) {
			$path = __DIR__ . DIRECTORY_SEPARATOR . $directory . substr($className, $strLen) . '.php';
			$path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
			require_once($path);
			return TRUE;
		}
	}
	return FALSE;
});
