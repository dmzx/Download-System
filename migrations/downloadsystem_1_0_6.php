<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_1_0_6 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return [
			'\dmzx\downloadsystem\migrations\downloadsystem_1_0_5',
		];
	}

	public function update_data()
	{
		return [
			// Update config
			['config.update', ['download_system_version', '1.0.6']],
			// Add permission
			 ['permission.add', ['u_dm_eds_upload', true]],
			 // Set permission
			 ['permission.permission_set', ['ADMINISTRATORS', 'u_dm_eds_upload', 'group']],
		];
	}
}
