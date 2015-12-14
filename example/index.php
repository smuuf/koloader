<?php

require __DIR__ . "/../src/loader.php";

$loader = new \Smuuf\Koloader\Autoloader(__DIR__ . "/temp/");
$loader->addDirectory(__DIR__ . "/app")
	->register();

$instance = new SomeClass;
$instance->doClassStuff();
