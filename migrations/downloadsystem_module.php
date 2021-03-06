<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_module extends \phpbb\db\migration\migration
{
	public function update_data()
	{
		return [
			['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_DM_EDS']],
			['module.add', [
			'acp', 'ACP_DM_EDS', [
					'module_basename'	=> '\dmzx\downloadsystem\acp\downloadsystem_module', 'modes' => ['config', 'categories', 'downloads'],
				],
			]],
		];
	}
}
