<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_schema extends \phpbb\db\migration\migration
{
	public function update_schema()
	{
		return array(
			'add_tables'	=> array(
				$this->table_prefix . 'dm_eds'	=> array(
					'COLUMNS'	=> array(
						'download_id'		=> array('UINT:8', null, 'auto_increment'),
						'download_count'	=> array('UINT:8', 0),
						'download_title'	=> array('VCHAR', ''),
						'download_filename'	=> array('VCHAR', ''),
						'download_desc'		=> array('MTEXT_UNI',	''),
						'download_version'	=> array('VCHAR:10', 0),
						'download_cat_id'	=> array('UINT:8', 0),
						'bbcode_bitfield'	=> array('VCHAR', ''),
						'bbcode_uid'		=> array('VCHAR:8', ''),
						'bbcode_options'	=> array('VCHAR:255', ''),
						'upload_time'		=> array('UINT:8', 0),
						'last_changed_time'	=> array('UINT:8', 0),
						'cost_per_dl'		=> array('DECIMAL:10', 0.00),
						'filesize'			=> array('INT:11', 0),
						'points_user_id'	=> array('INT:8', 0),
					),
					'PRIMARY_KEY'	=> 'download_id',
				),
				$this->table_prefix . 'dm_eds_cat'	=> array(
					'COLUMNS'	=> array(
						'cat_id'			=> array('UINT:8', null, 'auto_increment'),
						'parent_id'			=> array('UINT:8', 0),
						'left_id'			=> array('UINT:8', 0),
						'right_id'			=> array('UINT:8', 0),
						'cat_parents'		=> array('MTEXT_UNI',	''),
						'cat_name'			=> array('VCHAR', 0),
						'cat_desc'			=> array('MTEXT_UNI', 0),
						'cat_sub_dir'		=> array('VCHAR', ''),
						'cat_desc_uid'		=> array('VCHAR:8', ''),
						'cat_desc_bitfield'	=> array('VCHAR:8', 0),
						'cat_desc_options'	=> array('UINT:8', 0),
					),
					'PRIMARY_KEY'	=> 'cat_id',
				),
				$this->table_prefix . 'dm_eds_config'	=> array(
					'COLUMNS'	=> array(
						'pagination_acp'	=> array('TINT:3', 0),
						'pagination_user'	=> array('TINT:3', 0),
						'costs_per_dl'		=> array('DECIMAL:10', 0.00),
						'announce_enable'	=> array('TINT:1', 0),
						'announce_forum'	=> array('INT:10', 0),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return 	array(
			'drop_tables' => array(
				$this->table_prefix . 'dm_eds',
				$this->table_prefix . 'dm_eds_cat',
				$this->table_prefix . 'dm_eds_config',
			),
		);
	}
}
