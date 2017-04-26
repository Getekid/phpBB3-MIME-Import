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
	'ACP_MBOXIMPORT'								=> 'Mbox import',
	'ACP_MBOXIMPORT_IMPORT'							=> 'Import',
	'ACP_MBOXIMPORT_IMPORT_EXPLAIN'					=> 'Import an Mbox file',
	'ACP_MBOXIMPORT_PATH'							=> 'File path',

	// Messages
	'ACP_MBOXIMPORT_MIME_PARSER_CLASS_NOT_FOUND'	=> 'MIME Parser class not found',
	'ACP_MBOXIMPORT_MIME_DECODING_ERROR'			=> 'MIME message decoding error:',
	'ACP_MBOXIMPORT_POSITION'						=> 'at position',
	'ACP_MBOXIMPORT_LINE'							=> 'line',
	'ACP_MBOXIMPORT_COLUMN'							=> 'column',
	'ACP_MBOXIMPORT_MIME_ANALYSE_ERROR'				=> 'MIME message analyse error:',
	'ACP_MBOXIMPORT_IMPORT_SUCCESS'					=> 'Mbox file has been imported successfully',
));
