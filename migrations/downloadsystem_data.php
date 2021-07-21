<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_data extends \phpbb\db\migration\migration
{
	public function update_data()
	{
		return [
		 // Add permissions
		 ['permission.add', ['u_dm_eds_use', true]],
		 ['permission.add', ['u_dm_eds_download', true]],
		 ['permission.add', ['a_dm_eds', true]],

		 // Set permissions
		 ['permission.permission_set', ['REGISTERED', 'u_dm_eds_use', 'group']],
		 ['permission.permission_set', ['REGISTERED', 'u_dm_eds_download', 'group']],
		 ['permission.permission_set', ['ADMINISTRATORS', 'a_dm_eds', 'group']],
		 ['permission.permission_set', ['ADMINISTRATORS', 'u_dm_eds_use', 'group']],
		 ['permission.permission_set', ['ADMINISTRATORS', 'u_dm_eds_download', 'group']],
        ];
	}
}
