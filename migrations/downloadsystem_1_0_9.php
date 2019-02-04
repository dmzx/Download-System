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
		return array(
			'\dmzx\downloadsystem\migrations\downloadsystem_1_0_8',
		);
	}

	public function update_data()
	{
		return array(
			array('config.update', array('download_system_version', '1.0.9')),
			array('custom', array(array($this, 'dm_eds_image_dir'))),
		);
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'dm_eds_config' => array(
					'dm_eds_image_dir'			=> array('VCHAR', 'images/downloadsystem'),
					'dm_eds_image_cat_dir'		=> array('VCHAR', 'images/downloadsystem/categories'),
					'dm_eds_image_size'			=> array('VCHAR', '15'),
					'dm_eds_allow_bbcodes'		=> array('BOOL', 1),
					'dm_eds_allow_smilies'		=> array('BOOL', 1),
					'dm_eds_allow_magic_url'	=> array('BOOL', 1),
					'dm_eds_allow_dl_img'		=> array('BOOL', 1),
					'dm_eds_allow_cat_img'		=> array('BOOL', 1),
				),
				$this->table_prefix . 'dm_eds_cat' => array(
					'category_image'			=> array('VCHAR:255', ''),
					'enable_bbcode'				=> array('BOOL', 1),
					'enable_smilies'			=> array('BOOL', 1),
					'enable_magic_url'			=> array('BOOL', 1),
				),
				$this->table_prefix . 'dm_eds' => array(
					'download_image'			=> array('VCHAR:255', ''),
					'enable_bbcode_file'		=> array('BOOL', 1),
					'enable_smilies_file'		=> array('BOOL', 1),
					'enable_magic_url_file'		=> array('BOOL', 1),
				),
			),
			'drop_columns' => array(
				$this->table_prefix . 'dm_eds' => array(
					'bbcode_uid',
					'bbcode_bitfield',
					'bbcode_options',
				),
			),
		);
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
