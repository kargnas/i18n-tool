<?
	/**
	 * 파일 Download
	 */

	include "../../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = json_decode(file_get_contents("php://input"));
	$type    = $request->type;
	$key     = $request->key;
	$locale  = $request->locale;

	$dataStore = new \App\DataStore();
	$keyInfo   = $dataStore->getKey($type, $key);

	$dataStore->setKey($type, $key, !$keyInfo['modified']);

	$stringInfoList = \App\Model\StringInfoList::ByLocaleAndTypeAndKey($locale, $type, $key);

	echo json_encode(array(
		                 'success' => true,
		                 'info'     => $stringInfoList->items[0]->getJson()
	                 ));