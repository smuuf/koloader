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
$loader = new \Smuuf\Koloader\Autoloader(__DIR__ . "/temp/");
$loader->addDirectory(__DIR__ . "/app")
	->register();

// Autoloading is enabled now!

$instance = new SomeClass; // Autoloading will be handled by the Koloader.
$instance->doClassStuff(); // Profit!
```

In fact, you need to call only two methods on the *Koloader* instance:
- **addDirectory**(*string* $pathToDirectory) - Add a directory to the list of directories that will be scanned for definitions of autoloadable tokens (those good ol' **class**, **interface**, **trait** keywords)
- **register**() - Call this after all directories were added. This will register the Koloader and from that moment it will handle autoloading.

That is all. Have fun!
