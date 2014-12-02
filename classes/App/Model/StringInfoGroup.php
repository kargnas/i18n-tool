<?php
	namespace App\Model;

	// Wrapping class of StringInfoList
	use App\Exception\UnknownExportTypeException;
	use App\LocalizationDictionary\StringDictionary;
	use App\Util\ExcelExporter;

	class StringInfoGroup extends Base
	{
		/** @var array */
		protected $languages;

		/** @var String */
		public $type;

		/** @var StringInfoList[] */
		public $list;

		function __construct(array $json) {
			$this->languages = $json['languages'];
			$this->type      = $json['type'];
			$this->list      = $this->makeList($json['list'], function ($json) {
				return new StringInfoList($json);
			});
		}

		protected static function getStringsInfoListByLocaleAndType($locale, $type) {
			return \App\Model\StringInfoList::ByLocaleAndType($locale, $type);
		}

		/**
		 * @param $type
		 *
		 * @return array
		 *
		 * @throws UnknownExportTypeException
		 */
		protected static function getLanguages($type) {
			switch ($type) {
				case 'iphone':
					$languages = \App\Source\Git\iOS::getLanguages();
					return $languages;

				case 'android':
					$languages = \App\Source\Git\Android::getLanguages();
					return $languages;

				case 'server':
					$languages = \App\Source\Git\Server::getLanguages();
					return $languages;

				default:
					throw new UnknownExportTypeException("`{$type}`는 알 수 없는 타입입니다.");
			}
		}

		static function ByType($type) {
			$languages = static::getLanguages($type);

			$json = array(
				'type'      => $type,
				'languages' => $languages,
				'list'      => array()
			);
			foreach ($languages as $language) {
				$json['list'][] = static::getStringsInfoListByLocaleAndType($language['code'], $type)->getJson();
			}
			return new static($json);
		}

		protected function getLanguageIndexInfo() {
			$order = array();
			foreach ($this->languages as $index => $language) {
				$order[] = array(
					'language' => $language,
					'index'    => $index
				);
			}
			return $order;
		}

		protected function getIndexByLanguage() {
			$info  = $this->getLanguageIndexInfo();
			$order = array();
			foreach ($info as $item) {
				$order[$item['language']['code']] = $item['index'];
			}
			return $order;
		}

		protected function getExcelExporter() {
			$exporter           = new ExcelExporter\StringInfoGroupExporter($this);
			$exporter->fileName = "[summary] {$this->type}.xls";
			return $exporter;
		}

		public function getArrayAggregatedByKey() {
			$languageIndexInfo = $this->getLanguageIndexInfo();
			$indexByLanguage   = $this->getIndexByLanguage();

			$listByKey = array();
			foreach ($this->list as $infoList) {
				foreach ($infoList->items as $info) {
					$listByKey[$info->key->toString()][$infoList->locale] = $info;
				}
			}

			$json = array(
				'languageIndexInfo' => $languageIndexInfo,
				'items'             => array()
			);
			foreach ($listByKey as $key => $itemsByLocale) {
				$infoList = array();
				/**
				 * @var $itemsByLocale StringInfo[]
				 */
				foreach ($itemsByLocale as $locale => $info) {
					$infoList[$indexByLanguage[$locale]] = $info->getJson();
				}
				$json['items'][] = array(
					'key'      => $key,
					'modified' => $infoList[0]['modified'],
					'infoList' => $infoList
				);
			}

			return $json;
		}

		public function exportExcel() {
			$exporter = $this->getExcelExporter();
			$exporter->printHeader();

			echo $exporter->getBody();
		}
	}