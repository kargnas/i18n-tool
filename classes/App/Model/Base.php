<?php
	namespace App\Model;

	class Base
	{
		protected function makeList($jsonList, $callback) {
			$list = array();
			foreach($jsonList as $json) {
				$list[] = $callback($json);
			}
			return $list;
		}
	}