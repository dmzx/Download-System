<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_1_0_4 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array(
			'\dmzx\downloadsystem\migrations\downloadsystem_1_0_3',
		);
	}

	public function update_data()
	{
		return array(
			// Update config
			array('config.update', array('download_system_version', '1.0.4')),
		);
	}
}
