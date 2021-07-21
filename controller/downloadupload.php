<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\controller;

use phpbb\exception\http_exception;
use dmzx\downloadsystem\core\functions;
use phpbb\textformatter\parser_interface;
use phpbb\textformatter\renderer_interface;
use phpbb\template\template;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\log\log_interface;
use phpbb\db\driver\driver_interface as db_interface;
use phpbb\controller\helper;
use phpbb\request\request_interface;
use phpbb\extension\manager;
use phpbb\path_helper;
use phpbb\files\factory;

class downloadupload
{
	/** @var functions */
	protected $functions;

	/** @var parser_interface */
	protected $parser;

	/** @var renderer_interface */
	protected $renderer;

	/** @var template */
	protected $template;

	/** @var user */
	protected $user;

	/** @var auth */
	protected $auth;

	/** @var log_interface */
	protected $log;

	/** @var db_interface */
	protected $db;

	/** @var helper */
	protected $helper;

	/** @var request_interface */
	protected $request;

	/** @var manager */
	protected $ext_manager;

	/** @var path_helper */
	protected $path_helper;

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

	/** @var factory */
	protected $files_factory;

	/**
	* Constructor
	*
	* @param functions						$functions
	* @param parser_interface				$parser
	* @param renderer_interface 			$renderer
	* @param template		 				$template
	* @param user							$user
	* @param auth							$auth
	* @param log_interface 					$log
	* @param db_interface					$db
	* @param helper		 					$helper
	* @param request_interface		 		$request
	* @param manager						$ext_manager
	* @param path_helper					$path_helper
	* @param string 						$php_ext
	* @param string 						$root_path
	* @param string 						$dm_eds_table
	* @param string 						$dm_eds_cat_table
	* @param factory						$files_factory
	*
	*/
	public function __construct(
		functions $functions,
		parser_interface $parser,
		renderer_interface $renderer,
		template $template,
		user $user,
		auth $auth,
		log_interface $log,
		db_interface $db,
		helper $helper,
		request_interface $request,
		manager $ext_manager,
		path_helper $path_helper,
		$php_ext,
		$root_path,
		$dm_eds_table,
		$dm_eds_cat_table,
		factory $files_factory = null
	)
	{
		$this->functions 			= $functions;
		$this->parser				= $parser;
		$this->renderer				= $renderer;
		$this->template 			= $template;
		$this->user 				= $user;
		$this->auth 				= $auth;
		$this->log 					= $log;
		$this->db 					= $db;
		$this->helper 				= $helper;
		$this->request 				= $request;
		$this->ext_manager	 		= $ext_manager;
		$this->path_helper	 		= $path_helper;
		$this->php_ext 				= $php_ext;
		$this->root_path 			= $root_path;
		$this->dm_eds_table 		= $dm_eds_table;
		$this->dm_eds_cat_table 	= $dm_eds_cat_table;
		$this->files_factory 		= $files_factory;
		$this->ext_path 			= $this->ext_manager->get_extension_path('dmzx/downloadsystem', true);
		$this->ext_path_web 		= $this->path_helper->update_web_root_path($this->ext_path);

		if (!function_exists('submit_post'))
		{
			include($this->root_path . 'includes/functions_posting.' . $this->php_ext);
		}
		if (!defined('PHPBB_USE_BOARD_URL_PATH'))
		{
			define('PHPBB_USE_BOARD_URL_PATH', true);
		}
	}

	public function handle_downloadsystemupload()
	{
		if (!$this->auth->acl_get('u_dm_eds_upload'))
		{
			throw new http_exception(401, 'EDS_NO_UPLOAD');
		}

		// Read out config values
		$eds_values = $this->functions->config_values();

		$id							= $this->request->variable('id', 0);
		$title						= $this->request->variable('title', '', true);
		$cat_name_show				= $this->request->variable('cat_name_show', 1);
		$filename					= $this->request->variable('filename', '', true);
		$desc						= $this->request->variable('desc', '', true);
		$dl_version					= $this->request->variable('dl_version', '', true);
		$costs_dl					= $this->request->variable('cost_per_dl', 0.00, true);
		$cat_option 				= $this->request->variable('parent', '', true);
		$ftp_upload					= $this->request->variable('ftp_upload', '', true);
		$enable_bbcode_file			= !$this->request->variable('disable_bbcode_file', false);
		$enable_smilies_file		= !$this->request->variable('disable_smilies_file', false);
		$enable_magic_url_file		= !$this->request->variable('disable_magic_url_file', false);

		$max_filesize = @ini_get('upload_max_filesize');
		$unit = 'MB';

		if (!empty($max_filesize))
		{
			$unit = strtolower(substr($max_filesize, -1, 1));
			$max_filesize = (int) $max_filesize;
			$unit = ($unit == 'k') ? 'KB' : (($unit == 'g') ? 'GB' : 'MB');
		}

		add_form_key('add_upload');

		$this->user->add_lang('posting');

		if ($this->request->is_set_post('submit'))
		{
			$filecheck = $multiplier = '';

			// Add allowed extensions
			$allowed_extensions = $this->functions->allowed_extensions();

			$fileupload = $this->files_factory->get('upload')
				->set_allowed_extensions($allowed_extensions);

			$target_folder = $this->request->variable('parent', 0);
			$upload_name = $this->request->variable('filename', '');

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
					meta_refresh(3, $this->helper->route('dmzx_downloadsystem_controller_upload'));
					throw new http_exception(400, 'EDS_NO_FILENAME');
				}

				if (file_exists($this->root_path . $upload_dir . '/' . $upload_file->get('uploadname')))
				{
					meta_refresh(3, $this->helper->route('dmzx_downloadsystem_controller_upload'));
					throw new http_exception(400, 'EDS_UPLOAD_FILE_EXISTS');
				}

				$upload_file->move_file($upload_dir, false, false, false);
				@chmod($this->ext_path_web . 'files/' . $upload_file->get('uploadname'), 0644);

				if (sizeof($upload_file->error) && $upload_file->get('uploadname'))
				{
					$upload_file->remove();
					meta_refresh(3, $this->helper->route('dmzx_downloadsystem_controller_upload'));

					trigger_error(implode('<br />', $upload_file->error));
				}

				// End the upload
				$filesize = @filesize($this->root_path . $upload_dir . '/' . $upload_file->get('uploadname'));
				$sql_ary = [
					'download_title'		=> $title,
					'download_desc'	 		=> $desc,
					'download_filename'		=> $upload_file->get('uploadname'),
					'download_version'		=> $dl_version,
					'download_cat_id'		=> $cat_option,
					'upload_time'			=> time(),
					'cost_per_dl'			=> $costs_dl,
					'last_changed_time'		=> time(),
					'filesize'				=> $filesize,
					'points_user_id'		=> $this->user->data['user_id'],
					'enable_bbcode_file'	=> $enable_bbcode_file,
					'enable_smilies_file'	=> $enable_smilies_file,
					'enable_magic_url_file'	=> $enable_magic_url_file,
					'download_image' 		=> 'default_dl.png',
				];

				!$sql_ary['enable_bbcode_file'] || !$eds_values['dm_eds_allow_bbcodes'] ? $this->parser->disable_bbcodes() : $this->parser->enable_bbcodes();
				!$sql_ary['enable_smilies_file'] || !$eds_values['dm_eds_allow_smilies'] ? $this->parser->disable_smilies() : $this->parser->enable_smilies();
				!$sql_ary['enable_magic_url_file'] || !$eds_values['dm_eds_allow_magic_url'] ? $this->parser->disable_magic_url() : $this->parser->enable_magic_url();
				$download_desc = $sql_ary['download_desc'];
				$download_desc = htmlspecialchars_decode($download_desc, ENT_COMPAT);
				$sql_ary['download_desc'] = $this->parser->parse($download_desc);

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
					throw new http_exception(400, 'EDS_FILE_TOO_BIG');
				}
			}

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

				$download_link = '[url=' . generate_board_url() . '/downloadsystemcat?id=' . $cat_option . ']' . $this->user->lang['EDS_CLICK'] . '[/url]';
				$download_subject = sprintf($this->user->lang['EDS_ANNOUNCE_TITLE'], $dl_title);

				$download_msg = sprintf($this->user->lang['EDS_ANNOUNCE_MSG'], $title, $desc, $cat_name, $download_link);

				$this->functions->create_announcement($download_subject, $download_msg, $eds_values['announce_forum']);
			}

			$this->db->sql_query('INSERT INTO ' . $this->dm_eds_table .' ' . $this->db->sql_build_array('INSERT', $sql_ary));
			// Log message
			$this->log_message('LOG_DOWNLOAD_ADD', $title, 'EDS_NEW_ADDED');
		}

		if ($this->auth->acl_get('a_'))
		{
			$sql_show_cat =	'';
		}
		else
		{
			$sql_show_cat = ' WHERE cat_name_show = ' . (int) $cat_name_show . '';
		}

		// Check if categories exists
		$sql = 'SELECT COUNT(cat_id) AS total_cats
			FROM ' . $this->dm_eds_cat_table . '
			' . $sql_show_cat;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$total_cats = $row['total_cats'];
		$this->db->sql_freeresult($result);

		if ($total_cats <= 0)
		{
			throw new http_exception(400, 'EDS_NO_CAT_IN_UPLOAD');
		}

		$sql = 'SELECT *
			FROM ' . $this->dm_eds_cat_table . '
			' . $sql_show_cat . '
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
			if ($key ??= $row2['download_cat_id'])
			{
				$cat_options .= '<option value="' . $value['cat_id'] . '" selected="selected">' . $value['cat_title'] . '</option>';
			}
			else
			{
				$cat_options .= '<option value="' . $value['cat_id'] . '">' . $value['cat_title'] . '</option>';
			}
		}

		$form_enctype = (@ini_get('file_uploads') == '0' || strtolower(@ini_get('file_uploads')) == 'off') ? '' : ' enctype="multipart/form-data"';

		$this->template->assign_vars([
			'ID'						=> $id,
			'TITLE'						=> $title,
			'DESC'						=> $desc,
			'FILENAME'					=> $filename,
			'DL_VERSION'				=> $dl_version,
			'PARENT_OPTIONS'			=> $cat_options,
			'ALLOWED_SIZE'				=> sprintf($this->user->lang['EDS_NEW_DOWNLOAD_SIZE'], $max_filesize, $unit),
			'S_FORM_ENCTYPE'			=> $form_enctype,
			'S_BBCODE_ENABLED_FILE'		=> !empty($row['enable_bbcode_file']) ? $row['enable_bbcode_file'] : 0,
			'S_SMILIES_ENABLED_FILE'	=> !empty($row['enable_smilies_file']) ? $row['enable_smilies_file'] : 0,
			'S_MAGIC_URL_ENABLED_FILE'	=> !empty($row['enable_magic_url_file']) ? $row['enable_magic_url_file'] : 0,
			'BBCODE_STATUS'				=> !empty($eds_values['dm_eds_allow_bbcodes']) ? $this->user->lang('BBCODE_IS_ON', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>') : $this->user->lang('BBCODE_IS_OFF', '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>'),
			'SMILIES_STATUS'			=> !empty($eds_values['dm_eds_allow_smilies']) ? $this->user->lang('SMILIES_ARE_ON') : $this->user->lang('SMILIES_ARE_OFF'),
			'URL_STATUS'				=> !empty($eds_values['dm_eds_allow_magic_url']) ? $this->user->lang('URL_IS_ON') : $this->user->lang('URL_IS_OFF'),
			'S_DL_CATEGORY_ADD'			=> true,
		]);

		// Build navigation link
		$this->template->assign_block_vars('navlinks', [
			'FORUM_NAME'	=> $this->user->lang('EDS_UPLOAD_SECTION'),
			'U_VIEW_FORUM'	=> $this->helper->route('dmzx_downloadsystem_controller_upload'),
		]);

		$this->functions->assign_authors();
		$this->template->assign_var('DOWNLOADSYSTEM_FOOTER_VIEW', true);

		// Send all data to the template file
		return $this->helper->render('upload_body.html', $this->user->lang('EDS_TITLE') . ' &bull; ' . $this->user->lang('EDS_UPLOAD_SECTION'));
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

		meta_refresh(3, $this->helper->route('dmzx_downloadsystem_controller_upload'));

		trigger_error($this->user->lang[$user_message]);
	}
}
