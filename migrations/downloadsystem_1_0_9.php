<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2019 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\migrations;

class downloadsystem_1_0_9 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return [
			'\dmzx\downloadsystem\migrations\downloadsystem_1_0_8',
		];
	}

	public function update_data()
	{
		return [
			['config.update', ['download_system_version', '1.0.9']],
			['custom', [[$this, 'dm_eds_image_dir']]],
		];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'dm_eds_config' => [
					'dm_eds_image_dir'			=> ['VCHAR', 'images/downloadsystem'],
					'dm_eds_image_cat_dir'		=> ['VCHAR', 'images/downloadsystem/categories'],
					'dm_eds_image_size'			=> ['VCHAR', '15'],
					'dm_eds_allow_bbcodes'		=> ['BOOL', 1],
					'dm_eds_allow_smilies'		=> ['BOOL', 1],
					'dm_eds_allow_magic_url'	=> ['BOOL', 1],
					'dm_eds_allow_dl_img'		=> ['BOOL', 1],
					'dm_eds_allow_cat_img'		=> ['BOOL', 1],
				],
				$this->table_prefix . 'dm_eds_cat' => [
					'category_image'			=> ['VCHAR:255', ''],
					'enable_bbcode'				=> ['BOOL', 1],
					'enable_smilies'			=> ['BOOL', 1],
					'enable_magic_url'			=> ['BOOL', 1],
				],
				$this->table_prefix . 'dm_eds' => [
					'download_image'			=> ['VCHAR:255', ''],
					'enable_bbcode_file'		=> ['BOOL', 1],
					'enable_smilies_file'		=> ['BOOL', 1],
					'enable_magic_url_file'		=> ['BOOL', 1],
				],
			],
			'drop_columns' => [
				$this->table_prefix . 'dm_eds' => [
					'bbcode_uid',
					'bbcode_bitfield',
					'bbcode_options',
				],
			],
		];
	}

	public function dm_eds_image_dir()
	{
		global $phpbb_container;

		$img_dir = $this->phpbb_root_path . 'images';
		$downloadsystem_dir = $img_dir . '/downloadsystem';
		$cat_dir = $downloadsystem_dir . '/categories';
		$filesystem = $phpbb_container->get('filesystem');

		if ($filesystem->exists($img_dir) && $filesystem->is_writable($img_dir))
		{
			if (!$filesystem->exists($downloadsystem_dir))
			{
				$filesystem->mkdir($downloadsystem_dir, 511);
			}
			if (!$filesystem->exists($cat_dir))
			{
				$filesystem->mkdir($cat_dir, 511);
			}
		}

		$filesystem->mirror($this->phpbb_root_path . 'ext/dmzx/downloadsystem/images/', $downloadsystem_dir);
	}
}
