<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\acp;

class downloadsystem_module
{
	public	$u_action;

	function main($id, $mode)
	{
		global $phpbb_container, $request, $user;

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('dmzx.downloadsystem.controller.admin.controller');

		// Requests
		$action = $request->variable('action', '');
		if ($request->is_set_post('add'))
		{
			$action = 'add';
		}

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		// Here we set the main switches to use within the ACP
		switch ($mode)
		{
			case 'config':
				$this->page_title = $user->lang['ACP_MANAGE_CONFIG'];
				$this->tpl_name = 'acp_dm_eds_config';
				$admin_controller->display_config();
			break;

			case 'categories':
				$this->page_title = $user->lang['ACP_MANAGE_CATEGORIES'];
				$this->tpl_name = 'acp_dm_eds';
				$admin_controller->display_categories();
			break;

			case 'downloads':
				$this->page_title = $user->lang['ACP_EDIT_DOWNLOADS'];
				$this->tpl_name = 'acp_dm_eds_list';

				switch ($action)
				{
					case 'new_download';
						$this->page_title = $user->lang['ACP_NEW_DOWNLOAD'];
						$this->tpl_name = 'acp_dm_eds_new';

						$admin_controller->new_download();
						return;
					break;

					case 'copy_new';
						$this->page_title = $user->lang['ACP_NEW_DOWNLOAD'];
						$this->tpl_name = 'acp_dm_eds_new_copy';

						$admin_controller->copy_new();
						return;
					break;

					case 'edit';
						$this->page_title = $user->lang['ACP_EDIT_DOWNLOADS'];
						$this->tpl_name = 'acp_dm_eds_edit';

						$admin_controller->edit();
						return;
					break;

					case 'add_new';
						$admin_controller->add_new();
						return;
					break;

					case 'update';
						$admin_controller->update();
						return;
					break;

					case 'delete';
						$admin_controller->delete();
						return;
					break;

					default:
						$admin_controller->display_downloads();
					break;
				}
			break;
		}
	}
}
