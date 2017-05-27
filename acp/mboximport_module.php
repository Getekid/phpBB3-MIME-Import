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
use \DOMDocument;
use \XSLTProcessor;

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
							if ($this->message_not_imported($decoded[$message]['Headers']['message-id:']))
							{
								// We need to post as ANONYMOUS user
								$user_id = $user->data['user_id'];
								$user->session_kill();
								// Parse the data from the mime message
								$post_data = $this->parse_mime_message($decoded[$message], $results);
								// Submit the post
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
		$reply_to = (isset($decoded['Headers']['in-reply-to:'])) ? $decoded['Headers']['in-reply-to:'] : '';
		$mode = ($reply_to == '' || $this->message_not_imported($reply_to)) ? 'post' : 'reply';

		// Get username
		$mail_from = (isset($analysed['From'])) ? $analysed['From'][0] : '';
		$username = (isset($mail_from)) ? ((isset($mail_from['name'])) ? $mail_from['name'] : $mail_from['address']) : '';

		// Add attachments
		if (isset($analysed['Related']))
		{
			$attachment_data = array();
			foreach ($analysed['Related'] as $attachment)
			{
				$filename = tempnam(sys_get_temp_dir(), unique_id() . '-');
				file_put_contents($filename, $attachment['Data']);
				$attachment_data[] = array(
					'attach_comment'	=> '',
					'realname'			=> $attachment['FileName'],
					'size'				=> 0,
					'type'				=> $attachment['SubType'],
					'local'				=> true,
					'local_storage'		=> $filename,
					'content_id'		=> $attachment['ContentID'],
				);
			}
			$attachment_data = $this->parse_attachments('getekid_mboximport_import', $mode,4, false, $attachment_data);

		}

		// Convert HTML in Data to BBcode
		$message = (isset($analysed['Data'])) ? $analysed['Data'] : '';
		$message_phpbb = $this->html_to_bbcode($message, $attachment_data);

		// Put together the data for the post
		$poll = $uid = $bitfield = $flags = '';
		generate_text_for_storage($message_phpbb, $uid, $bitfield, $flags, true, true);
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
			// Attachments
			'attachment_data' => (!empty($attachment_data)) ? $attachment_data : 0,
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
	 * Converts HTML code to BBcode
	 *
	 * @param string $message
	 * @param array $attachment_data
	 * @return string
	 */
	private function html_to_bbcode($message, $attachment_data)
	{
		// Remove break lines and create those defined by the html code
		$message = preg_replace("/\r|\n/","", $message);
		$message = preg_replace('/\<br\>/', "\n", $message);

		// Remove the MS Word '<o:p>' tags
		$message = preg_replace('/\<\/?o:p\>/', '', $message);

		$doc = new DOMDocument();
		$doc->loadHTML('<?xml encoding="utf-8" ?>' . $message);
		$doc->saveHTML();

		$xsl = new DOMDocument;
		$xsl->load(__DIR__ . '/' . 'html_to_bbcode.xsl');

		$proc = new XSLTProcessor;
		$proc->importStyleSheet($xsl);

		$message_phpbb = $proc->transformToXML($doc);

		if (!empty($attachment_data))
		{
			// Build the index array
			$attachment_data = array_reverse($attachment_data);
			$attachment_index = array();
			foreach ($attachment_data as $key => $attachment)
			{
				$attachment_index[$attachment['content_id']] = array(
					'key'			=> $key,
					'real_filename'	=> $attachment['real_filename'],
				);
			}
			// Replace the Content ID with the attachment index
			$message_phpbb = preg_replace_callback('#\[attachment=([a-z]{2}_[a-z0-9]{16})\](.*?)\[\/attachment\]#', function ($match) use ($attachment_index) {
				return '[attachment='.$attachment_index[$match[1]]['key'].']' . $attachment_index[$match[1]]['real_filename'] . $match[2] . '[/attachment]';
			}, $message_phpbb);
			// Remove the formatting in the attachment BBcode
			$message_phpbb = preg_replace_callback('#\[(b|i|u)\](\[attachment=[0-9]+\].*?\[\/attachment\])\[\/(b|i|u)\]#', function ($match) {
				return $match[2];
			}, $message_phpbb);
			// Clean up the whitespaces
			$message_phpbb = preg_replace("#\302\240#", ' ' , $message_phpbb);
		}

		return $message_phpbb;
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
	 * Parses the attachments to be included in the post
	 *
	 * @param string $form_name
	 * @param string $mode
	 * @param int    $forum_id
	 * @param bool   $is_message
	 * @param array  $attachment_data
	 * @return array
	 */
	public function parse_attachments($form_name, $mode, $forum_id, $is_message = false, $attachment_data)
	{
		global $phpbb_container;

		/** @var \phpbb\db\driver\driver_interface $db */
		$db = $phpbb_container->get('dbal.conn');

		/** @var \phpbb\language\language $lang */
		$lang = $phpbb_container->get('language');

		/** @var \phpbb\user $user */
		$user = $phpbb_container->get('user');

		$error = array();

		$forum_id = ($is_message) ? 0 : $forum_id;

		foreach ($attachment_data as $key => $attachment)
		{
			$attachment_is_valid = (!empty($attachment) && $attachment['realname'] !== 'none' && trim($attachment['realname']));

			if (in_array($mode, array('post', 'reply', 'quote', 'edit')) && $attachment_is_valid)
			{
				/** @var \phpbb\attachment\manager $attachment_manager */
				$attachment_manager = $phpbb_container->get('attachment.manager');
				$filedata = $attachment_manager->upload($form_name, $forum_id, $attachment['local'], $attachment['local_storage'], $is_message, $attachment);
				$error = $filedata['error'];

				if (sizeof($error))
				{
					$error_msg = '';
					foreach ($error as $error_message)
					{
						$error_msg .= '<br />' . $lang->lang($error_message); // TODO Translation doesn't work
					}
					trigger_error('"' . $attachment['realname'] . '"' . ' error: ' . $error_msg . adm_back_link($this->u_action));
				}

				if ($filedata['post_attach'] && !sizeof($error))
				{
					$sql_ary = array(
						'physical_filename'	=> $filedata['physical_filename'],
						'attach_comment'	=> $attachment['attach_comment'],
						'real_filename'		=> $filedata['real_filename'],
						'extension'			=> $filedata['extension'],
						'mimetype'			=> $filedata['mimetype'],
						'filesize'			=> $filedata['filesize'],
						'filetime'			=> $filedata['filetime'],
						'thumbnail'			=> $filedata['thumbnail'],
						'is_orphan'			=> 1,
						'in_message'		=> ($is_message) ? 1 : 0,
						'poster_id'			=> $user->data['user_id'],
						'content_id'		=> $attachment['content_id'],
					);

					$db->sql_query('INSERT INTO ' . ATTACHMENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));

					$attachment_data[$key] = array(
						'attach_id'		=> $db->sql_nextid(),
						'is_orphan'		=> 1,
						'real_filename'	=> $filedata['real_filename'],
						'attach_comment'=> $attachment['attach_comment'],
						'filesize'		=> $filedata['filesize'],
						'content_id'	=> $attachment['content_id'],
					);

					// This Variable is set to false here, because Attachments are entered into the
					// Database in two modes, one if the id_list is 0 and the second one if post_attach is true
					// Since post_attach is automatically switched to true if an Attachment got added to the filesystem,
					// but we are assigning an id of 0 here, we have to reset the post_attach variable to false.
					//
					// This is very relevant, because it could happen that the post got not submitted, but we do not
					// know this circumstance here. We could be at the posting page or we could be redirected to the entered
					// post. :)
					$filedata['post_attach'] = false;
				}
			}
		}
		return (sizeof($error)) ? $error : $attachment_data;
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
