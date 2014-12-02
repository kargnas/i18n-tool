<?php
	namespace App\Util;

	use App\Model\StringInfoList;

	class ExcelExporter
	{
		public $fileName = "Untitled.xls";

		/** @var StringInfoList */
		public $stringInfoList;

		function __construct(StringInfoList $stringInfoList) {
			$this->stringInfoList = $stringInfoList;
		}

		/**
		 * CSV용 Escape
		 *
		 * @param $str
		 *
		 * @return mixed|string
		 */
		protected static function escape($str) {
			$isWrapped = false;

			$str = preg_replace("/\t/", "\\t", $str);

			if (strstr($str, "\n") !== false || strstr($str, "\r") !== false) {
				$str = preg_replace("/\r?\n/", "\n", $str);
				$isWrapped = true;
			}

			if (strstr($str, '"') !== false) {
				$str = str_replace('"', '""', $str);
				$isWrapped = true;
			}
			return ($isWrapped ? "\"{$str}\"" : $str);
		}

		/**
		 * 다운로드용 헤더 출력
		 */
		public function printHeader() {
			header("Content-Disposition: attachment; filename=\"" . addslashes($this->fileName) . "\"");
			header("Content-Type: application/vnd.ms-excel");
		}

		/**
		 * CSV Body 리턴
		 *
		 * @return string
		 */
		public function getBody() {
			$return = "";

			$flag = false;
			foreach ($this->stringInfoList->getJsonItems() as $row) {
				unset($row['modified']);
				if (!$flag) {
					// display field/column names as first row
					$return .= implode("\t", array_keys($row)) . "\r\n";
					$flag = true;
				}

				$strings = array();
				foreach ($row as $key => $val) {
					$strings[$key] = static::escape($val);
				}
				$return .= implode("\t", array_values($strings)) . "\r\n";
			}
			return $return;
		}
	}