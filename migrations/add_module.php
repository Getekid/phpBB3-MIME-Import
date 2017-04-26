<?php
/**
 *
 * Mbox import extension for the phpBB Forum Software package.
 *
 */

namespace getekid\mboximport\migrations;

class add_module extends \phpbb\db\migration\migration
{
	/**
	 * Add or update data in the database
	 *
	 * @return array Array of table data
	 * @access public
	 */
	public function update_data()
	{
		return array(
			array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_MBOXIMPORT')),
			array('module.add', array(
				'acp', 'ACP_MBOXIMPORT', array(
					'module_basename'	=> '\getekid\mboximport\acp\mboximport_module',
					'modes'				=> array('import'),
				),
			)),
		);
	}
}