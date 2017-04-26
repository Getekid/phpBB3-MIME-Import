<?php
/**
 *
 * Mbox import extension for the phpBB Forum Software package.
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACP_MBOXIMPORT'				=> 'Mbox import',
	'ACP_MBOXIMPORT_IMPORT'			=> 'Import',
	'ACP_MBOXIMPORT_IMPORT_EXPLAIN'	=> 'Import an Mbox file',
	'ACP_MBOXIMPORT_PATH'			=> 'File path',
	'ACP_MBOXIMPORT_IMPORT_SUCCESS'	=> 'Mbox file has been imported successfully',
));
