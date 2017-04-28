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

		/** @var \phpbb\user $user */
		$user = $phpbb_container->get('user');

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
							$post_data = $this->parse_mime_message($decoded[$message], $results);
							// Submit the post
							if ($this->message_not_imported($post_data['message_id']))
							{
								// We need to post as ANONYMOUS user
								$user_id = $user->data['user_id'];
								$user->session_kill();
								submit_post($post_data['mode'], $post_data['subject'], $post_data['username'], POST_NORMAL, $post_data['poll'], $post_data['data']);
								$user->session_create($user_id, true);
								$this->set_message_id_from_post_id($post_data['data']['post_id'], $post_data['message_id']);
							}
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

	/**
	 * Parses the Analysed message to phpBB post components
	 *
	 * @param array $decoded
	 * @param array $analysed
	 * @return array
	 */
	private function parse_mime_message($decoded, $analysed)
	{
		// Get mode
		$reply_to = (isset($decoded['Headers']['in-reply-to:'])) ? $decoded['Headers']['in-reply-to:'] : 0;
		$mode = ($reply_to == 0 || $this->message_not_imported($reply_to)) ? 'post' : 'reply';

		// Get username
		$mail_from = (isset($analysed['From'])) ? $analysed['From'][0] : '';
		$username = (isset($mail_from)) ? ((isset($mail_from['name'])) ? $mail_from['name'] : $mail_from['address']) : '';

		// Put together the data for the post
		$message_phpbb = (isset($analysed['Data'])) ? $analysed['Data'] : ''; // TODO convert HTML code to BBcode
		$poll = $uid = $bitfield = $flags = '';
		generate_text_for_storage($message_phpbb, $uid, $bitfield, $flags);
		$data = array(
			// General Posting Settings
			'forum_id' => 4, // TODO Make it dynamic
			'topic_id' => ($mode == 'reply') ? $this->get_topic_id_from_message_id($decoded['Headers']['in-reply-to:']) : 0,
			'icon_id' => false,
			// Defining Post Options
			'enable_bbcode' => true,
			'enable_smilies' => false,
			'enable_urls' => true,
			'enable_sig' => true,
			// Message Body
			'message' => $message_phpbb,
			'message_md5' => md5($message_phpbb),
			// Values from generate_text_for_storage()
			'bbcode_bitfield' => $bitfield,
			'bbcode_uid' => $uid,
			// Other Options
			'post_edit_locked' => 0,
			'topic_title' => (isset($analysed['Subject'])) ? $analysed['Subject'] : '',
			// Email Notification Settings
			'notify_set' => false,
			'notify' => false,
			'post_time' => (isset($analysed['Date'])) ? strtotime($analysed['Date']) : '',
			'forum_name' => '',
		);

		$post_data = array(
			'mode'			=> $mode,
			'subject'		=> (isset($analysed['Subject'])) ? $analysed['Subject'] : '',
			'username'		=> $username,
			'poll'			=> $poll,
			'data'			=> $data,
			'message_id'	=> (isset($decoded['Headers']['message-id:'])) ? $decoded['Headers']['message-id:'] : '',
		);

		return $post_data;
	}

	/**
	 * Gets the topic_id of the post that has a message_id
	 *
	 * @param string $message_id
	 * @return int
	 */
	private function get_topic_id_from_message_id($message_id)
	{
		global $phpbb_container;

		/** @var \phpbb\db\driver\driver_interface $db */
		$db = $phpbb_container->get('dbal.conn');

		$sql = 'SELECT topic_id
			  FROM ' . POSTS_TABLE . " 
			  WHERE message_id = '" . $db->sql_escape($message_id) . "'";

		// Run the query
		$result = $db->sql_query($sql);

		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		return $row['topic_id'];
	}

	/**
	 * Checks if the message hasn't been imported
	 *
	 * @param string $message_id
	 * @return bool
	 */
	private function message_not_imported($message_id)
	{
		global $phpbb_container;

		/** @var \phpbb\db\driver\driver_interface $db */
		$db = $phpbb_container->get('dbal.conn');

		$sql = 'SELECT message_id
			  FROM ' . POSTS_TABLE . " 
			  WHERE message_id = '" . $db->sql_escape($message_id) . "'";

		// Run the query
		$result = $db->sql_query($sql);

		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		return !isset($row['message_id']);
	}

	/**
	 * Sets the message_id in a post
	 *
	 * @param int $post_id
	 * @param string $message_id
	 */
	private function set_message_id_from_post_id($post_id, $message_id)
	{
		global $phpbb_container;

		/** @var \phpbb\db\driver\driver_interface $db */
		$db = $phpbb_container->get('dbal.conn');

		$sql_arr = array(
			'message_id'	=> $message_id,
		);

		$sql = 'UPDATE ' . POSTS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_arr) . 'WHERE ' . $db->sql_in_set('post_id', $post_id);
		$db->sql_query($sql);
	}
}
