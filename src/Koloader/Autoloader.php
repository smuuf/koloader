<?php

namespace Smuuf\Koloader;

class Autoloader {

	/**
	 * @var array List of autoloadable tokens.
	 */
	public static $autoloadableTokens = [
		T_CLASS,
		T_INTERFACE,
		T_TRAIT,
	];

	/**
	 * @var string[] Extensions of files to scan.
	 */
	public static $scanExtensions = [
		".php",
		".inc",
	];

	/** @var array List of already included files. */
	private static $includedFiles = [];

	/** @var ICache Cache provider. */
	private $cache;

	/** @var array Directories to be scanned. */
	private $scanDirs = [];

	/** @var array List of found paths for each of the autoloadable tokens. */
	private $tokens = [];

	/**
	 * Dict of modification times for each scanned file
	 * @var array<string, int>
	 */
	private $filemtimes = [];

	/** @var bool Did Autoloader already recreate cache during current runtime? */
	private $recreated = false;

	/** @var bool True if autoloader has been registered. */
	private $registered = false;

	public function __construct(string $cacheDir) {
		$this->cache = new SimpleCache($cacheDir, __CLASS__);
	}

	public function addDirectory(string $dir) {

		if ($this->registered) {
			throw new KoloaderException("Cannot add directory to already registered autoloader");
		}

		if (!is_dir($dir)) {
			throw new KoloaderException("Directory '$dir' does not exist");
		}

		$this->scanDirs[] = $dir;
		return $this;

	}

	public function register(): void {

		if (!$this->scanDirs) {
			throw new KoloaderException("There are no directories to scan");
		}

		// Register this autoloader.
		spl_autoload_register([$this, "handleAutoload"]);
		$this->registered = true;

		// Create unique cache key for each combination of scanned dirs.
		$this->cacheKey = json_encode($this->scanDirs);

		[$tokens, $filemtimes] = $this->cache->load($this->cacheKey);

		$this->tokens = $tokens ?? [];
		$this->filemtimes = $filemtimes ?? [];

	}

	// Internals

	private function handleAutoload(string $tokenName): bool {

		// Try including cached path.
		if ($this->tryIncludeToken($tokenName)) {
			return true;
		} elseif (!$this->recreated) {

			// The token was not found in any of the
			// cached paths, so recreate the cache.
			$this->rescan();
			// Try it again.
			return $this->handleAutoload($tokenName);

		}

		// On failure...
		return false;

	}

	private function tryIncludeToken(string $tokenName): bool {

		if (isset($this->tokens[$tokenName])) {
			return self::tryIncludeFile($this->tokens[$tokenName]);
		}

		return false;
	}

	private function rescan(): void {

		// Scan source files.
		$t = microtime(true);
		[$this->tokens, $this->filemtimes] = $this->scanDirectories();

		// Save into cache.
		$this->cache->save($this->cacheKey, [$this->tokens, $this->filemtimes]);
		$this->recreated = true;

	}


	private function scanDirectories(): array {

		$tokens = [];
		$mtimes = [];
		foreach ($this->scanDirs as $dir) {

			$files = self::findAllFiles($dir, self::$scanExtensions);
			foreach ($files as $filepath) {

				$currentMtime = filemtime($filepath);
				$mtimes[$filepath] = $currentMtime;

				// If the file modification time is newer, than it was when it
				// was previously scanned, scan it again. Otherwise just use old
				// data.
				$prevMtime = $this->filemtimes[$filepath] ?? false;
				if ($prevMtime !== false && $currentMtime >= $prevMtime) {

					// Get all tokens this file contains and add them and their
					// file path to the dict of tokens.
					foreach ($this->tokens as $tok => $fp) {
						if ($fp === $filepath) {
							$tokens[$tok] = $fp;
						}
					}

					continue;

				}

				// Get PHP tokens from the specified file.
				$source = file_get_contents($filepath);

				// Get all autoloadable tokens within the file.
				if ($foundTokens = self::scanDeclarations($source)) {
					foreach ($foundTokens as $token) {
						$tokens[$token] = $filepath;
					}
				}

			}

		}

		return [$tokens, $mtimes];

	}

	private static function tryIncludeFile(string $path): bool {

		$path = realpath($path);

		// Include only if the file was not already included  and the file is readable.
		if (!isset(self::$includedFiles[$path]) && is_readable($path)) {

			include_once $path;
			self::$includedFiles[$path] = true;
			return true;

		}

		return false;

	}

	private static function findAllFiles(string $dir): \Generator {

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {

			$path = $file->getRealPath();
			if (is_file($path) && self::hasValidExtension($path)) {
				yield $path;
			}

			if (is_dir($path)) {
				yield from self::findAllFiles($path);
			}

		}

	}

	/**
	 * Go through the file and return an array of autoloadable tokens present.
	 */
	private static function scanDeclarations(string $src): ?array {

		// Defaults
		$gathered = [];
		$gatheringNamespace = false;
		$namespace = null;
		$tokens = token_get_all($src);

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

	private static function hasValidExtension(string $filename) {

		foreach (self::$scanExtensions as $ext) {
			if (substr($filename, strlen($ext) * -1) === $ext) {
				return true;
			}
		}

		return false;

	}

}

class KoloaderException extends \RuntimeException {}
