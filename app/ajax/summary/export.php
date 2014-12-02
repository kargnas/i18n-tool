<?
	/**
	 * 파일 Download
	 */

	include "../../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = (object) $_GET;
	$type    = $request->type;
	$export  = $request->export;

	$group = \App\Model\StringInfoGroup::ByType($type);
	$group->exportExcel();