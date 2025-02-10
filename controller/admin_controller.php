<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\controller;

use dmzx\downloadsystem\core\functions;
use phpbb\filesystem\filesystem;
use phpbb\textformatter\parser_interface;
use phpbb\textformatter\renderer_interface;
use phpbb\controller\helper;
use phpbb\template\template;
use phpbb\user;
use phpbb\log\log_interface;
use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\db\driver\driver_interface as db_interface;
use phpbb\request\request_interface;
use phpbb\pagination;
use phpbb\extension\manager;
use phpbb\path_helper;
use phpbb\config\config;
use phpbb\files\factory;

class admin_controller
{
	/** @var functions */
	protected $functions;

	/** @var filesystem */
	protected $filesystem;

	/** @var parser_interface */
	protected $parser;

	/** @var renderer_interface */
	protected $renderer;

	/** @var helper */
	protected $helper;

	/** @var template */
	protected $template;

	/** @var user */
	protected $user;

	/** @var log_interface */
	protected $log;

	/** @var cache_interface */
	protected $cache;

	/** @var db_interface */
	protected $db;

	/** @var request_interface */
	protected $request;

	/** @var pagination */
	protected $pagination;

	/** @var manager */
	protected $ext_manager;

	/** @var path_helper */
	protected $path_helper;

	/** @var config */
	protected $config;

	/** @var string */
	protected $php_ext;

	/** @var string */
	protected $root_path;

	/**
	* The database tables
	*
	* @var string
	*/
	protected $dm_eds_table;

	protected $dm_eds_cat_table;

	protected $dm_eds_config_table;

	/** @var factory */
	protected $files_factory;

	/**
	* Constructor
	*
	* @param functions					$functions
	* @param filesystem					$filesystem
	* @param parser_interface			$parser
	* @param renderer_interface 		$renderer
	* @param helper						$helper
	* @param template		 			$template
	* @param user						user
	* @param log_interface				$log
	* @param cache_interface 			$cache
	* @param db_interface				$db
	* @param request_interface		 	$request
	* @param pagination					$pagination
	* @param manager					$ext_manager
	* @param path_helper				$path_helper
	* @param config						$config
	* @param string 					$php_ext
	* @param string 					$root_path
	* @param string 					$dm_eds_table
	* @param string 					$dm_eds_cat_table
	* @param string 					$dm_eds_config_table
	* @param factory					$files_factory
	*
	*/
	public function __construct(
		functions $functions,
		filesystem $filesystem,
		parser_interface $parser,
		renderer_interface $renderer,
		helper $helper,
		template $template,
		user $user,
		log_interface $log,
		cache_interface $cache,
		db_interface $db,
		request_interface $request,
		pagination $pagination,
		manager $ext_manager,
		path_helper $path_helper,
		config $config,
		$php_ext, $root_path,
		$dm_eds_table,
		$dm_eds_cat_table,
		$dm_eds_config_table,
		factory $files_factory = null
	)
	{
		$this->functions 			= $functions;
		$this->filesystem			= $filesystem;
		$this->parser				= $parser;
		$this->renderer				= $renderer;
		$this->helper				= $helper;
		$this->template 			= $template;
		$this->user 				= $user;
		$this->log 					= $log;
		$this->cache 				= $cache;
		$this->db 					= $db;
		$this->request 				= $request;
		$this->pagination 			= $pagination;
		$this->ext_manager	 		= $ext_manager;
		$this->path_helper	 		= $path_helper;
		$this->config				= $config;
		$this->php_ext 				= $php_ext;
		$this->root_path 			= $root_path;
		$this->dm_eds_table 		= $dm_eds_table;
		$this->dm_eds_cat_table 	= $dm_eds_cat_table;
		$this->dm_eds_config_table 	= $dm_eds_config_table;
		$this->files_factory 		= $files_factory;

		$this->ext_path = $this->ext_manager->get_extension_path('dmzx/downloadsystem', true);
		$this->ext_path_web = $this->path_helper->update_web_root_path($this->ext_path);

		if (!function_exists('submit_post'))
		{
			include($this->root_path . 'includes/functions_posting.' . $this->php_ext);
		}

		$this->user->add_lang('posting');
	}

	public function display_config()
	{
		$form_action = $this->u_action. '&amp;action=add';

		// Read out config values
		$eds_values = $this->functions->config_values();

		$set_pagination = $this->request->is_set('action_set_pagination') ? true : false;

		if ($set_pagination)
		{
			// Values for eds_config
			$sql_ary = [
				'pagination_acp'			=> $this->request->variable('pagination_acp', 0),
				'pagination_user'			=> $this->request->variable('pagination_user', 0),
				'announce_enable'			=> $this->request->variable('announce_enable', 0),
				'announce_forum'			=> $this->request->variable('announce_forum', 0),
				'announce_lock_enable'		=> $this->request->variable('announce_lock_enable', 0),
				'pagination_downloads'		=> $this->request->variable('pagination_downloads', 0),
				'dm_eds_image_size'			=> $this->request->variable('dm_eds_image_size', 0),
				'dm_eds_image_dir'			=> $this->request->variable('dm_eds_image_dir', 'images/downloadsystem', true),
				'dm_eds_image_cat_dir'		=> $this->request->variable('dm_eds_image_cat_dir', 'images/downloadsystem/categories', true),
				'dm_eds_allow_bbcodes'		=> $this->request->variable('dm_eds_allow_bbcodes', 0),
				'dm_eds_allow_smilies'		=> $this->request->variable('dm_eds_allow_smilies', 0),
				'dm_eds_allow_magic_url'	=> $this->request->variable('dm_eds_allow_magic_url', 0),
				'dm_eds_allow_dl_img'		=> $this->request->variable('dm_eds_allow_dl_img', 0),
				'dm_eds_allow_cat_img'		=> $this->request->variable('dm_eds_allow_cat_img', 0),
				'show_donation'				=> $this->request->variable('show_donation', 0),
				'donation_url'				=> $this->request->variable('donation_url', '', true),
			];

			// Check if pagination_acp is at least 5
			$check_acp = $this->request->variable('pagination_acp', 0);
			if ($check_acp < 5)
			{
				trigger_error($this->user->lang['ACP_PAGINATION_ERROR_ACP'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			// Check if pagination_user is at least 3
			$check_user = $this->request->variable('pagination_user', 0);
			if ($check_user < 3)
			{
				trigger_error($this->user->lang['ACP_PAGINATION_ERROR_USER'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			// Check if pagination_downloads is at least 10
			$check_user = $this->request->variable('pagination_downloads', 0);
			if ($check_user < 10)
			{
				trigger_error($this->user->lang['ACP_PAGINATION_ERROR_DOWNLOADS'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			// Check if announce forum id exists
			if ($this->request->variable('announce_enable', 0) == 1)
			{
				$check_forum_id = $this->request->variable('announce_forum', 0);
				$sql = 'SELECT *
					FROM ' . FORUMS_TABLE . '
					WHERE forum_id = ' . $check_forum_id;
				$result = $this->db->sql_query($sql);
				$check_id = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (empty($check_id))
				{
					trigger_error($this->user->lang['ACP_FORUM_ID_ERROR'] . adm_back_link($this->u_action), E_USER_WARNING);
				}
			}

			// Update values
			$sql = 'UPDATE ' . $this->dm_eds_config_table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary);
			$this->db->sql_query($sql);

			// Log message
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_UPDATED');

			trigger_error($this->user->lang['ACP_CONFIG_SUCCESS'] . adm_back_link($this->u_action));
		}
		else
		{
			$this->template->assign_vars([
				'PAGINATION_ACP'			=> $eds_values['pagination_acp'],
				'PAGINATION_USER'			=> $eds_values['pagination_user'],
				'ANNOUNCE_ENABLE'			=> $eds_values['announce_enable'],
				'ANNOUNCE_FORUM'			=> $eds_values['announce_forum'],
				'ANNOUNCE_LOCK'				=> $eds_values['announce_lock_enable'],
				'PAGINATION_DOWNLOADS'		=> $eds_values['pagination_downloads'],
				'DM_EDS_IMAGE_DIR'			=> $eds_values['dm_eds_image_dir'],
				'DM_EDS_IMAGE_CAT_DIR'		=> $eds_values['dm_eds_image_cat_dir'],
				'DM_EDS_IMAGE_SIZE'			=> $eds_values['dm_eds_image_size'],
				'DM_EDS_ALLOW_BBCODES'		=> $eds_values['dm_eds_allow_bbcodes'],
				'DM_EDS_ALLOW_SMILIES'		=> $eds_values['dm_eds_allow_smilies'],
				'DM_EDS_ALLOW_MAGIC_URL'	=> $eds_values['dm_eds_allow_magic_url'],
				'DM_EDS_ALLOW_DL_IMG'		=> $eds_values['dm_eds_allow_dl_img'],
				'DM_EDS_ALLOW_CAT_IMG'		=> $eds_values['dm_eds_allow_cat_img'],
				'SHOW_DONATION'				=> $eds_values['show_donation'],
				'DONATION_URL'				=> $eds_values['donation_url'],
				'DM_EDS_VERSION'			=> $this->config['download_system_version'],
				'U_BACK'					=> $this->u_action,
				'U_ACTION'					=> $form_action,
				'U_ABOUT'					=> $this->u_action. '&amp;action=about',
			]);
		}

		include($this->ext_path_web . 'includes/parsedown.' . $this->php_ext);

		$parsedown = new \parsedown();

		$s_about = $this->u_action. '&amp;action=about';

		if ($s_about)
		{
			$changelog_file = $this->ext_path_web . 'CHANGELOG.md';

			if (file_exists($changelog_file))
			{
				// let's get the changelog :)
				$data = file($changelog_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

				// We do not want the first line.
				unset($data[0]);

				foreach ($data as $row)
				{
					$row = ltrim($row);

					if ($row[0] === '#')
					{
						$key = substr($row, 3);

						$this->template->assign_block_vars('history', [
							'CHANGES_SINCE'	=> $key,
							'U_CHANGES'		=> strtolower(str_replace([' ', '.'], ['-', ''], $key)),
						]);
					}
					else if ($row[0] === '-')
					{
						$change = substr($row, 2);

						$this->template->assign_block_vars('history.changelog', [
							'CHANGE'	=> $parsedown->line($change),
						]);
					}
				}
			}
		}
	}

	public function new_download()
	{
		// Read out config values
		$eds_values = $this->functions->config_values();

		$form_action 	= $this->u_action. '&amp;action=add_new';
		$lang_mode 		= $this->user->lang['ACP_NEW_DOWNLOAD'];
		$action 		= $this->request->variable('action', '');
		$action 		= ($this->request->is_set('submit') && !$this->request->is_set('id')) ? 'add' : $action;
		$id				= $this->request->variable('id', 0);
		$title			= $this->request->variable('title', '', true);
		$filename		= $this->request->variable('filename', '', true);
		$desc			= $this->request->variable('desc', '', true);
		$dl_version		= $this->request->variable('dl_version', '', true);
		$costs_dl		= $this->request->variable('cost_per_dl', 0.00);
		$ftp_upload 	= $this->request->variable('ftp_upload', '', true);

		// Check if categories exists
		$sql = 'SELECT COUNT(cat_id) AS total_cats
			FROM ' . $this->dm_eds_cat_table;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$total_cats = $row['total_cats'];
		$this->db->sql_freeresult($result);

		if ($total_cats <= 0)
		{
			trigger_error($this->user->lang['ACP_NO_CAT'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$sql = 'SELECT *
			FROM ' . $this->dm_eds_cat_table . '
			ORDER BY LOWER(cat_name)';
		$result = $this->db->sql_query($sql);
		$cats = [];
		while ($row2 = $this->db->sql_fetchrow($result))
		{
			$cats[$row2['cat_id']] = [
				'cat_title'	=> $row2['cat_name'],
				'cat_id'	=> $row2['cat_id'],
			];
		}
		$this->db->sql_freeresult($result);

		$cat_options = '';

		foreach ($cats as $key => $value)
		{
			if ($key == isset($row2['download_cat_id']))
			{
				$cat_options .= '<option value="' . $value['cat_id'] . '" selected="selected">' . $value['cat_title'] . '</option>';
			}
			else
			{
				$cat_options .= '<option value="' . $value['cat_id'] . '">' . $value['cat_title'] . '</option>';
			}
		}

		$max_filesize = @ini_get('upload_max_filesize');
		$unit = 'MB';

		if (!empty($max_filesize))
		{
			$unit = strtolower(substr($max_filesize, -1, 1));
			$max_filesize = (int) $max_filesize;

			$unit = ($unit == 'k') ? 'KB' : (($unit == 'g') ? 'GB' : 'MB');
		}

		$this->template->assign_vars([
			'ID'					=> $id,
			'TITLE'					=> $title,
			'DESC'					=> $desc,
			'FILENAME'				=> $filename,
			'DL_VERSION'			=> $dl_version,
			'FTP_UPLOAD'			=> $ftp_upload,
			'PARENT_OPTIONS'		=> $cat_options,
			'ALLOWED_SIZE'			=> sprintf($this->user->lang['ACP_NEW_DOWNLOAD_SIZE'], $max_filesize, $unit),
			'U_BACK'				=> $this->u_action,
			'U_ACTION'				=> $form_action,
			'L_MODE_TITLE'			=> $lang_mode,
			'S_BBCODE_ENABLED'		=> !empty($dm_eds_data['enable_bbcode_file']) ? $dm_eds_data['enable_bbcode_file'] : 0,
			'S_SMILIES_ENABLED'		=> !empty($dm_eds_data['enable_smilies_file']) ? $dm_eds_data['enable_smilies_file'] : 0,
			'S_MAGIC_URL_ENABLED'	=> !empty($dm_eds_data['enable_magic_url_file']) ? $dm_eds_data['enable_magic_url_file'] : 0,
			'BBCODE_STATUS'			=> !empty($eds_values['dm_eds_allow_bbcodes']) ? $this->user->lang('BBCODE_IS_ON', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>') : $this->user->lang('BBCODE_IS_OFF', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>'),
			'SMILIES_STATUS'		=> !empty($eds_values['dm_eds_allow_smilies']) ? $this->user->lang('SMILIES_ARE_ON') : $this->user->lang('SMILIES_ARE_OFF'),
			'URL_STATUS'			=> !empty($eds_values['dm_eds_allow_magic_url']) ? $this->user->lang('URL_IS_ON') : $this->user->lang('URL_IS_OFF'),
			'S_DL_CATEGORY_ADD'		=> true,
			'S_DM_EDS_ALLOW_DL_IMG'	=> $eds_values['dm_eds_allow_dl_img'],
		]);
	}

	public function copy_new()
	{
		// Read out config values
		$eds_values = $this->functions->config_values();

		$form_action = $this->u_action. '&amp;action=add_new';
		$lang_mode = $this->user->lang['ACP_NEW_DOWNLOAD'];

		$action = $this->request->variable('action', '');
		$action = ($this->request->is_set('submit') && !$this->request->is_set('id')) ? 'add' : $action;

		$id	= $this->request->variable('id', 0);

		$sql = 'SELECT *
			FROM ' . $this->dm_eds_table . '
			WHERE download_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		decode_message($row['download_desc']);
		$copy_title = $row['download_title'];
		$copy_version = $row['download_version'];
		$copy_desc = $row['download_desc'];
		$copy_costs_dl = $row['cost_per_dl'];
		$download_image = $row['download_image'];
		$this->db->sql_freeresult($result);

		$id							= $this->request->variable('id', 0);
		$title						= $this->request->variable('title', '', true);
		$filename					= $this->request->variable('filename', '', true);
		$desc						= $this->request->variable('desc', '', true);
		$dl_version					= $this->request->variable('dl_version', '', true);
		$costs_dl					= $this->request->variable('cost_per_dl', 0.00);
		$ftp_upload 				= $this->request->variable('ftp_upload', '', true);
		$enable_bbcode_file			= !$this->request->variable('disable_bbcode_file', false);
		$enable_smilies_file		= !$this->request->variable('disable_smilies_file', false);
		$enable_magic_url_file		= !$this->request->variable('disable_magic_url_file', false);

		// Check if categories exists
		$sql = 'SELECT COUNT(cat_id) AS total_cats
			FROM ' . $this->dm_eds_cat_table;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$total_cats = $row['total_cats'];
		$this->db->sql_freeresult($result);

		if ($total_cats <= 0)
		{
			trigger_error($this->user->lang['ACP_NO_CAT'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$sql = 'SELECT *
			FROM ' . $this->dm_eds_cat_table . '
			ORDER BY LOWER(cat_name)';
		$result = $this->db->sql_query($sql);

		$cats = [];

		while ($row2 = $this->db->sql_fetchrow($result))
		{
			$cats[$row2['cat_id']] = [
				'cat_title'	=> $row2['cat_name'],
				'cat_id'	=> $row2['cat_id'],
			];
		}
		$this->db->sql_freeresult($result);

		$cat_options = '';

		foreach ($cats as $key => $value)
		{
			if ($key == isset($row2['download_cat_id']))
			{
				$cat_options .= '<option value="' . $value['cat_id'] . '" selected="selected">' . $value['cat_title'] . '</option>';
			}
			else
			{
				$cat_options .= '<option value="' . $value['cat_id'] . '">' . $value['cat_title'] . '</option>';
			}
		}

		$max_filesize = @ini_get('upload_max_filesize');
		$unit = 'MB';

		if (!empty($max_filesize))
		{
			$unit = strtolower(substr($max_filesize, -1, 1));
			$max_filesize = (int) $max_filesize;

			$unit = ($unit == 'k') ? 'KB' : (($unit == 'g') ? 'GB' : 'MB');
		}

		$upload_dl_dir = $eds_values['dm_eds_image_dir'];

		$this->template->assign_vars([
			'ID'						=> $id,
			'TITLE'						=> $copy_title,
			'DESC'						=> $copy_desc,
			'FILENAME'					=> $filename,
			'FTP_UPLOAD'				=> $ftp_upload,
			'DL_VERSION'				=> $copy_version,
			'PARENT_OPTIONS'			=> $cat_options,
			'ALLOWED_SIZE'				=> sprintf($this->user->lang['ACP_NEW_DOWNLOAD_SIZE'], $max_filesize, $unit),
			'U_BACK'					=> $this->u_action,
			'U_ACTION'					=> $form_action,
			'L_MODE_TITLE'				=> $lang_mode,
			'S_BBCODE_ENABLED_FILE'		=> !empty($dm_eds_data['enable_bbcode_file']) ? $dm_eds_data['enable_bbcode_file'] : 0,
			'S_SMILIES_ENABLED_FILE'	=> !empty($dm_eds_data['enable_smilies_file']) ? $dm_eds_data['enable_smilies_file'] : 0,
			'S_MAGIC_URL_ENABLED_FILE'	=> !empty($dm_eds_data['enable_magic_url_file']) ? $dm_eds_data['enable_magic_url_file'] : 0,
			'BBCODE_STATUS'				=> !empty($eds_values['dm_eds_allow_bbcodes']) ? $this->user->lang('BBCODE_IS_ON', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>') : $this->user->lang('BBCODE_IS_OFF', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>'),
			'SMILIES_STATUS'			=> !empty($eds_values['dm_eds_allow_smilies']) ? $this->user->lang('SMILIES_ARE_ON') : $this->user->lang('SMILIES_ARE_OFF'),
			'URL_STATUS'				=> !empty($eds_values['dm_eds_allow_magic_url']) ? $this->user->lang('URL_IS_ON') : $this->user->lang('URL_IS_OFF'),
			'S_DL_CATEGORY_ADD'			=> true,
			'DOWNLOAD_IMAGE'			=> !empty($download_image) ? $this->root_path . $upload_dl_dir . '/' . $download_image : '',
		]);
	}

	public function edit()
	{
		// Read out config values
		$eds_values = $this->functions->config_values();

		// Edit an existing download
		$form_action = $this->u_action. '&amp;action=update';
		$lang_mode = $this->user->lang['ACP_EDIT_DOWNLOADS'];

		$action = $this->request->variable('action', '');
		$action = ($this->request->is_set('submit') && !$this->request->is_set('id')) ? 'add' : $action;

		$enable_bbcode_file				= !$this->request->variable('disable_bbcode_file', false);
		$enable_smilies_file			= !$this->request->variable('disable_smilies_file', false);
		$enable_magic_url_file			= !$this->request->variable('disable_magic_url_file', false);

		$id = $this->request->variable('id', '');

		$sql = 'SELECT d.*, c.*
			FROM ' . $this->dm_eds_table . ' d
				LEFT JOIN ' . $this->dm_eds_cat_table . ' c
				ON d.download_cat_id = c.cat_id
			WHERE download_id = ' . (int) $id;
		$result = $this->db->sql_query_limit($sql,1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		decode_message($row['download_desc']);
		$download_id = $row['download_id'];
		$download_version = $row['download_version'];

		$sql = 'SELECT *
			FROM ' . $this->dm_eds_cat_table . '
			ORDER BY LOWER(cat_name)';
		$result = $this->db->sql_query($sql);

		$cats = [];

		while ($row2 = $this->db->sql_fetchrow($result))
		{
			$cats[$row2['cat_id']] = [
				'cat_title'	=> $row2['cat_name'],
				'cat_id'	=> $row2['cat_id'],
			];
		}
		$this->db->sql_freeresult($result);

		$cat_options = '';

		foreach ($cats as $key => $value)
		{
			if ($key == $row['download_cat_id'])
			{
				$cat_options .= '<option value="' . $value['cat_id'] . '" selected="selected">' . $value['cat_title'] . '</option>';
			}
			else
			{
				$cat_options .= '<option value="' . $value['cat_id'] . '">' . $value['cat_title'] . '</option>';
			}
		}

		$max_filesize = @ini_get('upload_max_filesize');
		$unit = 'MB';

		if (!empty($max_filesize))
		{
			$unit = strtolower(substr($max_filesize, -1, 1));
			$max_filesize = (int) $max_filesize;

			$unit = ($unit == 'k') ? 'KB' : (($unit == 'g') ? 'GB' : 'MB');
		}

		$upload_dl_dir = $eds_values['dm_eds_image_dir'];

		$this->template->assign_vars([
			'ID'						=> $download_id,
			'TITLE'						=> $row['download_title'],
			'DESC'						=> $row['download_desc'],
			'FILENAME'					=> $row['download_filename'],
			'CATNAME'					=> $row['cat_name'],
			'DL_VERSION'				=> $download_version,
			'PARENT_OPTIONS'			=> $cat_options,
			'ALLOWED_SIZE'				=> sprintf($this->user->lang['ACP_NEW_DOWNLOAD_SIZE'], $max_filesize, $unit),
			'U_ACTION'					=> $form_action,
			'L_MODE_TITLE'				=> $lang_mode,
			'S_BBCODE_ENABLED_FILE'		=> !empty($row['enable_bbcode_file']) ? $row['enable_bbcode_file'] : 0,
			'S_SMILIES_ENABLED_FILE'	=> !empty($row['enable_smilies_file']) ? $row['enable_smilies_file'] : 0,
			'S_MAGIC_URL_ENABLED_FILE'	=> !empty($row['enable_magic_url_file']) ? $row['enable_magic_url_file'] : 0,
			'BBCODE_STATUS'				=> !empty($eds_values['dm_eds_allow_bbcodes']) ? $this->user->lang('BBCODE_IS_ON', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>') : $this->user->lang('BBCODE_IS_OFF', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>'),
			'SMILIES_STATUS'			=> !empty($eds_values['dm_eds_allow_smilies']) ? $this->user->lang('SMILIES_ARE_ON') : $this->user->lang('SMILIES_ARE_OFF'),
			'URL_STATUS'				=> !empty($eds_values['dm_eds_allow_magic_url']) ? $this->user->lang('URL_IS_ON') : $this->user->lang('URL_IS_OFF'),
			'DOWNLOAD_IMAGE'			=> !empty($row['download_image']) ? $this->root_path . $upload_dl_dir . '/' . $row['download_image'] : '',
		]);
	}

	public function add_new()
	{
		$filecheck = $multiplier = '';

		// Read out config values
		$eds_values = $this->functions->config_values();

		$id							= $this->request->variable('id', 0);
		$title						= $this->request->variable('title', '', true);
		$filename					= $this->request->variable('filename', '', true);
		$desc						= $this->request->variable('desc', '', true);
		$dl_version					= $this->request->variable('dl_version', '', true);
		$costs_dl					= $this->request->variable('cost_per_dl', 0.00, true);
		$cat_option 				= $this->request->variable('parent', '', true);
		$upload_time 				= time();
		$last_changed_time 			= time();
		$uid = $bitfield 			= $options = '';
		$allow_bbcode 				= $allow_urls = $allow_smilies = true;
		$ftp_upload					= $this->request->variable('ftp_upload', '', true);
		$enable_bbcode_file			= !$this->request->variable('disable_bbcode_file', false);
		$enable_smilies_file		= !$this->request->variable('disable_smilies_file', false);
		$enable_magic_url_file		= !$this->request->variable('disable_magic_url_file', false);

		if (!$ftp_upload)
		{
			// Check max. allowed filesize from php.ini
			$max_filesize = @ini_get('upload_max_filesize');
			$unit = 'MB';

			if (!empty($max_filesize))
			{
				$unit = strtolower(substr($max_filesize, -1, 1));
				$max_filesize = (int) $max_filesize;

				$unit = ($unit == 'k') ? 'KB' : (($unit == 'g') ? 'GB' : 'MB');
			}
		}

		// Add allowed extensions
		$allowed_extensions = $this->functions->allowed_extensions();
		$allowed_image_extensions = $this->functions->allowed_image_extensions();

		// Check if categories exists
		$sql = 'SELECT COUNT(cat_id) AS total_cats
			FROM ' . $this->dm_eds_cat_table;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$total_cats = $row['total_cats'];
		$this->db->sql_freeresult($result);

		if ($total_cats <= 0)
		{
			trigger_error($this->user->lang['ACP_NO_CAT_UPLOAD'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$fileupload = $this->files_factory->get('upload')
			->set_allowed_extensions($allowed_extensions);

		$target_folder = $this->request->variable('parent', 0);
		$upload_name = $this->request->variable('filename', '');

		// Check if FTP upload and normal upload is entered
		if ($ftp_upload && $upload_name)
		{
			trigger_error($this->user->lang['ACP_FTP_UPLOAD'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$sql = 'SELECT cat_sub_dir
			FROM ' . $this->dm_eds_cat_table . '
			WHERE cat_id = ' . (int) $target_folder;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$target = $row['cat_sub_dir'];
		$this->db->sql_freeresult($result);

		$upload_dir = 'ext/dmzx/downloadsystem/files/' . $target;

		if (!$ftp_upload)
		{
			$upload_file = $fileupload->handle_upload('files.types.form', 'filename');

			if (!$upload_file->get('uploadname'))
			{
				trigger_error($this->user->lang['ACP_NO_FILENAME'] . adm_back_link($this->u_action), E_USER_WARNING);//continue;
			}

			if (file_exists($this->root_path . $upload_dir . '/' . $upload_file->get('uploadname')))
			{
				trigger_error($this->user->lang['ACP_UPLOAD_FILE_EXISTS'] . adm_back_link($this->u_action), E_USER_WARNING);//continue;
			}

			$upload_file->move_file($upload_dir, false, false, 0644);

			if (sizeof($upload_file->error) && $upload_file->get('uploadname'))
			{
				$upload_file->remove();
				trigger_error(implode('<br />', $upload_file->error), E_USER_WARNING);
			}
			// End the upload

			$filesize = @filesize($this->root_path . $upload_dir . '/' . $upload_file->get('uploadname'));
			$sql_ary = [
				'download_title'			=> $title,
				'download_desc'	 			=> $desc,
				'download_filename'			=> $upload_file->get('uploadname'),
				'download_version'			=> $dl_version,
				'download_cat_id'			=> $cat_option,
				'upload_time'				=> $upload_time,
				'cost_per_dl'				=> $costs_dl,
				'last_changed_time'			=> $last_changed_time,
				'filesize'					=> $filesize,
				'points_user_id'			=> $this->user->data['user_id'],
				'enable_bbcode_file'		=> $enable_bbcode_file,
				'enable_smilies_file'		=> $enable_smilies_file,
				'enable_magic_url_file'		=> $enable_magic_url_file,
			];

			# Get an instance of the files upload class
			$upload = $this->files_factory->get('upload')
				-> set_max_filesize($eds_values['dm_eds_image_size'] * 1024)
				-> set_allowed_extensions($allowed_image_extensions);

			$upload_file = $upload->handle_upload('files.types.form', 'download_image');

			if (sizeof($upload_file->error) && $upload_file->get('uploadname'))
			{
				trigger_error(implode('<br />', $upload_file->error), E_USER_WARNING);
			}

			$upload_name = $this->request->variable('download_image', '');

			$upload_dir = $eds_values['dm_eds_image_dir'];

			if ($upload_file->get('uploadname'))
			{
				$upload_file->clean_filename('unique_ext', 'dm_eds_dl_');
				$upload_file->move_file($upload_dir, true, true, 0644);
				$sql_ary['download_image'] = $upload_file->get('realname');
			}
			else
			{
				$sql_ary['download_image'] = 'default_dl.png';
			}

			// Check, if filesize is greater than PHP ini allows
			if ($unit == 'MB')
			{
				$multiplier = 1048576;
			}
			else if ($unit == 'KB')
			{
				$multiplier = 1024;
			}

			if ($filesize > ($max_filesize * $multiplier))
			{
				@unlink($this->root_path . $upload_dir . '/' . $upload_file->get('uploadname'));
				trigger_error($this->user->lang['ACP_FILE_TOO_BIG'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
		}
		else
		{
			// check, if FTP upload file exists
			if (!file_exists($this->root_path . $upload_dir . '/' . $ftp_upload))
			{
				trigger_error($this->user->lang['ACP_UPLOAD_FILE_NOT_EXISTS'] . adm_back_link($this->u_action), E_USER_WARNING);//continue;
			}

			$filesize = @filesize($this->root_path . $upload_dir . '/' . $ftp_upload);
			$sql_ary = [
				'download_title'			=> $title,
				'download_desc'	 			=> $desc,
				'download_filename'			=> $ftp_upload,
				'download_version'			=> $dl_version,
				'download_cat_id'			=> $cat_option,
				'upload_time'				=> $upload_time,
				'cost_per_dl'				=> $costs_dl,
				'last_changed_time'			=> $last_changed_time,
				'filesize'					=> $filesize,
				'points_user_id'			=> $this->user->data['user_id'],
				'enable_bbcode_file'		=> $enable_bbcode_file,
				'enable_smilies_file'		=> $enable_smilies_file,
				'enable_magic_url_file'		=> $enable_magic_url_file,
			];
		}

		!$sql_ary['enable_bbcode_file'] || !$eds_values['dm_eds_allow_bbcodes'] ? $this->parser->disable_bbcodes() : $this->parser->enable_bbcodes();
		!$sql_ary['enable_smilies_file'] || !$eds_values['dm_eds_allow_smilies'] ? $this->parser->disable_smilies() : $this->parser->enable_smilies();
		!$sql_ary['enable_magic_url_file'] || !$eds_values['dm_eds_allow_magic_url'] ? $this->parser->disable_magic_url() : $this->parser->enable_magic_url();
		$download_desc = $sql_ary['download_desc'];
		$download_desc = htmlspecialchars_decode($download_desc, ENT_COMPAT);
		$sql_ary['download_desc'] = $this->parser->parse($download_desc);

		// Announce download, if enabled
		if ($eds_values['announce_enable'] == 1)
		{
			$sql = 'SELECT *
				FROM ' . $this->dm_eds_cat_table . '
				WHERE cat_id = ' . (int) $cat_option;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$cat_name = $row['cat_name'];
			$this->db->sql_freeresult($result);

			if (empty($dl_version))
			{
				$dl_title = $title;
			}
			else
			{
				$dl_title = $title . ' v' . $dl_version;
			}

			$download_link = '[url=' . generate_board_url() . '/downloadsystemcat?id=' . $cat_option . ']' . $this->user->lang['ACP_CLICK'] . '[/url]';
			$download_subject = $this->user->lang('ACP_ANNOUNCE_TITLE', $dl_title);

			$download_msg = $this->user->lang('ACP_ANNOUNCE_MSG', $title, $desc, $cat_name, $download_link);

			$this->functions->create_announcement($download_subject, $download_msg, $eds_values['announce_forum']);
		}

		$this->db->sql_query('INSERT INTO ' . $this->dm_eds_table .' ' . $this->db->sql_build_array('INSERT', $sql_ary));

		// Log message
		$this->log_message('LOG_DOWNLOAD_ADD', $title, 'ACP_NEW_ADDED');
	}

	public function update()
	{
		// Change an existing download
		$filecheck = $filecheck_current = $new_filename = '';

		// Read out config values
		$eds_values = $this->functions->config_values();

		$id = $this->request->variable('id', '');

		$sql = 'SELECT d.download_cat_id, d.download_filename, d.filesize, c.cat_sub_dir
			FROM ' . $this->dm_eds_table . ' d
			LEFT JOIN ' . $this->dm_eds_cat_table . ' c
				ON d.download_cat_id = c.cat_id
			WHERE download_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$current_cat_id = $row['download_cat_id'];
		$current_cat_name = $row['cat_sub_dir'];
		$current_filename = $row['download_filename'];
		$current_filesize = $row['filesize'];
		$this->db->sql_freeresult($result);

		$title 						= $this->request->variable('title', '', true);
		$v_cat_id					= $this->request->variable('parent', '');
		$dl_version					= $this->request->variable('dl_version', '', true);
		$costs_dl					= $this->request->variable('cost_per_dl', 0.00);
		$cat_option 				= $this->request->variable('parent', '', true);
		$upload_time 				= time();
		$last_changed_time 			= time();
		$desc 						= $this->request->variable('desc', '', true);
		$announce_up 				= $this->request->variable('announce_up', '');
		$ftp_upload					= $this->request->variable('ftp_upload', '', true);
		$enable_bbcode_file			= !$this->request->variable('disable_bbcode_file', false);
		$enable_smilies_file		= !$this->request->variable('disable_smilies_file', false);
		$enable_magic_url_file		= !$this->request->variable('disable_magic_url_file', false);
		$changedlimage 				= $this->request->variable('changedlimage', '');

		// Add allowed extensions
		$allowed_extensions = $this->functions->allowed_extensions();
		$allowed_image_extensions = $this->functions->allowed_image_extensions();

		$fileupload = $this->files_factory->get('upload')
			->set_allowed_extensions($allowed_extensions);

		$target_folder = $this->request->variable('parent', 0);
		$upload_name = $this->request->variable('filename', '');

		// Check if FTP upload and normal upload is entered
		if ($ftp_upload && $upload_name)
		{
			trigger_error($this->user->lang['ACP_FTP_UPLOAD'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$sql = 'SELECT cat_sub_dir
			FROM ' . $this->dm_eds_cat_table . '
			WHERE cat_id = ' . (int) $target_folder;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$target = $row['cat_sub_dir'];
		$this->db->sql_freeresult($result);

		$upload_dir = 'ext/dmzx/downloadsystem/files/' . $target;

		if (!$ftp_upload)
		{
			$upload_file = $fileupload->handle_upload('files.types.form', 'filename');

			$new_filename = $upload_file->get('uploadname');

			if (!$upload_file->get('uploadname'))
			{
				$new_filename = $current_filename;
				$filesize = $current_filesize;
			}
			else
			{
				$delete_file = $this->ext_path_web . 'files/' . $current_cat_name .'/' . $current_filename;
				@unlink($delete_file);

				$upload_file->move_file($upload_dir, false, false, 0644);

				if (sizeof($upload_file->error) && $upload_file->get('uploadname'))
				{
					$upload_file->remove();
					trigger_error(implode('<br />', $upload_file->error), E_USER_WARNING);
				}

				$filesize = @filesize($this->root_path . $upload_dir . '/' . $new_filename);
			}

			$sql_ary = [
				'download_title'		=> $title,
				'download_version'		=> $dl_version,
				'download_desc'			=> $desc,
				'download_filename'		=> $new_filename,
				'download_cat_id'		=> $v_cat_id,
				'cost_per_dl'			=> $costs_dl,
				'last_changed_time' 	=> $last_changed_time,
				'filesize'				=> $filesize,
				'points_user_id'		=> $this->user->data['user_id'],
				'enable_bbcode_file'	=> $enable_bbcode_file,
				'enable_smilies_file'	=> $enable_smilies_file,
				'enable_magic_url_file'	=> $enable_magic_url_file,
			];

			# Get an instance of the files upload class
			$upload = $this->files_factory->get('upload')
				-> set_max_filesize($eds_values['dm_eds_image_size'] * 1024)
				-> set_allowed_extensions($allowed_image_extensions);

			$upload_file = $upload->handle_upload('files.types.form', 'download_image');

			if (sizeof($upload_file->error) && $upload_file->get('uploadname'))
			{
				trigger_error(implode('<br />', $upload_file->error), E_USER_WARNING);
			}

			$upload_dir = $eds_values['dm_eds_image_dir'];

			if ($upload_file->get('uploadname') && $changedlimage != '')
			{
				$upload_file->clean_filename('unique_ext', 'dm_eds_dl_');
				$upload_file->move_file($upload_dir, true, true, 0644);
				$sql_ary['download_image'] = $upload_file->get('realname');
			}

			!$sql_ary['enable_bbcode_file'] || !$eds_values['dm_eds_allow_bbcodes'] ? $this->parser->disable_bbcodes() : $this->parser->enable_bbcodes();
			!$sql_ary['enable_smilies_file'] || !$eds_values['dm_eds_allow_smilies'] ? $this->parser->disable_smilies() : $this->parser->enable_smilies();
			!$sql_ary['enable_magic_url_file'] || !$eds_values['dm_eds_allow_magic_url'] ? $this->parser->disable_magic_url() : $this->parser->enable_magic_url();
			$download_desc = $sql_ary['download_desc'];
			$download_desc = htmlspecialchars_decode($download_desc, ENT_COMPAT);
			$sql_ary['download_desc'] = $this->parser->parse($download_desc);

			// If the title is empty, return an error
			if ($title == '')
			{
				trigger_error($this->user->lang['ACP_NEED_DATA'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
			else
			{
				// Check, if the file already is in the directory
				$sql = 'SELECT cat_sub_dir
					FROM ' . $this->dm_eds_cat_table . '
					WHERE cat_id = ' . (int) $v_cat_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$cat_dir = $row['cat_sub_dir'];
				$this->db->sql_freeresult($result);

				$filecheck = $this->ext_path_web . 'files/' . $cat_dir .'/' . $new_filename;
				$filecheck_current = $this->ext_path_web . 'files/' . $current_cat_name .'/' . $new_filename;

				// If file should move to new category
				if ($current_cat_id != $v_cat_id)
				{
					// Check, if the file already exists in the cat, where to move
					if (file_exists($filecheck))
					{
						trigger_error($this->user->lang['ACP_UPLOAD_FILE_EXISTS'] . adm_back_link($this->u_action), E_USER_WARNING);//continue;
					}
					else
					{
						// Announce download, if enabled
						if ($eds_values['announce_enable'] == 1 && $announce_up != '')
						{
							$sql = 'SELECT *
								FROM ' . $this->dm_eds_cat_table . '
								WHERE cat_id = ' . (int) $v_cat_id;
							$result = $this->db->sql_query($sql);
							$row = $this->db->sql_fetchrow($result);
							$cat_name = $row['cat_name'];
							$this->db->sql_freeresult($result);

							if (empty($dl_version))
							{
								$dl_title = $title;
							}
							else
							{
								$dl_title = $title . ' v' . $dl_version;
							}

							$download_link = '[url=' . generate_board_url() . '/downloadsystemcat?id=' . $v_cat_id . ']' . $this->user->lang['ACP_CLICK'] . '[/url]';
							$download_subject = sprintf($this->user->lang['ACP_ANNOUNCE_UP_TITLE'], $dl_title);

							$download_msg = sprintf($this->user->lang['ACP_ANNOUNCE_UP_MSG'], $title, $desc, $cat_name, $download_link);

							$this->functions->create_announcement($download_subject, $download_msg, $eds_values['announce_forum']);
						}

						if (rename(($this->root_path . 'ext/dmzx/downloadsystem/files/' . $current_cat_name . '/' .$new_filename), ($this->root_path . 'ext/dmzx/downloadsystem/files/' . $cat_dir . '/' . $new_filename)))
						{
							$this->db->sql_query('UPDATE ' . $this->dm_eds_table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE download_id = ' . (int) $id);
							$this->cache->destroy('sql', $this->dm_eds_table);
						}

						// Log message
						$this->log_message('LOG_DOWNLOAD_UPDATED', $title, 'ACP_DOWNLOAD_UPDATED');

						return;
					}
				}
				else // If only data changes and no new cat
				{
					// Announce download, if enabled
					if ($eds_values['announce_enable'] == 1 && $announce_up != '')
					{
						$sql = 'SELECT *
							FROM ' . $this->dm_eds_cat_table . '
							WHERE cat_id = ' . (int) $v_cat_id;
						$result = $this->db->sql_query($sql);
						$row = $this->db->sql_fetchrow($result);
						$cat_name = $row['cat_name'];
						$this->db->sql_freeresult($result);

						if (empty($dl_version))
						{
							$dl_title = $title;
						}
						else
						{
							$dl_title = $title . ' v' . $dl_version;
						}

						$download_link = '[url=' . generate_board_url() . '/downloadsystemcat?id=' . $v_cat_id . ']' . $this->user->lang['ACP_CLICK'] . '[/url]';
						$download_subject = sprintf($this->user->lang['ACP_ANNOUNCE_UP_TITLE'], $dl_title);

						$download_msg = sprintf($this->user->lang['ACP_ANNOUNCE_UP_MSG'], $title, $desc, $cat_name, $download_link);

						$this->functions->create_announcement($download_subject, $download_msg, $eds_values['announce_forum']);
					}

					$this->db->sql_query('UPDATE ' . $this->dm_eds_table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE download_id = ' . (int) $id);
					$this->cache->destroy('sql', $this->dm_eds_table);

					// Log message
					$this->log_message('LOG_DOWNLOAD_UPDATED', $title, 'ACP_DOWNLOAD_UPDATED');

					return;
				}
			}
		}
		else
		{
			// check, if FTP upload file exists
			if (!file_exists($this->root_path . $upload_dir . '/' . $ftp_upload))
			{
				trigger_error($this->user->lang['ACP_UPLOAD_FILE_NOT_EXISTS'] . adm_back_link($this->u_action), E_USER_WARNING);//continue;
			}

			$filesize = @filesize($this->root_path . $upload_dir . '/' . $ftp_upload);
			$sql_ary = [
				'download_title'		=> $title,
				'download_desc'	 		=> $desc,
				'download_filename'		=> $ftp_upload,
				'download_version'		=> $dl_version,
				'download_cat_id'		=> $cat_option,
				'upload_time'			=> $upload_time,
				'cost_per_dl'			=> $costs_dl,
				'last_changed_time'		=> $last_changed_time,
				'filesize'				=> $filesize,
				'points_user_id'		=> $this->user->data['user_id'],
				'enable_bbcode_file'	=> $enable_bbcode_file,
				'enable_smilies_file'	=> $enable_smilies_file,
				'enable_magic_url_file'	=> $enable_magic_url_file,
			];

			!$sql_ary['enable_bbcode_file'] || !$eds_values['dm_eds_allow_bbcodes'] ? $this->parser->disable_bbcodes() : $this->parser->enable_bbcodes();
			!$sql_ary['enable_smilies_file'] || !$eds_values['dm_eds_allow_smilies'] ? $this->parser->disable_smilies() : $this->parser->enable_smilies();
			!$sql_ary['enable_magic_url_file'] || !$eds_values['dm_eds_allow_magic_url'] ? $this->parser->disable_magic_url() : $this->parser->enable_magic_url();
			$download_desc = $sql_ary['download_desc'];
			$download_desc = htmlspecialchars_decode($download_desc, ENT_COMPAT);
			$sql_ary['download_desc'] = $this->parser->parse($download_desc);

			$this->db->sql_query('UPDATE ' . $this->dm_eds_table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE download_id = ' . (int) $id);
			$this->cache->destroy('sql', $this->dm_eds_table);

			// Log message
			$this->log_message('LOG_DOWNLOAD_UPDATED', $title, 'ACP_DOWNLOAD_UPDATED');

			return;
		}
	}

	public function delete()
	{
		$id = $this->request->variable('id', '');

		// Read out config values
		$eds_values = $this->functions->config_values();

		// Delete an existing download
		if (confirm_box(true))
		{
			$sql = 'SELECT c.cat_sub_dir, d.download_filename, d.download_image
				FROM ' . $this->dm_eds_cat_table . ' c
				LEFT JOIN ' . $this->dm_eds_table . ' d
					ON c.cat_id = d.download_cat_id
				WHERE d.download_id = ' . (int) $id;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$cat_dir = $row['cat_sub_dir'];
			$download_image = $row['download_image'];
			$file_name = $row['download_filename'];
			$this->db->sql_freeresult($result);

			$delete_file = $this->ext_path_web . 'files/' . $cat_dir .'/' . $file_name;
			@unlink($delete_file);

			$sql = 'DELETE FROM ' . $this->dm_eds_table . '
				WHERE download_id = '. (int) $id;
			$this->db->sql_query($sql);

			# Delete the download image
			if ($this->filesystem->exists($this->root_path . $eds_values['dm_eds_image_dir'] . '/' . $download_image))
			{
				if ($download_image != 'default_dl.png')
				{
					$this->filesystem->remove($this->root_path . $eds_values['dm_eds_image_dir'] . '/' . $download_image);
				}
			}

			// Log message
			$this->log_message('LOG_DOWNLOAD_DELETED', $file_name, 'ACP_DOWNLOAD_DELETED');

			if ($this->request->is_ajax())
			{
				$json_response->send([
					'MESSAGE_TITLE'	=> $this->user->lang('INFORMATION'),
					'MESSAGE_TEXT'	=> $this->user->lang('ACP_REALLY_DELETE'),
					'REFRESH_DATA'	=> [
						'time'	=> 3
					]
				]);
			}
		}
		else
		{
			confirm_box(false, $this->user->lang['ACP_REALLY_DELETE'], build_hidden_fields([
				'download_id'	=> $id,
				'action'	=> 'delete',
				])
			);
		}
		redirect($this->u_action);
	}

	public function display_downloads()
	{
		$action = $this->request->is_set_post('submit');
		$id = $this->request->variable('id', 0);
		$form_action = $this->u_action . '&amp;action=add';
		$lang_mode = $this->user->lang['ACP_ADD'];

		// Read out config values
		$eds_values = $this->functions->config_values();

		$start = $this->request->variable('start', 0);
		$number = $eds_values['pagination_acp'];

		$this->template->assign_vars([
			'BASE' => $this->u_action,
		]);

		$sort_days = $this->request->variable('st', 0);
		$sort_key = $this->request->variable('sk', 'download_title');
		$sort_dir = $this->request->variable('sd', 'ASC');
		$limit_days = [0 => $this->user->lang['ACP_ALL_DOWNLOADS'], 1 => $this->user->lang['1_DAY'], 7 => $this->user->lang['7_DAYS'], 14 => $this->user->lang['2_WEEKS'], 30 => $this->user->lang['1_MONTH'], 90 => $this->user->lang['3_MONTHS'], 180 => $this->user->lang['6_MONTHS'], 365 => $this->user->lang['1_YEAR']];

		$sort_by_text = ['t' => $this->user->lang['ACP_SORT_TITLE'], 'c' => $this->user->lang['ACP_SORT_CAT']];
		$sort_by_sql = ['t' => 'download_title', 'c' => 'cat_name'];

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
		$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');

		$search_query = $this->request->variable('q', '', true);
		$search_sql = $this->functions->generate_search_file_sql($search_query);

		// Total number of downloads
		$sql = 'SELECT COUNT(download_id) AS total_downloads
			FROM ' . $this->dm_eds_table . ' d
			LEFT JOIN ' . $this->dm_eds_cat_table . ' c
			ON d.download_cat_id = c.cat_id ' . $search_sql;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$total_downloads = $row['total_downloads'];
		$this->db->sql_freeresult($result);

		// List all downloads
		$sql = 'SELECT d.*, c.*
			FROM ' . $this->dm_eds_table . ' d
			LEFT JOIN ' . $this->dm_eds_cat_table . ' c
			ON d.download_cat_id = c.cat_id ' . $search_sql . '
			ORDER BY ' . $sql_sort_order;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('downloads', [
				'ICON_COPY' => '<i class="icon acp-icon acp-icon-copy fa-copy fa-fw" title="' . $this->user->lang['ACP_COPY_NEW'] . '"></i>',
				'TITLE' => $row['download_title'],
				'FILENAME' => $row['download_filename'],
				'DESC' => $this->renderer->render(html_entity_decode($row['download_desc'])),
				'VERSION' => $row['download_version'],
				'DL_COST' => ($row['cost_per_dl'] == 0 ? $this->user->lang['ACP_COST_FREE'] : $row['cost_per_dl']),
				'DL_CLICKS' => $row['download_count'],
				'SUB_DIR' => $row['cat_sub_dir'],
				'CATNAME' => $row['cat_name'],
				'U_COPY' => $this->u_action . '&amp;action=copy_new&amp;id=' . $row['download_id'],
				'U_EDIT' => $this->u_action . '&amp;action=edit&amp;id=' . $row['download_id'],
				'U_DEL' => $this->u_action . '&amp;action=delete&amp;id=' . $row['download_id'],
				'DL_IMAGE' => generate_board_url() . '/' . $eds_values['dm_eds_image_dir'] . '/' . $row['download_image'],
			]);
		}
		$this->db->sql_freeresult($result);

		$base_url = $this->u_action;

		// Disable pagination if search query is present
		if (!$search_query)
		{
			// Start pagination
			$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $total_downloads, $number, $start);
		}

		$this->template->assign_vars([
			'S_DOWNLOAD_ACTION' => $this->u_action,
			'S_SELECT_SORT_DIR' => $s_sort_dir,
			'S_SELECT_SORT_KEY' => $s_sort_key,
			'TOTAL_DOWNLOADS' => ($total_downloads == 1) ? $this->user->lang['ACP_SINGLE_DOWNLOAD'] : sprintf($this->user->lang['ACP_MULTI_DOWNLOAD'], $total_downloads),
			'U_NEW_DOWNLOAD' => $this->u_action . '&amp;action=new_download',
			'U_ACTION_SEARCH' => $this->u_action,
			'L_MODE_TITLE' => $lang_mode,
			'U_EDIT_ACTION' => $this->u_action,
			'S_DM_EDS_ALLOW_DL_IMG' => $eds_values['dm_eds_allow_dl_img'],
			'SEARCH_QUERY' => $search_query,
		]);
	}

	/**
	* Function for managing categories
	*/
	public function manage_cats()
	{
		$catrow = [];
		$parent_id = $this->request->variable('parent_id', 0);

		// Read out config values
		$eds_values = $this->functions->config_values();

		$this->template->assign_vars([
			'S_MODE_MANAGE' => true,
			'S_ACTION' => $this->u_action . '&amp;action=create&amp;parent_id=' . $parent_id,
			'U_ACTION_SEARCH' => $this->u_action,
		]);

		$dm_eds = [];
		$search_query = $this->request->variable('q', '', true);
		$search_sql = $this->functions->generate_search_cat_sql($search_query);

		$sql = 'SELECT *
			FROM ' . $this->dm_eds_cat_table . '
			' . $search_sql . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$dm_eds[] = $row;
		}

		for ($i = 0; $i < count($dm_eds); $i++)
		{
			$folder_fa_icon = ($dm_eds[$i]['left_id'] + 1 != $dm_eds[$i]['right_id']) ? '<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i title="' . $this->user->lang['ACP_CAT_SUB'] . '" class="fa fa-folder-open fa-stack-1x"></i></span>' : '<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i title="' . $this->user->lang['ACP_CAT'] . '" class="fa fa-folder fa-stack-1x"></i></span>';

			$cat_main_name = '';
			$dm_eds_nav = $this->functions->get_cat_branch($dm_eds[$i]['parent_id'], 'parents', 'descending');

			foreach ($dm_eds_nav as $row)
			{
				if ($row['cat_id'] == $dm_eds[$i]['parent_id'])
				{
					$cat_main_name = $this->user->lang['ACP_CAT_OF'] . '&nbsp;' . $row['cat_name'];
				}
			}

			$this->template->assign_block_vars('catrow', [
				'FOLDER_FA_ICON' => $folder_fa_icon,
				'U_CAT' => $this->u_action . '&amp;parent_id=' . $dm_eds[$i]['cat_id'],
				'CAT_NAME' => $dm_eds[$i]['cat_name'],
				'CAT_DESC' => $this->renderer->render(html_entity_decode($dm_eds[$i]['cat_desc'])),
				'CAT_SUBS' => ($dm_eds[$i]['left_id'] + 1 == $dm_eds[$i]['right_id'] && !$dm_eds[$i]['cat_id'] == $dm_eds[$i]['parent_id']) ? true : false,
				'CAT_SUBS_SHOW' => ($dm_eds[$i]['left_id'] + 1 != $dm_eds[$i]['right_id'] && $dm_eds[$i]['cat_id'] != $parent_id || $dm_eds[$i]['parent_id'] == 0) ? true : false,
				'CAT_MAIN_NAME' => $cat_main_name,
				'CAT_NAME_SHOW' => ($dm_eds[$i]['cat_name_show'] == 1) ? $this->user->lang['ACP_CAT_NAME_SHOW_YES'] : $this->user->lang['ACP_CAT_NAME_SHOW_NO'],
				'CAT_DESCRIPTION' => generate_text_for_display($dm_eds[$i]['cat_desc'], $dm_eds[$i]['cat_desc_uid'], $dm_eds[$i]['cat_desc_bitfield'], $dm_eds[$i]['cat_desc_options']),
				'U_MOVE_UP' => $this->u_action . '&amp;action=move&amp;move=move_up&amp;cat_id=' . $dm_eds[$i]['cat_id'],
				'U_MOVE_DOWN' => $this->u_action . '&amp;action=move&amp;move=move_down&amp;cat_id=' . $dm_eds[$i]['cat_id'],
				'U_EDIT' => $this->u_action . '&amp;action=edit&amp;cat_id=' . $dm_eds[$i]['cat_id'],
				'U_DELETE' => $this->u_action . '&amp;action=delete&amp;cat_id=' . $dm_eds[$i]['cat_id'],
				'IMAGE' => generate_board_url() . '/' . $eds_values['dm_eds_image_cat_dir'] . '/' . $dm_eds[$i]['category_image'],
			]);
		}

		$this->template->assign_vars([
			'S_DM_EDS_ALLOW_CAT_IMG' => $eds_values['dm_eds_allow_cat_img'],
			'S_DM_EDS' => $parent_id,
			'U_EDIT' => ($parent_id) ? $this->u_action . '&amp;action=edit&amp;cat_id=' . $parent_id : '',
			'U_DELETE' => ($parent_id) ? $this->u_action . '&amp;action=delete&amp;cat_id=' . $parent_id : '',
			'SEARCH_QUERY' => $search_query,
		]);
	}

	/**
	* Function for create a category
	*/
	public function create_cat()
	{
		// Read out config values
		$eds_values = $this->functions->config_values();

		$allowed_image_extensions = $this->functions->allowed_image_extensions();

		if ($this->request->is_set('submit'))
		{
			$dm_eds_data = [];
			$dm_eds_data = [
				'cat_name'			=> $this->request->variable('cat_name', '', true),
				'cat_sub_dir'		=> $this->request->variable('cat_sub_dir', ''),
				'parent_id'			=> $this->request->variable('parent_id', 0),
				'cat_parents'		=> $this->request->variable('cat_parents', 0),
				'cat_desc'			=> $this->request->variable('cat_desc', '', true),
				'cat_desc_options'	=> 7,
				'cat_name_show'		=> $this->request->variable('cat_name_show', 0),
				'enable_bbcode'		=> !$this->request->variable('disable_bbcode', false),
				'enable_smilies'	=> !$this->request->variable('disable_smilies', false),
				'enable_magic_url'	=> !$this->request->variable('disable_magic_url', false)
			];

			!$dm_eds_data['enable_bbcode'] || !$eds_values['dm_eds_allow_bbcodes'] ? $this->parser->disable_bbcodes() : $this->parser->enable_bbcodes();
			!$dm_eds_data['enable_smilies'] || !$eds_values['dm_eds_allow_smilies'] ? $this->parser->disable_smilies() : $this->parser->enable_smilies();
			!$dm_eds_data['enable_magic_url'] || !$eds_values['dm_eds_allow_magic_url'] ? $this->parser->disable_magic_url() : $this->parser->enable_magic_url();
			$category_description = $dm_eds_data['cat_desc'];
			$category_description = htmlspecialchars_decode($category_description, ENT_COMPAT);
			$dm_eds_data['cat_desc'] = $this->parser->parse($category_description);

			// Create variable for the cat_sub_dir name
			$cat_sub_dir_name = '';
			$cat_sub_dir_name = $dm_eds_data['cat_sub_dir'];

			// Check, if sub-dir is filled
			if (empty($cat_sub_dir_name))
			{
				trigger_error($this->user->lang['ACP_CAT_NAME_ERROR'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			// Do the check, if cat_sub_dir has valid characters only
			// Let's make an array of allowed characters
			$allowed = range('a', 'z'); //latin letters
			$allowed = array_merge($allowed, range(0, 9)); //numbers
			// Additional symbols (recommended only these two below)
			$allowed[] = '_';
			$allowed[] = '-';
			$allowed = implode($allowed);

			// Now split the new category name into single parts
			$new_dir_name = str_split($cat_sub_dir_name); //works only in PHP5!

			# Get an instance of the files upload class
			$upload = $this->files_factory->get('upload')
				-> set_max_filesize($eds_values['dm_eds_image_size'] * 1024)
				-> set_allowed_extensions($allowed_image_extensions);

			$upload_file = $upload->handle_upload('files.types.form', 'category_image');

			if (sizeof($upload_file->error) && $upload_file->get('uploadname'))
			{
				trigger_error(implode('<br />', $upload_file->error), E_USER_WARNING);
			}

			$upload_name = $this->request->variable('category_image', '');

			$upload_dir = $eds_values['dm_eds_image_cat_dir'];

			if ($upload_file->get('uploadname'))
			{
				$upload_file->clean_filename('unique_ext', 'dm_eds_cat_');
				$upload_file->move_file($upload_dir, true, true, 0644);
				$dm_eds_data['category_image'] = $upload_file->get('realname');
			}
			else
			{
				$dm_eds_data['category_image'] = 'default_cat.png';
			}

			// Check each character if it's allowed
			foreach ($new_dir_name as $var)
			{
				if (stristr($allowed, $var) === false)
				{
					trigger_error($this->user->lang['ACP_WRONG_CHAR'] . adm_back_link($this->u_action), E_USER_WARNING);
				}
			}

			// Check if sub dir name already exists
			$sql = 'SELECT *
				FROM ' . $this->dm_eds_cat_table . "
				WHERE cat_sub_dir LIKE '$cat_sub_dir_name'";
			$result= $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);

			if ($row)
			{
				trigger_error($this->user->lang['ACP_CAT_EXIST'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			if ($dm_eds_data['parent_id'])
			{
				$sql = 'SELECT left_id, right_id
					FROM ' . $this->dm_eds_cat_table . '
					WHERE cat_id = ' . $dm_eds_data['parent_id'];
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					trigger_error($this->user->lang['PARENT_NOT_EXIST'] . adm_back_link($this->u_action . '&amp;' . $this->parent_id), E_USER_WARNING);
				}

				$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
					SET left_id = left_id + 2, right_id = right_id + 2
					WHERE left_id > ' . $row['right_id'];
				$this->db->sql_query($sql);

				$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
					SET right_id = right_id + 2
					WHERE ' . $row['left_id'] . ' BETWEEN left_id AND right_id';
				$this->db->sql_query($sql);

				$dm_eds_data['left_id'] = $row['right_id'];
				$dm_eds_data['right_id'] = $row['right_id'] + 1;
			}
			else
			{
				$sql = 'SELECT MAX(right_id) AS right_id
					FROM ' . $this->dm_eds_cat_table;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				$dm_eds_data['left_id'] = $row['right_id'] + 1;
				$dm_eds_data['right_id'] = $row['right_id'] + 2;
			}

			$this->db->sql_query('INSERT INTO ' . $this->dm_eds_cat_table . ' ' . $this->db->sql_build_array('INSERT', $dm_eds_data));
			$this->cache->destroy('sql', $this->dm_eds_cat_table);

			// Log message
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CATEGORY_ADD', time(), [$cat_sub_dir_name]);

			// Check if created foldername already exists
			if (is_dir($this->root_path . 'ext/dmzx/downloadsystem/files/' . $cat_sub_dir_name))
			{
				trigger_error($this->user->lang['ACP_CAT_NEW_DONE'] . adm_back_link($this->u_action . '&amp;parent_id=' . $dm_eds_data['parent_id']));
			}
			else if (mkdir(($this->root_path . 'ext/dmzx/downloadsystem/files/' . $cat_sub_dir_name)))
			{
				if (copy(($this->root_path . 'ext/dmzx/downloadsystem/files/' .'index.htm'), ($this->root_path . 'ext/dmzx/downloadsystem/files/' . $cat_sub_dir_name . '/index.htm')))
				{
					trigger_error($this->user->lang['ACP_CAT_NEW_DONE'] . adm_back_link($this->u_action . '&amp;parent_id=' . $dm_eds_data['parent_id']));
				}
			}
		}

		$parent_options = $this->functions->make_cat_select($this->request->variable('parent_id', 0), false, false, false, false);
		$this->template->assign_vars([
			'S_MODE_CREATE'					=> true,
			'S_ACTION'						=> $this->u_action . '&amp;parent_id=' . $this->request->variable('parent_id', 0),
			'S_BBCODE_ENABLED'				=> !empty($dm_eds_data['enable_bbcode']) ? $dm_eds_data['enable_bbcode'] : 0,
			'S_SMILIES_ENABLED'				=> !empty($dm_eds_data['enable_smilies']) ? $dm_eds_data['enable_smilies'] : 0,
			'S_MAGIC_URL_ENABLED'			=> !empty($dm_eds_data['enable_magic_url']) ? $dm_eds_data['enable_magic_url'] : 0,
			'BBCODE_STATUS'					=> !empty($eds_values['dm_eds_allow_bbcodes']) ? $this->user->lang('BBCODE_IS_ON', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>') : $this->user->lang('BBCODE_IS_OFF', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>'),
			'SMILIES_STATUS'				=> !empty($eds_values['dm_eds_allow_smilies']) ? $this->user->lang('SMILIES_ARE_ON') : $this->user->lang('SMILIES_ARE_OFF'),
			'URL_STATUS'					=> !empty($eds_values['dm_eds_allow_magic_url']) ? $this->user->lang('URL_IS_ON') : $this->user->lang('URL_IS_OFF'),
			'S_PARENT_OPTIONS'				=> $parent_options,
			'CAT_NAME_SHOW'					=> $this->request->variable('cat_name_show', 1),
			'CAT_NAME_NO_SHOW'				=> $this->user->lang['ACP_SUB_NO_CAT'],
			'S_DL_CATEGORY_ADD'				=> true,
			'S_DM_EDS_ALLOW_CAT_IMG'		=> $eds_values['dm_eds_allow_cat_img'],
		]);
	}

	/**
	* Function for editing a category
	*/
	public function edit_cat()
	{
		if (!$cat_id = $this->request->variable('cat_id', 0))
		{
			trigger_error($this->user->lang['ACP_NO_CAT_ID'], E_USER_WARNING);
		}

		$changecatimage = $this->request->variable('changecatimage', '');

		// Read out config values
		$eds_values = $this->functions->config_values();

		$allowed_image_extensions = $this->functions->allowed_image_extensions();

		if ($this->request->is_set('submit'))
		{
			$dm_eds_data = [];
			$dm_eds_data = [
				'cat_name'					=> $this->request->variable('cat_name', '', true),
				'parent_id'					=> $this->request->variable('parent_id', 0),
				'cat_parents'				=> '',
				'cat_desc_options'			=> 7,
				'cat_desc'					=> $this->request->variable('cat_desc', '', true),
				'cat_name_show'				=> $this->request->variable('cat_name_show', 0),
				'enable_bbcode'				=> !$this->request->variable('disable_bbcode', false),
				'enable_smilies'			=> !$this->request->variable('disable_smilies', false),
				'enable_magic_url'			=> !$this->request->variable('disable_magic_url', false)
			];

			!$dm_eds_data['enable_bbcode'] || !$eds_values['dm_eds_allow_bbcodes'] ? $this->parser->disable_bbcodes() : $this->parser->enable_bbcodes();
			!$dm_eds_data['enable_smilies'] || !$eds_values['dm_eds_allow_smilies'] ? $this->parser->disable_smilies() : $this->parser->enable_smilies();
			!$dm_eds_data['enable_magic_url'] || !$eds_values['dm_eds_allow_magic_url'] ? $this->parser->disable_magic_url() : $this->parser->enable_magic_url();
			$category_description = $dm_eds_data['cat_desc'];
			$category_description = htmlspecialchars_decode($category_description, ENT_COMPAT);
			$dm_eds_data['cat_desc'] = $this->parser->parse($category_description);

			$row = $this->functions->get_cat_info($cat_id);

			if ($row['parent_id'] != $dm_eds_data['parent_id'])
			{
				//how many do we have to move and how far
				$moving_ids = ($row['right_id'] - $row['left_id']) + 1;
				$sql = 'SELECT MAX(right_id) AS right_id
					FROM ' . $this->dm_eds_cat_table;
				$result = $this->db->sql_query($sql);
				$highest = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);
				$moving_distance = ($highest['right_id'] - $row['left_id']) + 1;
				$stop_updating = $moving_distance + $row['left_id'];

				//update the moving download... move it to the end
				$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
					SET right_id = right_id + ' . $moving_distance . ',
						left_id = left_id + ' . $moving_distance . '
					WHERE left_id >= ' . $row['left_id'] . '
						AND right_id <= ' . $row['right_id'];
				$this->db->sql_query($sql);
				$new['left_id'] = $row['left_id'] + $moving_distance;
				$new['right_id'] = $row['right_id'] + $moving_distance;

				//close the gap, we got
				if ($dm_eds_data['parent_id'] == 0)
				{
					//we move to root
					//left_id
					$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
						SET left_id = left_id - ' . $moving_ids . '
						WHERE left_id >= ' . $row['left_id'];
					$this->db->sql_query($sql);

					//right_id
					$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
						SET right_id = right_id - ' . $moving_ids . '
						WHERE right_id >= ' . $row['left_id'];
					$this->db->sql_query($sql);
				}
				else
				{
					//close the gap
					//left_id
					$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
						SET left_id = left_id - ' . $moving_ids . '
						WHERE left_id >= ' . $row['left_id'] . '
							AND right_id <= ' . $stop_updating;
					$this->db->sql_query($sql);

					//right_id
					$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
						SET right_id = right_id - ' . $moving_ids . '
						WHERE right_id >= ' . $row['left_id'] . '
							AND right_id <= ' . $stop_updating;
					$this->db->sql_query($sql);

					//create new gap
					//need parent_information
					$parent = $this->functions->get_cat_info($dm_eds_data['parent_id']);
					//left_id
					$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
						SET left_id = left_id + ' . $moving_ids . '
						WHERE left_id >= ' . $parent['right_id'] . '
							AND right_id <= ' . $stop_updating;
					$this->db->sql_query($sql);

					//right_id
					$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
						SET right_id = right_id + ' . $moving_ids . '
						WHERE right_id >= ' . $parent['right_id'] . '
							AND right_id <= ' . $stop_updating;
					$this->db->sql_query($sql);

					//close the gap again
					//new parent right_id!!!
					$parent['right_id'] = $parent['right_id'] + $moving_ids;
					$move_back = ($new['right_id'] - $parent['right_id']) + 1;
					$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
						SET left_id = left_id - ' . $move_back . ',
							right_id = right_id - ' . $move_back . '
						WHERE left_id >= ' . $stop_updating;
					$this->db->sql_query($sql);
				}
			}

			if ($row['cat_name'] != $dm_eds_data['cat_name'])
			{
				// the forum name has changed, clear the parents list of all forums (for safety)
				$sql = 'UPDATE ' . $this->dm_eds_cat_table . "
					SET cat_parents = ''";
				$this->db->sql_query($sql);
			}

			# Get an instance of the files upload class
			$upload = $this->files_factory->get('upload')
				-> set_max_filesize($eds_values['dm_eds_image_size'] * 1024)
				-> set_allowed_extensions($allowed_image_extensions);

			$upload_file = $upload->handle_upload('files.types.form', 'category_image');

			if (sizeof($upload_file->error) && $upload_file->get('uploadname'))
			{
				trigger_error(implode('<br />', $upload_file->error), E_USER_WARNING);
			}

			$upload_dir = $eds_values['dm_eds_image_cat_dir'];

			if ($upload_file->get('uploadname') && $changecatimage != '')
			{
				$upload_file->clean_filename('unique_ext', 'dm_eds_cat_');
				$upload_file->move_file($upload_dir, true, true, 0644);
				$dm_eds_data['category_image'] = $upload_file->get('realname');
			}

			$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
				SET ' . $this->db->sql_build_array('UPDATE', $dm_eds_data) . '
				WHERE cat_id	= ' . (int) $cat_id;
			$this->db->sql_query($sql);
			$this->cache->destroy('sql', $this->dm_eds_cat_table);

			// Log message
			$this->log_message('LOG_CATEGORY_UPDATED', $dm_eds_data['cat_name'], 'ACP_CAT_EDIT_DONE');
		}

		$sql = 'SELECT *
			FROM ' . $this->dm_eds_cat_table . "
			WHERE cat_id = '$cat_id'";
		$result = $this->db->sql_query($sql);

		if ($this->db->sql_affectedrows($result) == 0)
		{
			trigger_error($this->user->lang['ACP_CAT_NOT_EXIST'], E_USER_WARNING);
		}

		$dm_eds_data = $this->db->sql_fetchrow($result);
		$dm_eds_desc_data = generate_text_for_edit($dm_eds_data['cat_desc'], $dm_eds_data['cat_desc_uid'], $dm_eds_data['cat_desc_options']);

		$parents_list = $this->functions->make_cat_select($dm_eds_data['parent_id'], $cat_id);

		// Has subcategories
		if (($dm_eds_data['left_id'] + 1) != $dm_eds_data['right_id'])
		{
			$subcategories = false;
		}
		else
		{
			$subcategories = true;
		}

		$upload_dir = $eds_values['dm_eds_image_cat_dir'];

		$this->template->assign_vars([
			'S_MODE_EDIT'					=> true,
			'S_ACTION'						=> $this->u_action . '&amp;action=edit&amp;cat_id=' . $cat_id,
			'S_PARENT_OPTIONS'				=> $parents_list,
			'CAT_NAME'						=> $dm_eds_data['cat_name'],
			'CATEGORY_IMAGE'				=> !empty($dm_eds_data['category_image']) ? $this->root_path . $upload_dir . '/' . $dm_eds_data['category_image'] : '',
			'CAT_DESC'						=> $dm_eds_desc_data['text'],
			'CAT_SUB_DIR'					=> $dm_eds_data['cat_sub_dir'],
			'S_BBCODE_ENABLED'				=> !empty($dm_eds_data['enable_bbcode']) ? $dm_eds_data['enable_bbcode'] : 0,
			'S_SMILIES_ENABLED'				=> !empty($dm_eds_data['enable_smilies']) ? $dm_eds_data['enable_smilies'] : 0,
			'S_MAGIC_URL_ENABLED'			=> !empty($dm_eds_data['enable_magic_url']) ? $dm_eds_data['enable_magic_url'] : 0,
			'BBCODE_STATUS'					=> !empty($eds_values['dm_eds_allow_bbcodes']) ? $this->user->lang('BBCODE_IS_ON', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>') : $this->user->lang('BBCODE_IS_OFF', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>'),
			'SMILIES_STATUS'				=> !empty($eds_values['dm_eds_allow_smilies']) ? $this->user->lang('SMILIES_ARE_ON') : $this->user->lang('SMILIES_ARE_OFF'),
			'URL_STATUS'					=> !empty($eds_values['dm_eds_allow_magic_url']) ? $this->user->lang('URL_IS_ON') : $this->user->lang('URL_IS_OFF'),
			'S_HAS_SUBCATS'					=> $subcategories,
			'S_MODE'						=> 'edit',
			'CAT_NAME_SHOW'					=> $dm_eds_data['cat_name_show'],
			'CAT_NAME_NO_SHOW'				=> $this->user->lang['ACP_SUB_NO_CAT'],
			'S_DM_EDS_ALLOW_CAT_IMG'		=> $eds_values['dm_eds_allow_cat_img'],
		]);
	}

	/**
	* Function for deleting a category
	*/
	public function delete_cat()
	{
		if (!$cat_id = $this->request->variable('cat_id', 0))
		{
			trigger_error($this->user->lang['ACP_NO_CAT_ID'], E_USER_WARNING);
		}
		else
		{
			$sql = 'SELECT *
				FROM ' . $this->dm_eds_cat_table . "
				WHERE cat_id = '$cat_id'";
			$result = $this->db->sql_query($sql);

			if ($this->db->sql_affectedrows($result) == 0)
			{
				trigger_error($this->user->lang['ACP_CAT_NOT_EXIST'], E_USER_WARNING);
			}
		}

		// Read out config values
		$eds_values = $this->functions->config_values();

		$catname = '';
		$sql = 'SELECT ec.*, COUNT(ed.download_id) AS downloads
			FROM ' . $this->dm_eds_cat_table . ' AS ec
			LEFT JOIN ' . $this->dm_eds_table . ' AS ed
				ON ec.cat_id = ed.download_cat_id
			WHERE ec.cat_id = ' . (int) $cat_id . '
			GROUP BY ec.cat_id';
		$result = $this->db->sql_query($sql);

		$subs_found = false;

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['cat_id'] == $cat_id)
			{
				$thiseds = $row;
				$subs_found = true;
			}
			else
			{
				$edsrow[] = $row;
			}

			$catname = $row['cat_name'];
		}

		if (confirm_box(true))
		{
			$dm_eds = $this->functions->get_cat_info($cat_id);
			$handle_subs = $this->request->variable('handle_subs', 0);
			$handle_downloads = $this->request->variable('handle_downloads', 0);

			if (($dm_eds['right_id'] - $dm_eds['left_id']) > 2)
			{
				//handle subs if there
				//we have to learn how to delete or move the subs
				if ($handle_subs >= 0)
				{
					trigger_error($this->user->lang['ACP_DELETE_SUB_CATS'] . adm_back_link($this->u_action), E_USER_WARNING);
				}
			}

			// Get cat directory name
			$sql = 'SELECT cat_sub_dir, cat_name, category_image
				FROM ' . $this->dm_eds_cat_table . '
				WHERE cat_id = ' . (int) $cat_id;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$sub_cat_dir = $row['cat_sub_dir'];
			$cat_name = $row['cat_name'];
			$category_image = $row['category_image'];
			$this->db->sql_freeresult($result);

			// Check if category has files
			$sql = ' SELECT COUNT(download_id) AS has_downloads
				FROM ' . $this->dm_eds_table . '
				WHERE download_cat_id = ' . (int) $cat_id;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$has_downloads = $row['has_downloads'];
			$this->db->sql_freeresult($result);

			if ($has_downloads > 0)
			{
				trigger_error($this->user->lang['ACP_DELETE_HAS_FILES'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			//reorder the other downloads
			//left_id
			$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
				SET left_id = left_id - 2
				WHERE left_id > ' . $dm_eds['left_id'];
			$this->db->sql_query($sql);

			//right_id
			$sql = 'UPDATE ' . $this->dm_eds_cat_table . '
				SET right_id = right_id - 2
				WHERE right_id > ' . $dm_eds['left_id'];
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->dm_eds_cat_table . "
				WHERE cat_id = '$cat_id'";
			$result = $this->db->sql_query($sql);
			$this->cache->destroy('sql', $this->dm_eds_cat_table);

			// Remove the folder and all of its content
			$this->remove_dir($sub_cat_dir);

			if ($this->filesystem->exists($this->root_path . $eds_values['dm_eds_image_cat_dir'] . '/' . $category_image))
			{
				if ($category_image != 'default_cat.png')
				{
					$this->filesystem->remove($this->root_path . $eds_values['dm_eds_image_cat_dir'] . '/' . $category_image);
				}
			}

			// Log message
			$this->log_message('LOG_CATEGORY_DELETED', $cat_name, 'ACP_CAT_DELETE_DONE');

			if ($this->request->is_ajax())
			{
				$json_response->send([
					'MESSAGE_TITLE'	=> $this->user->lang('INFORMATION'),
					'MESSAGE_TEXT'	=> $this->user->lang('ACP_DEL_CAT', $catname),
					'REFRESH_DATA'	=> [
						'time'	=> 3
					]
				]);
			}
		}
		else
		{
			confirm_box(false, $this->user->lang('ACP_DEL_CAT', $catname), build_hidden_fields([
				'cat_id'	=> $cat_id,
				'action'	=> 'delete',
				])
			);
		}
		redirect($this->u_action);

		if (!$subs_found)
		{
			trigger_error($this->user->lang['ACP_CAT_NOT_EXIST'], E_USER_WARNING);
		}
		$this->db->sql_freeresult($result);
	}

	/**
	* Function for moving a category to another position
	*/
	function move_cat()
	{
		if (!$cat_id = $this->request->variable('cat_id', 0))
		{
			trigger_error($this->user->lang['ACP_NO_CAT_ID'], E_USER_WARNING);
		}
		else
		{
			$sql = 'SELECT *
				FROM ' . $this->dm_eds_cat_table . "
				WHERE cat_id = '$cat_id'";
			$result = $this->db->sql_query($sql);

			if ($this->db->sql_affectedrows($result) == 0)
			{
				trigger_error($this->user->lang['ACP_CAT_NOT_EXIST'], E_USER_WARNING);
			}
		}
		$move = $this->request->variable('move', '', true);
		$moving = $this->functions->get_cat_info($cat_id);

		if ($this->request->is_ajax())
		{
			$json_response->send(['success' => ($moving !== false)]);
		}

		$sql = 'SELECT cat_id, left_id, right_id, cat_name
			FROM ' . $this->dm_eds_cat_table . "
			WHERE parent_id = {$moving['parent_id']}
				AND " . (($move == 'move_up') ? "right_id < {$moving['right_id']} ORDER BY right_id DESC" : "left_id > {$moving['left_id']} ORDER BY left_id ASC");
		$result = $this->db->sql_query_limit($sql, 1);

		$target = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$target = $row;
		}
		$this->db->sql_freeresult($result);

		if (!sizeof($target))
		{
			// The forum is already on top or bottom
			return false;
		}

		if ($move == 'move_up')
		{
			$left_id = $target['left_id'];
			$right_id = $moving['right_id'];

			$diff_up = $moving['left_id'] - $target['left_id'];
			$diff_down = $moving['right_id'] + 1 - $moving['left_id'];

			$move_up_left = $moving['left_id'];
			$move_up_right = $moving['right_id'];
		}
		else
		{
			$left_id = $moving['left_id'];
			$right_id = $target['right_id'];

			$diff_up = $moving['right_id'] + 1 - $moving['left_id'];
			$diff_down = $target['right_id'] - $moving['right_id'];

			$move_up_left = $moving['right_id'] + 1;
			$move_up_right = $target['right_id'];
		}

		// Now do the dirty job
		$sql = 'UPDATE ' . $this->dm_eds_cat_table . "
			SET left_id = left_id + CASE
				WHEN left_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
				ELSE {$diff_down}
			END,
			right_id = right_id + CASE
				WHEN right_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
				ELSE {$diff_down}
			END,
			cat_parents = ''
			WHERE
				left_id BETWEEN {$left_id} AND {$right_id}
				AND right_id BETWEEN {$left_id} AND {$right_id}";
		$this->db->sql_query($sql);
		$this->cache->destroy('sql', $this->dm_eds_cat_table);

		return $target['cat_name'];
	}

	/**
	* Function for removing a category and all its content
	*/
	public function remove_dir($selected_dir)
	{
		$current_dir = $this->ext_path_web . 'files/' . $selected_dir;
		$empty_dir = $this->ext_path_web . 'files/' . $selected_dir . '/';

		if ($dir = @opendir($current_dir))
		{
			while (($f = readdir($dir)) !== false)
			{
				if ($f > '0' and filetype($empty_dir.$f) == "file")
				{
					@unlink($empty_dir.$f);
				}
				else if ($f > '0' and filetype($empty_dir.$f) == "dir")
				{
					remove_dir($current_dir.$f."\\");
				}
			}
			closedir($dir);
			rmdir($current_dir);
		}
	}

	/**
	 * Log Message
	 *
	 * @return message
	 * @access private
	*/
	private function log_message($log_message, $title, $user_message)
	{
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_message, time(), [$title]);

		trigger_error($this->user->lang[$user_message] . adm_back_link($this->u_action));
	}

	/**
	* Set page url
	*
	* @param string $u_action Custom form action
	* @return null
	* @access public
	*/
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}
