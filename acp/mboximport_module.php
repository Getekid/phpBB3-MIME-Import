<?php
/**
 *
 * Mbox import extension for the phpBB Forum Software package.
 *
 */

namespace getekid\mboximport\acp;

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

			// TODO Add check for Mbox file in the path
			// TODO Add import function for Mbox file in the path
			trigger_error($lang->lang('ACP_MBOXIMPORT_IMPORT_SUCCESS') . adm_back_link($this->u_action));
		}
	}
}
