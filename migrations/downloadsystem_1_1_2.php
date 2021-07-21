<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2019 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_1_1_2 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return [
			'\dmzx\downloadsystem\migrations\downloadsystem_1_1_1',
        ];
	}

	public function update_data()
	{
		return [
			// Update config
			['config.update', ['download_system_version', '1.1.2']],
        ];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'dm_eds_config' => [
					'show_donation'		=> ['TINT:1', 0],
					'donation_url'		=> ['VCHAR:255', ''],
                ],
            ],
        ];
	}
}
