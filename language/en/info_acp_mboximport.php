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
	// Import from file
	'ACP_MBOXIMPORT_IMPORT_FILE'					=> 'Import from file',
	'ACP_MBOXIMPORT_IMPORT_FILE_EXPLAIN'			=> 'Import an Mbox file',
	'ACP_MBOXIMPORT_PATH_FILE'						=> 'File path',
	'ACP_MBOXIMPORT_IMPORT_FILE_WARNING'			=> 'Make sure the is no \'From\' at the beginning of any line in the file as it will break the import. If so please use the \'Import from directory\' option instead.',
	// Import from directory
	'ACP_MBOXIMPORT_IMPORT_DIR'						=> 'Import from directory',
	'ACP_MBOXIMPORT_IMPORT_DIR_EXPLAIN'				=> 'Import multiple Mbox files from a directory',
	'ACP_MBOXIMPORT_PATH_DIR'						=> 'Directory path',
	// Default forum
	'ACP_MBOXIMPORT_SELECT_DEFAULT_FORUM'			=> 'Select default forum',
	'ACP_MBOXIMPORT_SELECT_DEFAULT_FORUM_EXPLAIN'	=> 'The default forum for new topics. Reply posts to topics already imported will use the topic\'s forum.',

	// Messages
	'ACP_MBOXIMPORT_MIME_PARSER_CLASS_NOT_FOUND'	=> 'MIME Parser class not found',
	'ACP_MBOXIMPORT_FORUM_NOT_VALID'				=> 'Forum is not valid',
	'ACP_MBOXIMPORT_NOT_FILE'						=> 'Input is not a file',
	'ACP_MBOXIMPORT_NOT_DIR'						=> 'Input is not a directory',
	'ACP_MBOXIMPORT_MIME_DECODING_ERROR'			=> 'MIME message decoding error: %s',
	'ACP_MBOXIMPORT_POSITION'						=> 'at position %s',
	'ACP_MBOXIMPORT_LINE_AND_COLUMN'				=> 'line %s column %s',
	'ACP_MBOXIMPORT_MIME_ANALYSE_ERROR'				=> 'MIME message analyse error:',
	'ACP_MBOXIMPORT_ATTACHMENT_ERROR'				=> 'Error with attachment "%s": ',
	'ACP_MBOXIMPORT_ATTACHMENT_EMPTY_ERROR'			=> 'Empty attachment found, could not be imported',
	'ACP_MBOXIMPORT_IMPORT_SUCCESS'					=> 'Mbox file has been imported successfully',
));
