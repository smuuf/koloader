<?php

declare(strict_types=1);

namespace Smuuf\Koloader;

class SimpleCache {

	/** @var string Cache directory. **/
	private $directory;

	/** @var string Cache namespace **/
	private $namespace;

	/**
	 * @param string $path Cache directory path.
	 * @param string $namespace Cache namespace.
	 */
	public function __construct(string $path, ?string $namespace = null) {

		$path = rtrim($path, '\\/');
		if (!is_dir($path)) {
			throw new KoloaderException("Passed cache path '$path' is not a directory");
		}

		if (!is_writable($path)) {
			throw new KoloaderException("Cache directory '$path' not writable");
		}

		$this->directory = $path;
		$this->namespace = self::pathalize((string) $namespace);

	}

	public function load(string $key) {
		if (is_file($path = $this->getCachePath($key)) ){
			return json_decode(file_get_contents($path), true);
		}
	}

	public function save(string $key, $value) {
		return file_put_contents(
			$this->getCachePath($key),
			json_encode($value)
		);
	}

	private function getCachePath(string $key) {

		$filename = ($this->namespace ?? '') . ' ' . md5($key);
		return "{$this->directory}/{$filename}";

	}

	private static function pathalize(string $string): string {
		return preg_replace('#[^a-zA-Z0-9._-]#', '_', $string);
	}

}
