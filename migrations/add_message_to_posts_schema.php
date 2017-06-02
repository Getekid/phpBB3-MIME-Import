<?php
/**
 *
 * Mbox import extension for the phpBB Forum Software package.
 *
 */

namespace getekid\mboximport\migrations;

class add_message_to_posts_schema extends \phpbb\db\migration\migration
{
	public function update_schema()
	{
		return array(
			'add_columns'        => array(
				$this->table_prefix . 'posts'        => array(
					'mime_message_id'    => array('VCHAR:255', ''),
					'mime_in_reply_to'   => array('VCHAR:255', ''),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'        => array(
				$this->table_prefix . 'posts'        => array(
					'mime_message_id',
					'mime_in_reply_to',
				),
			),
		);
	}
}