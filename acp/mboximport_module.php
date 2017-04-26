<?php
/**
 *
 * Mbox import extension for the phpBB Forum Software package.
 *
 */

namespace getekid\mboximport\acp;

if (!defined('IN_PHPBB'))
{
	exit;
}

// Load the Mime Parser library
@include_once('mimeparser/rfc822_addresses.php');
@include_once('mimeparser/mime_parser.php');
use mime_parser_class;

class mboximport_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	public function main($id, $mode)
	{
		global $phpbb_container;

		/** @var \phpbb\language\language $lang */
		$lang = $phpbb_container->get('language');

		/** @var \phpbb\request\request $request */
		$request = $phpbb_container->get('request');

		/** @var \phpbb\template\template $template */
		$template = $phpbb_container->get('template');

		// Load a template from adm/style for our ACP page
		$this->tpl_name = 'mboximport_import';

		// Set the page title for our ACP page
		$this->page_title = $lang->lang('ACP_MBOXIMPORT_IMPORT');

		add_form_key('getekid_mboximport_import');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('getekid_mboximport_import'))
			{
				trigger_error('FORM_INVALID');
			}
			if (!class_exists('mime_parser_class'))
			{
				trigger_error($lang->lang('ACP_MBOXIMPORT_MIME_PARSER_CLASS_NOT_FOUND') . adm_back_link($this->u_action));
			}

			/*
			 * Using the example from test_message_decoder.php,v 1.13 2012/04/11 09:28:19 mlemos Exp $
			 */
			$message_file = ($request->variable('mboximport_path', ''));
			$mime = new mime_parser_class;

			/*
			 * Set to 0 for parsing a single message file
			 * Set to 1 for parsing multiple messages in a single file in the mbox format
			 */
			$mime->mbox = 1;

			/*
			 * Set to 0 for not decoding the message bodies
			 */
			$mime->decode_bodies = 1;

			/*
			 * Set to 0 to make syntax errors make the decoding fail
			 */
			$mime->ignore_syntax_errors = 1;

			/*
			 * Set to 0 to avoid keeping track of the lines of the message data
			 */
			$mime->track_lines = 1;

			/*
			 * Set to 1 to make message parts be saved with original file names
			 * when the SaveBody parameter is used.
			 */
			$mime->use_part_file_names = 0;

			/*
			 * Set this variable with entries that define MIME types not yet
			 * recognized by the Analyze class function.
			 */
			$mime->custom_mime_types = array(
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>array(
					'Type' => 'ms-word',
					'Description' => 'Word processing document in Microsoft Office OpenXML format'
				)
			);

			$parameters=array(
				'File'=>$message_file,
				'SkipBody'=>0,
			);

			if (!$mime->Decode($parameters, $decoded))
			{
				$error_msg = $lang->lang('ACP_MBOXIMPORT_MIME_DECODING_ERROR') . ' ' . $mime->error . ' ' . $lang->lang('ACP_MBOXIMPORT_POSITION') . ' ' . $mime->error_position;
				if ($mime->track_lines && $mime->GetPositionLine($mime->error_position, $line, $column))
				{
					$error_msg .= ' '. $lang->lang('ACP_MBOXIMPORT_LINE') . $line . ' ' . $lang->lang('ACP_MBOXIMPORT_COLUMN') . $column;
				}
				trigger_error($error_msg . adm_back_link($this->u_action));
			}
			else
			{
				for ($message = 0; $message < count($decoded); $message++)
				{
					if ($mime->decode_bodies)
					{
						if ($mime->Analyze($decoded[$message], $results))
						{
							// TODO Add import function for Mbox file in the path
						}
						else
						{
							trigger_error($lang->lang('ACP_MBOXIMPORT_MIME_ANALYSE_ERROR') . ' ' . $mime->error . adm_back_link($this->u_action));
						}
					}
				}
				for ($warning = 0, Reset($mime->warnings); $warning < count($mime->warnings); Next($mime->warnings), $warning++)
				{
					$w = Key($mime->warnings);
					$error_msg = ($lang->lang('WARNING')) . ': ' . $mime->warnings[$w] . ' ' . $lang->lang('ACP_MBOXIMPORT_POSITION') . ' ' . $w;
					if ($mime->track_lines && $mime->GetPositionLine($w, $line, $column))
						$error_msg .= ' '. $lang->lang('ACP_MBOXIMPORT_LINE') . $line . ' ' . $lang->lang('ACP_MBOXIMPORT_COLUMN') . $column;
					trigger_error($error_msg . adm_back_link($this->u_action));
				}
			}

			// TODO Add message with the number of messages imported
			trigger_error($lang->lang('ACP_MBOXIMPORT_IMPORT_SUCCESS') . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'S_MIME_PARSER_CLASS'	=> class_exists('mime_parser_class'),
			'U_ACTION'          	=> $this->u_action,
		));
	}
}
