<?php
/**
 *
 * Mbox import extension for the phpBB Forum Software package.
 *
 */

namespace getekid\mboximport\acp;

class mboximport_info
{
	public function module()
	{
		return array(
			'filename'	=> '\getekid\mboximport\acp\mboximport_module',
			'title'		=> 'ACP_MBOXIMPORT',
			'modes'		=> array(
				'import'	=> array('title' => 'ACP_MBOXIMPORT_IMPORT', 'auth' => 'ext_getekid/mboximport', 'cat' => array('ACP_MBOXIMPORT')),
			),
		);
	}
}
