<?php
	/**
	 * User: kars
	 * Date: 2014. 9. 30. 오후 6:54
	 */

	namespace App\Model;

	class Key
	{
		public $key;

		function __construct($key) {
			$this->key = $key;
		}

		public function __toString() {
			return $this->toString();
		}

		public function toString() {
			return $this->key;
		}

		public function clearByType($type) {
			$store = new \App\DataStore();
			$store->deleteAllKey($type, $this->key);
		}
	}