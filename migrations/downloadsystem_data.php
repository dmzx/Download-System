<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_data extends \phpbb\db\migration\migration
{
	public function update_data()
	{
		return array(
		 // Add permissions
		 array('permission.add', array('u_dm_eds_use', true)),
		 array('permission.add', array('u_dm_eds_download', true)),
		 array('permission.add', array('a_dm_eds', true)),

		 // Set permissions
		 array('permission.permission_set', array('REGISTERED', 'u_dm_eds_use', 'group')),
		 array('permission.permission_set', array('REGISTERED', 'u_dm_eds_download', 'group')),
		 array('permission.permission_set', array('ADMINISTRATORS', 'a_dm_eds', 'group')),
		 array('permission.permission_set', array('ADMINISTRATORS', 'u_dm_eds_use', 'group')),
		 array('permission.permission_set', array('ADMINISTRATORS', 'u_dm_eds_download', 'group')),
		);
	}
}
