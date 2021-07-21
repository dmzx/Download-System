<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_schema extends \phpbb\db\migration\migration
{
	public function update_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'dm_eds'	=> [
					'COLUMNS'	=> [
						'download_id'		=> ['UINT:8', null, 'auto_increment'],
						'download_count'	=> ['UINT:8', 0],
						'download_title'	=> ['VCHAR', ''],
						'download_filename'	=> ['VCHAR', ''],
						'download_desc'		=> ['MTEXT_UNI', ''],
						'download_version'	=> ['VCHAR:10', 0],
						'download_cat_id'	=> ['UINT:8', 0],
						'bbcode_bitfield'	=> ['VCHAR', ''],
						'bbcode_uid'		=> ['VCHAR:8', ''],
						'bbcode_options'	=> ['VCHAR:255', ''],
						'upload_time'		=> ['UINT:8', 0],
						'last_changed_time'	=> ['UINT:8', 0],
						'cost_per_dl'		=> ['DECIMAL:10', 0.00],
						'filesize'			=> ['INT:11', 0],
						'points_user_id'	=> ['INT:8', 0],
					],
					'PRIMARY_KEY'	=> 'download_id',
				],
				$this->table_prefix . 'dm_eds_cat'	=> [
					'COLUMNS'	=> [
						'cat_id'			=> ['UINT:8', null, 'auto_increment'],
						'parent_id'			=> ['UINT:8', 0],
						'left_id'			=> ['UINT:8', 0],
						'right_id'			=> ['UINT:8', 0],
						'cat_parents'		=> ['MTEXT_UNI', ''],
						'cat_name'			=> ['VCHAR', 0],
						'cat_desc'			=> ['MTEXT_UNI', 0],
						'cat_sub_dir'		=> ['VCHAR', ''],
						'cat_desc_uid'		=> ['VCHAR:8', ''],
						'cat_desc_bitfield'	=> ['VCHAR:8', 0],
						'cat_desc_options'	=> ['UINT:8', 0],
					],
					'PRIMARY_KEY'	=> 'cat_id',
				],
				$this->table_prefix . 'dm_eds_config'	=> [
					'COLUMNS'	=> [
						'pagination_acp'	=> ['TINT:3', 0],
						'pagination_user'	=> ['TINT:3', 0],
						'costs_per_dl'		=> ['DECIMAL:10', 0.00],
						'announce_enable'	=> ['TINT:1', 0],
						'announce_forum'	=> ['INT:10', 0],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return 	[
			'drop_tables' => [
				$this->table_prefix . 'dm_eds',
				$this->table_prefix . 'dm_eds_cat',
				$this->table_prefix . 'dm_eds_config',
			],
		];
	}
}
