<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_1_0_8 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return [
			'\dmzx\downloadsystem\migrations\downloadsystem_1_0_7',
		];
	}

	public function update_data()
	{
		return [
			// Update config
			['config.update', ['download_system_version', '1.0.8']],
		];
	}
}
