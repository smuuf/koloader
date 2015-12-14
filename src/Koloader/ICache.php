<?php

namespace Smuuf\Koloader;

interface ICache {

	public function save($key, $value);
	public function load($key);

}
