# Koloader

*Koloader* is a super-lightweight directory-sniffing autoloader with caching.

### How to use it?

Koloader is created with ease-of-use in mind. Really, you will need only four lines of code in your new project. The rest will be handled for you.

##### First, include the Koloader in your project

*Koloader* needs to be included first. If you're not using *Composer*, this can be done directly by including the Koloader's *loader* script, which will load everything else that is needed:

```
require __DIR__ . "/path/to/Koloader/src/loader.php";
```

##### And then use it!

```
$loader = new \Smuuf\Koloader\Autoloader(__DIR__ . "/temp/"); // The temp directory must exist beforehand.
$loader->addDirectory(__DIR__ . "/app")
	->register();

// Autoloading is enabled now!

$instance = new SomeClass; // Autoloading will be handled by the Koloader.
$instance->doClassStuff(); // Profit!
```

The Koloader must be instantiated with a path to an existing temporary directory as an argument:
- **Autoloader::__construct**(*string* $pathToTmpDir) - Specified directory will be used for storing cached maps of files that will be scanned for autoloadable tokens. **This directory will *not* be created automatically** and thus must exist beforehand.

And then you need to call only two methods on the *Koloader* instance:
- **Autoloader::addDirectory**(*string* $pathToDirectory) - Add a directory to the list of directories that will be scanned for definitions of autoloadable tokens (those good ol' **class**, **interface**, **trait** keywords)
- **Autoloader::register**() - Call this after all directories were added. This will register the Koloader and from that moment it will handle autoloading.

That is all. Have fun!
