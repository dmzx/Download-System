<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_sample extends \phpbb\db\migration\migration
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
			array('custom', array(array($this, 'insert_sample_data'))),
		);
	}

	public function insert_sample_data()
	{
		$sample_data = array(
			array(
				'pagination_acp' 		=> '5',
				'pagination_user' 		=> '3',
				'pagination_downloads' 	=> '25',
			),
		);
		$this->db->sql_multi_insert($this->table_prefix . 'dm_eds_config', $sample_data);
	}
}
