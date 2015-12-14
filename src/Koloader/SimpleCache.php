<?php

namespace Smuuf\Koloader;

class SimpleCache implements ICache {

	/** @var string Cache directory. **/	
	private $directory;

	/** @var string Cache namespace **/
	private $namespace;

	/**
	 * @param string Directory for cache to be created in.
	 * @param string Namespace for the created cache.
	 */
	public function __construct($directory, $namespace = null) {

		$directory = rtrim($directory, '\\/');
		$this->namespace = self::pathalize($namespace);

		if (!is_writable($directory)) {
			throw new \LogicException("Directory '$directory' not writable.");
		}

		if (!is_dir($directory)) {
			throw new \LogicException("Passed path '$directory' is not a directory.");
		}

		$this->directory = realpath($directory);

	}

	public function load($key) {
		if (is_file($path = $this->getCachePath($key)) ){
			return file_get_contents($path);
		}
	}

	public function save($key, $value) {
		return file_put_contents($this->getCachePath($key), $value);
	}

	protected function getCachePath($key) {
		return $this->directory  . "/" . ($this->namespace ? $this->namespace . '_' : null) . md5($key);
	}

	protected static function pathalize($string) {
		return preg_replace('#[^a-zA-Z0-9._-]#', '_', $string);
	}

}
