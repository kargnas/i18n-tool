<?php
	/**
	 * User: kars
	 * Date: 14. 10. 22. 오전 11:24
	 */

	namespace App\Util\ExcelExporter;

	use App\Model\StringInfoGroup;
	use App\Util\ExcelExporter;

	class StringInfoGroupExporter extends ExcelExporter
	{
		/** @var StringInfoGroup */
		protected $infoGroup;

		function __construct(StringInfoGroup $infoGroup) {
			$this->infoGroup = $infoGroup;
		}

		/**
		 * CSV 본문 내용을 출력
		 *
		 * @return string
		 */
		public function getBody() {
			$return = "";

			$array = $this->infoGroup->getArrayAggregatedByKey();
			// Column Name at First Row
			$return .= "Key";
			foreach($array['languageIndexInfo'] as $info) {
				$return .= "\t{$info['language']['lang']}";
			}
			$return .= "\r\n";

			foreach($array['items'] as $item) {
				$return .= "{$item['key']}";

				foreach($item['infoList'] as $info) {
					$return .= "\t" . static::escape($info['string']);
				}
				$return .= "\r\n";
			}

			return $return;
		}
	}