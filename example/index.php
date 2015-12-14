<?php

// Direct Koloader loader
// require __DIR__ . "/../src/loader.php";

// Use Composer's autoload
require __DIR__ . "/../vendor/autoload.php";

$loader = new \Smuuf\Koloader\Autoloader(__DIR__ . "/temp/");
$loader->addDirectory(__DIR__ . "/app")
	->register();

$instance = new SomeClass;
$instance->doClassStuff();
