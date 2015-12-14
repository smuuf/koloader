<?php

class SomeClass implements ISomeSecondInterface {

	use SomeTrait;

	public function doClassStuff() {
		echo "Class ok.";
		$this->doTraitStuff();
	}

}