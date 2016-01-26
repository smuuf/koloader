<?php

namespace Smuuf\Koloader;

class Autoloader {

	/** @var ICache Cache provider. **/
	protected $cache;

	/** @var array Directories to be scanned. **/
	protected $scanDirs = array();

	/** @var array Static array for storing list of already included files. **/
	protected static $includedFiles = array();

	/** @var array Autoloadable tokens. **/
	protected static $autoloadableTokens = [
		T_CLASS,
		T_INTERFACE,
		T_TRAIT,
	];

	/** @var array Files of which extensions to scan through. **/
	protected static $scanExtensions = [
		".php",
		".inc"
	];

	/** @var array List of found paths for each of the autoloadable tokens. **/
	protected $cachedPaths = [];

	public function __construct($cache = null) {

		// User must provide ither instance of ICache
		// or a temporary directory for storing cached paths.
		if ($cache instanceof ICache) {
			$this->cache = $cache;
		} else {
			$this->cache = is_dir($cache) ? new SimpleCache($cache, __CLASS__) : null;
		}

		if (!$this->cache) {
			throw new KoloaderException("A path to temporary directory or an instance of ICache must be provided.");
		}

	}

	// Public interface

	public function addDirectory($dir) {

		if (!is_dir($dir)) {
			throw new KoloaderException("Directory '$dir' does not exist.");
		}

		$this->scanDirs[] = $dir;
		return $this;

	}

	public function register() {

		if (!$this->scanDirs) {
			throw new KoloaderException("There are no directories added.");
		}

		// Create unique cache key for each combination of scanned dirs.
		$this->cacheKey = json_encode($this->scanDirs);

		// Register this autoloader.
		spl_autoload_register([$this, "handleAutoload"]);

		// Handle generating cache for the first time, if neccessary.
		$this->handleCache();

	}

	// Internals

	protected function handleCache() {

		// Load cached paths, if possible.
		if (!$this->cachedPaths = json_decode($this->cache->load($this->cacheKey), true)) {

			// Recreate cache if there is none.
			$this->recreateCache();

		}

	}

	protected function handleAutoload($tokenName) {

		// Try including cached path.
		if ($this->tryCachedPath($tokenName)) {

			return true;

		} else {

			// The token was not found in any of the
			// cached paths, so recreate the cache.
			$this->recreateCache();

			// Try it again.
			return $this->tryCachedPath($tokenName);

		}

		// On failure...
		return false;

	}

	protected function tryCachedPath($tokenName) {

		$tokenName = strtolower($tokenName);

		if (isset($this->cachedPaths[$tokenName])) {
			return self::tryInclude($this->cachedPaths[$tokenName]);
		}

		return false;
	}

	protected function recreateCache() {

		// Scan source files.
		$this->cachedPaths = $this->scanDirectory($this->scanDirs);

		// Save into cache.
		$this->cache->save($this->cacheKey, json_encode($this->cachedPaths));

	}


	protected function scanDirectory($scanDir) {

		$items = array();

		foreach ($this->scanDirs as $dir) {

			// Find all source files in the directory.
			$allPhpFiles = self::findAllFiles($dir, self::$scanExtensions);

			foreach ($allPhpFiles as $phpFile) {

				if (is_readable($phpFile)) {

					// Get all autoloadable tokens within the file.
					if ($tokens = self::findDeclarations($phpFile)) {

						foreach ($tokens as $token) {

							// Case-insensitive, same as PHP.
							$items[strtolower($token)] = $phpFile;

						}

					}

				}

			}

		}

		return $items;

	}

	protected static function tryInclude($path) {

		$path = realpath($path);

		// Include only if the file was not already included (check against our
		// static $includedFiles property to save some include_once logic)
		// and the file is readable.
		if (!isset(self::$includedFiles[$path]) && $readable = is_readable($path)) {

			include_once $path;
			self::$includedFiles[$path] = true;

			return true;

		}

		return false;

	}

	protected static function findAllFiles($dir, array $extensions) {

		$result = array();

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
		);

		foreach($iterator as $node) {

			$nodePath = $node->getRealPath();

			if (is_file($nodePath)) {

				// Add only files with the right extension.
				foreach ($extensions as $ext) {
					if (self::hasExtension($nodePath, $ext)) {
						$result[] = $nodePath;
					}
				}

			} elseif (is_dir($nodePath)) {

				// Go through the directory...
				foreach (self::FindAllFiles($nodePath, $extensions) as $nodeName) {
					$result[] = $nodeName;
				}

			}
		}

		return $result;

	}

	/**
	 * Go through the file and return an array of autoloadable tokens present.
	 */
	private static function findDeclarations($filePath) {

		// Defaults
		$gathered = array();
		$gatheringNamespace = false;
		$namespace = null;

		// Get PHP tokens from the specified file.
		$tokens = token_get_all(file_get_contents($filePath));

		foreach ($tokens as $index => $token) {

			// Namespace detection.
			if ($gatheringNamespace) {
				if (isset($token[1])) $namespace .= $token[1];
				if ($token == ";") {
					$namespace = trim($namespace, " ;\\");
					$namespace = $namespace . '\\';
					$gatheringNamespace = false;
				}
			}

			if ($namespace === null && $token[0] === T_NAMESPACE) {
				$gatheringNamespace = true;
			}

			if (isset($tokens[$index - 2]) && in_array($tokens[$index - 2][0], self::$autoloadableTokens, true)) {
				if ($tokens[$index - 1][0] === T_WHITESPACE &&
					$token[0] === T_STRING) {
					$gathered[] = $namespace . $token[1];
				}
			}

		}

		return $gathered;

	}

	private static function hasExtension($filename, $extension) {

		// If the extension is empty, always return true.
		if (!$extension) return true;

		return substr($filename, strlen($extension) * -1) === $extension;

	}

}

class KoloaderException extends \LogicException {}
