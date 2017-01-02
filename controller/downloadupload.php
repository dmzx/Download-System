<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\controller;

use phpbb\exception\http_exception;

class downloadupload
{
	/** @var \dmzx\downloadsystem\core\functions */
	protected $functions;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\extension\manager "Extension Manager" */
	protected $ext_manager;

	/** @var \phpbb\path_helper */
	protected $path_helper;

	/** @var string */
	protected $php_ext;

	/** @var string phpBB root path */
	protected $root_path;

	/**
	* The database tables
	*
	* @var string
	*/
	protected $dm_eds_table;

	protected $dm_eds_cat_table;

	/** @var \phpbb\files\factory */
	protected $files_factory;

	/**
	* Constructor
	*
	* @param \dmzx\downloadsystem\core\functions						$functions
	* @param \phpbb\template\template		 							$template
	* @param \phpbb\user												$user
	* @param \phpbb\auth\auth											$auth
	* @param \phpbb\log													$log
	* @param \phpbb\db\driver\driver_interface							$db
	* @param \phpbb\controller\helper		 							$helper
	* @param \phpbb\request\request		 								$request
	* @param \phpbb\extension\manager									$ext_manager
	* @param \phpbb\path_helper											$path_helper
	* @param string 													$php_ext
	* @param string 													$root_path
	* @param string 													$dm_eds_table
	* @param string 													$dm_eds_cat_table
	* @param \phpbb\files\factory										$files_factory
	*
	*/
	public function __construct(
		\dmzx\downloadsystem\core\functions $functions,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\log\log $log,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\request\request $request,
		\phpbb\extension\manager $ext_manager,
		\phpbb\path_helper $path_helper,
		$php_ext, $root_path,
		$dm_eds_table,
		$dm_eds_cat_table,
		\phpbb\files\factory $files_factory = null)
	{
		$this->functions 			= $functions;
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

		$id				= $this->request->variable('id', 0);
		$title			= $this->request->variable('title', '', true);
		$cat_name_show	= $this->request->variable('cat_name_show', 1);
		$filename		= $this->request->variable('filename', '', true);
		$desc			= $this->request->variable('desc', '', true);
		$dl_version		= $this->request->variable('dl_version', '', true);
		$costs_dl		= $this->request->variable('cost_per_dl', 0.00, true);
		$cat_option 	= $this->request->variable('parent', '', true);
		$ftp_upload		= $this->request->variable('ftp_upload', '', true);

		$uid = $bitfield = $options = '';
		$allow_bbcode = $allow_urls = $allow_smilies = true;

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

			if ($this->files_factory !== null)
			{
				$fileupload = $this->files_factory->get('upload')
					->set_allowed_extensions($allowed_extensions);
			}
			else
			{
				generate_text_for_storage($desc, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

				if (!class_exists('\fileupload'))
				{
					include($this->root_path . 'includes/functions_upload.' . $this->php_ext);
				}
				$fileupload = new \fileupload();
				$fileupload->fileupload('', $allowed_extensions);
			}

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
				$upload_file = (isset($this->files_factory)) ? $fileupload->handle_upload('files.types.form', 'filename') : $fileupload->form_upload('filename');

				if (!$upload_file->get('uploadname'))
				{
					meta_refresh(3, $this->helper->route('dmzx_downloadsystem_controller_upload'));
					throw new http_exception(400, 'ACP_NO_FILENAME');
				}

				if (file_exists($this->root_path . $upload_dir . '/' . $upload_file->get('uploadname')))
				{
					meta_refresh(3, $this->helper->route('dmzx_downloadsystem_controller_upload'));
					throw new http_exception(400, 'ACP_UPLOAD_FILE_EXISTS');
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
				$sql_ary = array(
					'download_title'	=> $title,
					'download_desc'	 	=> $desc,
					'download_filename'	=> $upload_file->get('uploadname'),
					'download_version'	=> $dl_version,
					'download_cat_id'	=> $cat_option,
					'upload_time'		=> time(),
					'cost_per_dl'		=> $costs_dl,
					'last_changed_time'	=> time(),
					'bbcode_uid'		=> $uid,
					'bbcode_bitfield'	=> $bitfield,
					'bbcode_options'	=> $options,
					'filesize'			=> $filesize,
					'points_user_id'	=> $this->user->data['user_id'],
				);

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
					throw new http_exception(400, 'ACP_FILE_TOO_BIG');
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
				$download_link = '[url=' . generate_board_url() . '/downloadsystemcat?id=' . $cat_option . ']' . $this->user->lang['ACP_CLICK'] . '[/url]';
				$download_subject = sprintf($this->user->lang['ACP_ANNOUNCE_TITLE'], $dl_title);

				if ($this->files_factory !== null)
				{
					$download_msg = sprintf($this->user->lang['ACP_ANNOUNCE_MSG'], $title, $desc, $cat_name, $download_link);
				}
				else
				{
					$download_msg = sprintf($this->user->lang['ACP_ANNOUNCE_MSG'], $title, generate_text_for_display($desc, $uid, $bitfield, $options), $cat_name, $download_link);
				}
				$this->functions->create_announcement($download_subject, $download_msg, $eds_values['announce_forum']);
			}
			$this->db->sql_query('INSERT INTO ' . $this->dm_eds_table .' ' . $this->db->sql_build_array('INSERT', $sql_ary));
			// Log message
			$this->log_message('LOG_DOWNLOAD_ADD', $title, 'ACP_NEW_ADDED');
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
		$cats = array();

		while ($row2 = $this->db->sql_fetchrow($result))
		{
			$cats[$row2['cat_id']] = array(
				'cat_title'	=> $row2['cat_name'],
				'cat_id'	=> $row2['cat_id'],
			);
		}
		$this->db->sql_freeresult($result);

		$cat_options = '';
		foreach ($cats as $key => $value)
		{
			if ($key == $row2['download_cat_id'])
			{
				$cat_options .= '<option value="' . $value['cat_id'] . '" selected="selected">' . $value['cat_title'] . '</option>';
			}
			else
			{
				$cat_options .= '<option value="' . $value['cat_id'] . '">' . $value['cat_title'] . '</option>';
			}
		}

		$form_enctype = (@ini_get('file_uploads') == '0' || strtolower(@ini_get('file_uploads')) == 'off') ? '' : ' enctype="multipart/form-data"';

		$this->template->assign_vars(array(
			'ID'				=> $id,
			'TITLE'				=> $title,
			'DESC'				=> $desc,
			'FILENAME'			=> $filename,
			'DL_VERSION'		=> $dl_version,
			'PARENT_OPTIONS'	=> $cat_options,
			'ALLOWED_SIZE'		=> sprintf($this->user->lang['EDS_NEW_DOWNLOAD_SIZE'], $max_filesize, $unit),
			'S_FORM_ENCTYPE'	=> $form_enctype,
		));

		// Build navigation link
		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->user->lang('EDS_UPLOAD_SECTION'),
			'U_VIEW_FORUM'	=> $this->helper->route('dmzx_downloadsystem_controller_upload'),
		));

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
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_message, time(), array($title));

		meta_refresh(3, $this->helper->route('dmzx_downloadsystem_controller_upload'));

		trigger_error($this->user->lang[$user_message]);
	}
}
