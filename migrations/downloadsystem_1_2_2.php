<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2025 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_1_2_2 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return [
			'\dmzx\downloadsystem\migrations\downloadsystem_1_2_1',
		];
	}

	public function update_data()
	{
		return [
			['config.update', ['download_system_version', '1.2.2']],
		];
	}
}
