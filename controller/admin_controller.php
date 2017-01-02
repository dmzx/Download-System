<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\controller;

class admin_controller
{
	/** @var \dmzx\downloadsystem\core\functions */
	protected $functions;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\cache\service */
	protected $cache;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var ContainerBuilder */
	protected $phpbb_container;

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

	protected $dm_eds_config_table;

	/** @var \phpbb\files\factory */
	protected $files_factory;

	/**
	* Constructor
	*
	* @param \dmzx\downloadsystem\core\functions						$functions
	* @param \phpbb\template\template		 							$template
	* @param \phpbb\user												$user
	* @param \phpbb\log													$log
	* @param \phpbb\cache\service										$cache
	* @param \phpbb\db\driver\driver_interface							$db
	* @param \phpbb\request\request		 								$request
	* @param \phpbb\pagination											$pagination
	* @param \phpbb\extension\manager									$ext_manager
	* @param \phpbb\path_helper											$path_helper
	* @param string 													$php_ext
	* @param string 													$root_path
	* @param string 													$dm_eds_table
	* @param string 													$dm_eds_cat_table
	* @param string 													$dm_eds_config_table
	* @param \phpbb\files\factory										$files_factory
	*
	*/
	public function __construct(
		\dmzx\downloadsystem\core\functions $functions,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\log\log $log,
		\phpbb\cache\service $cache,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\pagination $pagination,
		\phpbb\extension\manager $ext_manager,
		\phpbb\path_helper $path_helper,
		$php_ext, $root_path,
		$dm_eds_table,
		$dm_eds_cat_table,
		$dm_eds_config_table,
		\phpbb\files\factory $files_factory = null)
	{
		$this->functions 			= $functions;
		$this->template 			= $template;
		$this->user 				= $user;
		$this->log 					= $log;
		$this->cache 				= $cache;
		$this->db 					= $db;
		$this->request 				= $request;
		$this->pagination 			= $pagination;
		$this->ext_manager	 		= $ext_manager;
		$this->path_helper	 		= $path_helper;
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
		if (!class_exists('parse_message'))
		{
			include($this->root_path . 'includes/message_parser.' . $this->php_ext);
		}
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
			$sql_ary = array (
				'pagination_acp'		=> $this->request->variable('pagination_acp', 0),
				'pagination_user'		=> $this->request->variable('pagination_user', 0),
				'announce_enable'		=> $this->request->variable('announce_enable', 0),
				'announce_forum'		=> $this->request->variable('announce_forum', 0),
				'announce_lock_enable'	=> $this->request->variable('announce_lock_enable', 0),
				'pagination_downloads'	=> $this->request->variable('pagination_downloads', 0),
			);

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

				if ( empty($check_id) )
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
			$this->template->assign_vars(array(
				'PAGINATION_ACP'		=> $eds_values['pagination_acp'],
				'PAGINATION_USER'		=> $eds_values['pagination_user'],
				'ANNOUNCE_ENABLE'		=> $eds_values['announce_enable'],
				'ANNOUNCE_FORUM'		=> $eds_values['announce_forum'],
				'ANNOUNCE_LOCK'			=> $eds_values['announce_lock_enable'],
				'PAGINATION_DOWNLOADS'	=> $eds_values['pagination_downloads'],
				'U_BACK'				=> $this->u_action,
				'U_ACTION'				=> $form_action,
			));
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

		$max_filesize = @ini_get('upload_max_filesize');
		$unit = 'MB';

		if (!empty($max_filesize))
		{
			$unit = strtolower(substr($max_filesize, -1, 1));
			$max_filesize = (int) $max_filesize;

			$unit = ($unit == 'k') ? 'KB' : (($unit == 'g') ? 'GB' : 'MB');
		}

		$this->template->assign_vars(array(
			'ID'				=> $id,
			'TITLE'				=> $title,
			'DESC'				=> $desc,
			'FILENAME'			=> $filename,
			'DL_VERSION'		=> $dl_version,
			'FTP_UPLOAD'		=> $ftp_upload,
			'PARENT_OPTIONS'	=> $cat_options,
			'ALLOWED_SIZE'		=> sprintf($this->user->lang['ACP_NEW_DOWNLOAD_SIZE'], $max_filesize, $unit),
			'U_BACK'			=> $this->u_action,
			'U_ACTION'			=> $form_action,
			'L_MODE_TITLE'		=> $lang_mode,
		));
	}

	public function copy_new()
	{
		// Read out config values
		$eds_values = $this->functions->config_values();

		$form_action = $this->u_action. '&amp;action=add_new';
		$lang_mode = $this->user->lang['ACP_NEW_DOWNLOAD'];

		$action = $this->request->variable('action', '');
		$action = ($this->request->is_set('submit') && !$this->request->is_set('id')) ? 'add' : $action;

		$this->user->add_lang('posting');

		$id	= $this->request->variable('id', 0);

		$sql = 'SELECT *
			FROM ' . $this->dm_eds_table . '
			WHERE download_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		decode_message($row['download_desc'], $row['bbcode_uid']);
		$copy_title = $row['download_title'];
		$copy_version = $row['download_version'];
		$copy_desc = $row['download_desc'];
		$copy_costs_dl = $row['cost_per_dl'];
		$this->db->sql_freeresult($result);

		$id			= $this->request->variable('id', 0);
		$title		= $this->request->variable('title', '', true);
		$filename	= $this->request->variable('filename', '', true);
		$desc		= $this->request->variable('desc', '', true);
		$dl_version	= $this->request->variable('dl_version', '', true);
		$costs_dl	= $this->request->variable('cost_per_dl', 0.00);
		$ftp_upload = $this->request->variable('ftp_upload', '', true);

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

		$max_filesize = @ini_get('upload_max_filesize');
		$unit = 'MB';

		if (!empty($max_filesize))
		{
			$unit = strtolower(substr($max_filesize, -1, 1));
			$max_filesize = (int) $max_filesize;

			$unit = ($unit == 'k') ? 'KB' : (($unit == 'g') ? 'GB' : 'MB');
		}

		$this->template->assign_vars(array(
			'ID'				=> $id,
			'TITLE'				=> $copy_title,
			'DESC'				=> $copy_desc,
			'FILENAME'			=> $filename,
			'FTP_UPLOAD'		=> $ftp_upload,
			'DL_VERSION'		=> $copy_version,
			'PARENT_OPTIONS'	=> $cat_options,
			'ALLOWED_SIZE'		=> sprintf($this->user->lang['ACP_NEW_DOWNLOAD_SIZE'], $max_filesize, $unit),
			'U_BACK'			=> $this->u_action,
			'U_ACTION'			=> $form_action,
			'L_MODE_TITLE'		=> $lang_mode,
		));
	}

	public function edit()
	{
		// Edit an existing download
		$form_action = $this->u_action. '&amp;action=update';
		$lang_mode = $this->user->lang['ACP_EDIT_DOWNLOADS'];

		$action = $this->request->variable('action', '');
		$action = ($this->request->is_set('submit') && !$this->request->is_set('id')) ? 'add' : $action;

		$id = $this->request->variable('id', '');

		$sql = 'SELECT d.*, c.*
			FROM ' . $this->dm_eds_table . ' d
				LEFT JOIN ' . $this->dm_eds_cat_table . ' c
				ON d.download_cat_id = c.cat_id
			WHERE download_id = ' . (int) $id;
		$result = $this->db->sql_query_limit($sql,1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		decode_message($row['download_desc'], $row['bbcode_uid']);
		$download_id = $row['download_id'];
		$download_version = $row['download_version'];

		$sql = 'SELECT *
			FROM ' . $this->dm_eds_cat_table . '
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

		$this->template->assign_vars(array(
			'ID'				=> $download_id,
			'TITLE'				=> $row['download_title'],
			'DESC'				=> $row['download_desc'],
			'FILENAME'			=> $row['download_filename'],
			'CATNAME'			=> $row['cat_name'],
			'DL_VERSION'		=> $download_version,
			'PARENT_OPTIONS'	=> $cat_options,
			'ALLOWED_SIZE'		=> sprintf($this->user->lang['ACP_NEW_DOWNLOAD_SIZE'], $max_filesize, $unit),
			'U_ACTION'			=> $form_action,
			'L_MODE_TITLE'		=> $lang_mode,
		));
	}

	public function add_new()
	{
		$filecheck = $multiplier = '';

		$this->user->add_lang('posting');

		// Read out config values
		$eds_values = $this->functions->config_values();

		$id					= $this->request->variable('id', 0);
		$title				= $this->request->variable('title', '', true);
		$filename			= $this->request->variable('filename', '', true);
		$desc				= $this->request->variable('desc', '', true);
		$dl_version			= $this->request->variable('dl_version', '', true);
		$costs_dl			= $this->request->variable('cost_per_dl', 0.00, true);
		$cat_option 		= $this->request->variable('parent', '', true);
		$upload_time 		= time();
		$last_changed_time 	= time();
		$uid = $bitfield = $options = '';
		$allow_bbcode 		= $allow_urls = $allow_smilies = true;
		$ftp_upload			= $this->request->variable('ftp_upload', '', true);

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
			$upload_file = (isset($this->files_factory)) ? $fileupload->handle_upload('files.types.form', 'filename') : $fileupload->form_upload('filename');

			if (!$upload_file->get('uploadname'))
			{
				trigger_error($this->user->lang['ACP_NO_FILENAME'] . adm_back_link($this->u_action), E_USER_WARNING);//continue;
			}

			if (file_exists($this->root_path . $upload_dir . '/' . $upload_file->get('uploadname')))
			{
				trigger_error($this->user->lang['ACP_UPLOAD_FILE_EXISTS'] . adm_back_link($this->u_action), E_USER_WARNING);//continue;
			}

			$upload_file->move_file($upload_dir, false, false, false);
			@chmod($this->ext_path_web . 'files/' . $upload_file->get('uploadname'), 0644);

			if (sizeof($upload_file->error) && $upload_file->get('uploadname'))
			{
				$upload_file->remove();
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
				'upload_time'		=> $upload_time,
				'cost_per_dl'		=> $costs_dl,
				'last_changed_time'	=> $last_changed_time,
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

			if ($filesize	> ($max_filesize * $multiplier))
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
			$sql_ary = array(
				'download_title'	=> $title,
				'download_desc'	 	=> $desc,
				'download_filename'	=> $ftp_upload,
				'download_version'	=> $dl_version,
				'download_cat_id'	=> $cat_option,
				'upload_time'		=> $upload_time,
				'cost_per_dl'		=> $costs_dl,
				'last_changed_time'	=> $last_changed_time,
				'bbcode_uid'		=> $uid,
				'bbcode_bitfield'	=> $bitfield,
				'bbcode_options'	=> $options,
				'filesize'			=> $filesize,
				'points_user_id'	=> $this->user->data['user_id'],
			);
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

	public function update()
	{
		// Change an existing download
		$filecheck = $filecheck_current = $new_filename = '';

		// Read out config values
		$eds_values = $this->functions->config_values();

		$this->user->add_lang('posting');

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

		$title 				= $this->request->variable('title', '', true);
		$v_cat_id			= $this->request->variable('parent', '');
		$dl_version			= $this->request->variable('dl_version', '', true);
		$costs_dl			= $this->request->variable('cost_per_dl', 0.00);
		$last_changed_time 	= time();
		$desc 				= $this->request->variable('desc', '', true);
		$announce_up 		= $this->request->variable('announce_up', '');
		$ftp_upload			= $this->request->variable('ftp_upload', '', true);

		$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
		$allow_bbcode = $allow_urls = $allow_smilies = true;

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
			$upload_file = (isset($this->files_factory)) ? $fileupload->handle_upload('files.types.form', 'filename') : $fileupload->form_upload('filename');

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

				$upload_file->move_file($upload_dir, false, false, false);
				@chmod($this->ext_path_web . 'files/' . $upload_file->get('uploadname'), 0644);

				if (sizeof($upload_file->error) && $upload_file->get('uploadname'))
				{
					$upload_file->remove();
					trigger_error(implode('<br />', $upload_file->error));
				}

				$filesize = @filesize($this->root_path . $upload_dir . '/' . $new_filename);
			}

			$sql_ary = array(
				'download_title'	=> $title,
				'download_version'	=> $dl_version,
				'download_desc'		=> $desc,
				'download_filename'	=> $new_filename,
				'download_cat_id'	=> $v_cat_id,
				'cost_per_dl'		=> $costs_dl,
				'last_changed_time' => $last_changed_time,
				'bbcode_uid'		=> $uid,
				'bbcode_bitfield'	=> $bitfield,
				'bbcode_options'	=> $options,
				'filesize'			=> $filesize,
				'points_user_id'	=> $this->user->data['user_id'],
			);

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

							if ($this->files_factory !== null)
							{
								$download_msg = sprintf($this->user->lang['ACP_ANNOUNCE_UP_MSG'], $title, $desc, $cat_name, $download_link);
							}
							else
							{
								$download_msg = sprintf($this->user->lang['ACP_ANNOUNCE_UP_MSG'], $title, generate_text_for_display($desc, $uid, $bitfield, $options), $cat_name, $download_link);

							}
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

						if ($this->files_factory !== null)
						{
							$download_msg = sprintf($this->user->lang['ACP_ANNOUNCE_UP_MSG'], $title, $desc, $cat_name, $download_link);
						}
						else
						{
							$download_msg = sprintf($this->user->lang['ACP_ANNOUNCE_UP_MSG'], $title, generate_text_for_display($desc, $uid, $bitfield, $options), $cat_name, $download_link);

						}
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
			$sql_ary = array(
				'download_title'	=> $title,
				'download_desc'	 	=> $desc,
				'download_filename'	=> $ftp_upload,
				'download_version'	=> $dl_version,
				'download_cat_id'	=> $cat_option,
				'upload_time'		=> $upload_time,
				'cost_per_dl'		=> $costs_dl,
				'last_changed_time'	=> $last_changed_time,
				'bbcode_uid'		=> $uid,
				'bbcode_bitfield'	=> $bitfield,
				'bbcode_options'	=> $options,
				'filesize'			=> $filesize,
				'points_user_id'	=> $this->user->data['user_id'],
			);
		}
	}

	public function delete()
	{
		$id = $this->request->variable('id', '');

		// Delete an existing download
		if (confirm_box(true))
		{
			$sql = 'SELECT c.cat_sub_dir, d.download_filename
				FROM ' . $this->dm_eds_cat_table . ' c
				LEFT JOIN ' . $this->dm_eds_table . ' d
					ON c.cat_id = d.download_cat_id
				WHERE d.download_id = ' . (int) $id;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$cat_dir = $row['cat_sub_dir'];
			$file_name = $row['download_filename'];
			$this->db->sql_freeresult($result);

			$delete_file = $this->ext_path_web . 'files/' . $cat_dir .'/' . $file_name;
			@unlink($delete_file);

			$sql = 'DELETE FROM ' . $this->dm_eds_table . '
				WHERE download_id = '. (int) $id;
			$this->db->sql_query($sql);

			// Log message
			$this->log_message('LOG_DOWNLOAD_DELETED', $file_name, 'ACP_DOWNLOAD_DELETED');
		}
		else
		{
			confirm_box(false, $this->user->lang['ACP_REALLY_DELETE'], build_hidden_fields(array(
				'download_id'	=> $id,
				'action'	=> 'delete',
				))
			);
		}
		redirect($this->u_action);
	}

	public function display_downloads()
	{
		$this->user->add_lang('posting');

		// Setup message parser
		$this->message_parser = new \parse_message();

		$action 		= $this->request->is_set_post('submit');
		$id				= $this->request->variable('id', 0);
		$form_action 	= $this->u_action. '&amp;action=add';
		$lang_mode 		= $this->user->lang['ACP_ADD'];

		// Read out config values
		$eds_values = $this->functions->config_values();

		$start	= $this->request->variable('start', 0);
		$number	= $eds_values['pagination_acp'];

		$this->template->assign_vars(array(
			'BASE'	=> $this->u_action,
		));

		$sort_days	= $this->request->variable('st', 0);
		$sort_key	= $this->request->variable('sk', 'download_title');
		$sort_dir	= $this->request->variable('sd', 'ASC');
		$limit_days = array(0 => $this->user->lang['ACP_ALL_DOWNLOADS'], 1 => $this->user->lang['1_DAY'], 7 => $this->user->lang['7_DAYS'], 14 => $this->user->lang['2_WEEKS'], 30 => $this->user->lang['1_MONTH'], 90 => $this->user->lang['3_MONTHS'], 180 => $this->user->lang['6_MONTHS'], 365 => $this->user->lang['1_YEAR']);

		$sort_by_text = array('t' => $this->user->lang['ACP_SORT_TITLE'], 'c' => $this->user->lang['ACP_SORT_CAT']);
		$sort_by_sql = array('t' => 'download_title', 'c' => 'cat_name');

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
		$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');

		// Total number of downloads
		$sql = 'SELECT COUNT(download_id) AS total_downloads
			FROM ' . $this->dm_eds_table;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$total_downloads = $row['total_downloads'];
		$this->db->sql_freeresult($result);

		// List all downloads
		$sql = 'SELECT d.*, c.*
			FROM ' . $this->dm_eds_table . ' d
			LEFT JOIN ' . $this->dm_eds_cat_table . ' c
				ON d.download_cat_id = c.cat_id
			ORDER BY '. $sql_sort_order;
		$result = $this->db->sql_query_limit($sql, $number, $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->message_parser->message = $row['download_desc'];
			$this->message_parser->bbcode_bitfield = $row['bbcode_bitfield'];
			$this->message_parser->bbcode_uid = $row['bbcode_uid'];
			$allow_bbcode = $allow_magic_url = $allow_smilies = true;
			$this->message_parser->format_display($allow_bbcode, $allow_magic_url, $allow_smilies);

			$this->template->assign_block_vars('downloads', array(
				'ICON_COPY'		=> '<img src="' . $this->root_path . 'adm/images/file_new.gif" alt="' . $this->user->lang['ACP_COPY_NEW'] . '" title="' . $this->user->lang['ACP_COPY_NEW'] . '" />',
				'TITLE'			=> $row['download_title'],
				'FILENAME'		=> $row['download_filename'],
				'DESC'			=> $this->message_parser->message,
				'VERSION'		=> $row['download_version'],
				'DL_COST'		=> ($row['cost_per_dl'] == 0 ? $this->user->lang['ACP_COST_FREE'] : $row['cost_per_dl']),
				'SUB_DIR'		=> $row['cat_sub_dir'],
				'CATNAME'		=> $row['cat_name'],
				'U_COPY'		=> $this->u_action . '&amp;action=copy_new&amp;id=' .$row['download_id'],
				'U_EDIT'		=> $this->u_action . '&amp;action=edit&amp;id=' .$row['download_id'],
				'U_DEL'			=> $this->u_action . '&amp;action=delete&amp;id=' .$row['download_id'],
			));
		}
		$this->db->sql_freeresult($result);

		$base_url = $this->u_action;
		//Start pagination
		$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $total_downloads, $number, $start);

		$this->template->assign_vars(array(
			'S_DOWNLOAD_ACTION' => $this->u_action,
			'S_SELECT_SORT_DIR'	=> $s_sort_dir,
			'S_SELECT_SORT_KEY'	=> $s_sort_key,
			'TOTAL_DOWNLOADS'	=> ($total_downloads == 1) ? $this->user->lang['ACP_SINGLE_DOWNLOAD'] : sprintf($this->user->lang['ACP_MULTI_DOWNLOAD'], $total_downloads),
			'U_NEW_DOWNLOAD'	=> $this->u_action . '&amp;action=new_download',
			'L_MODE_TITLE'		=> $lang_mode,
			'U_EDIT_ACTION'		=> $this->u_action,
		));
	}

	/**
	* Function for managing categories
	*/
	public function manage_cats()
	{
		$catrow = array();
		$parent_id = $this->request->variable('parent_id', 0);
		$this->template->assign_vars(array(
			'S_MODE_MANAGE'	=> true,
			'S_ACTION'		=> $this->u_action . '&amp;action=create&amp;parent_id=' . $parent_id,
		));
		if (!$parent_id)
		{
			$navigation = $this->user->lang['ACP_CAT_INDEX'];
		}
		else
		{
			$navigation = '<a href="' . $this->u_action . '">' . $this->user->lang['ACP_CAT_INDEX'] . '</a>';
			$dm_eds_nav = $this->functions->get_cat_branch($parent_id, 'parents', 'descending');
			foreach ($dm_eds_nav as $row)
			{
				if ($row['cat_id'] == $parent_id)
				{
					$navigation .= ' -&gt; ' . $row['cat_name'];
				}
				else
				{
					$navigation .= ' -&gt; <a href="' . $this->u_action . '&amp;parent_id=' . $row['cat_id'] . '">' . $row['cat_name'] . '</a>';
				}
			}
		}
		$dm_eds = array();
		$sql = 'SELECT *
			FROM ' . $this->dm_eds_cat_table . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$dm_eds[] = $row;
		}

		for ($i = 0; $i < count($dm_eds); $i++)
		{
			$folder_image = ($dm_eds[$i]['left_id'] + 1 != $dm_eds[$i]['right_id']) ? '<img src="images/icon_subfolder.gif" alt="' . $this->user->lang['SUBFORUM'] . '" />' : '<img src="images/icon_folder.gif" alt="' . $this->user->lang['FOLDER'] . '" />';
			$url = $this->u_action . "&amp;parent_id=$parent_id&amp;cat_id={$dm_eds[$i]['cat_id']}";

			$this->template->assign_block_vars('catrow', array(
				'FOLDER_IMAGE'			=> $folder_image,
				'U_CAT'					=> $this->u_action . '&amp;parent_id=' . $dm_eds[$i]['cat_id'],
				'CAT_NAME'				=> $dm_eds[$i]['cat_name'],
				'CAT_SUBS'				=> ($dm_eds[$i]['left_id'] + 1 == $dm_eds[$i]['right_id'] && !$dm_eds[$i]['cat_id'] == $dm_eds[$i]['parent_id']) ? true : false,
				'CAT_SUBS_SHOW'			=> ($dm_eds[$i]['left_id'] + 1 != $dm_eds[$i]['right_id'] && $dm_eds[$i]['cat_id'] != $parent_id	|| $dm_eds[$i]['parent_id'] == 0) ? true : false,
				'CAT_NAME_SHOW'			=> ($dm_eds[$i]['cat_name_show'] == 1) ? $this->user->lang['ACP_CAT_NAME_SHOW_YES'] : $this->user->lang['ACP_CAT_NAME_SHOW_NO'],
				'CAT_DESCRIPTION'		=> generate_text_for_display($dm_eds[$i]['cat_desc'], $dm_eds[$i]['cat_desc_uid'], $dm_eds[$i]['cat_desc_bitfield'], $dm_eds[$i]['cat_desc_options']),
				'U_MOVE_UP'				=> $this->u_action . '&amp;action=move&amp;move=move_up&amp;cat_id=' . $dm_eds[$i]['cat_id'],
				'U_MOVE_DOWN'			=> $this->u_action . '&amp;action=move&amp;move=move_down&amp;cat_id=' . $dm_eds[$i]['cat_id'],
				'U_EDIT'				=> $this->u_action . '&amp;action=edit&amp;cat_id=' . $dm_eds[$i]['cat_id'],
				'U_DELETE'				=> $this->u_action . '&amp;action=delete&amp;cat_id=' . $dm_eds[$i]['cat_id'],
			));
		}

		$this->template->assign_vars(array(
			'NAVIGATION'		=> $navigation,
			'S_DM_EDS'			=> $parent_id,
			'U_EDIT'			=> ($parent_id) ? $this->u_action . '&amp;action=edit&amp;cat_id=' . $parent_id : '',
			'U_DELETE'			=> ($parent_id) ? $this->u_action . '&amp;action=delete&amp;cat_id=' . $parent_id : '',
		));
	}

	/**
	* Function for create a category
	*/
	public function create_cat()
	{
		if ($this->request->is_set('submit'))
		{
			$dm_eds_data = array();
			$dm_eds_data = array(
				'cat_name'			=> $this->request->variable('cat_name', '', true),
				'cat_sub_dir'		=> $this->request->variable('cat_sub_dir', ''),
				'parent_id'			=> $this->request->variable('parent_id', 0),
				'cat_parents'		=> $this->request->variable('cat_parents', 0),
				'cat_desc'			=> $this->request->variable('cat_desc', '', true),
				'cat_desc_options'	=> 7,
				'cat_name_show'		=> $this->request->variable('cat_name_show', 0),
			);

			generate_text_for_storage($dm_eds_data['cat_desc'], $dm_eds_data['cat_desc_uid'], $dm_eds_data['cat_desc_bitfield'], $dm_eds_data['cat_desc_options'], $this->request->variable('desc_parse_bbcode', false), $this->request->variable('desc_parse_urls', false), $this->request->variable('desc_parse_smilies', false));

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

			// Check each character if it's allowed
			foreach ($new_dir_name as $var)
			{
				if (stristr($allowed, $var) === false)
				{
					trigger_error($this->user->lang['ACP_WRONG_CHAR'] . adm_back_link($this->u_action), E_USER_WARNING);
				}
			}

			// Check if sub dir name already exists
			$sql = 'SELECT * FROM ' . $this->dm_eds_cat_table . "
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
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CATEGORY_ADD', time(), array($cat_sub_dir_name));

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
		$this->template->assign_vars(array(
			'S_MODE_CREATE'				=> true,
			'S_ACTION'					=> $this->u_action . '&amp;parent_id=' . $this->request->variable('parent_id', 0),
			'S_DESC_BBCODE_CHECKED'		=> true,
			'S_DESC_SMILIES_CHECKED'	=> true,
			'S_DESC_URLS_CHECKED'		=> true,
			'S_PARENT_OPTIONS'			=> $parent_options,
			'CAT_NAME_SHOW'				=> $this->request->variable('cat_name_show', 1),
			'CAT_NAME_NO_SHOW'			=> $this->user->lang['ACP_SUB_NO_CAT'],
		));
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

		if ($this->request->is_set('submit'))
		{
			$dm_eds_data = array();
			$dm_eds_data = array(
				'cat_name'						=> $this->request->variable('cat_name', '', true),
				'parent_id'						=> $this->request->variable('parent_id', 0),
				'cat_parents'					=> '',
				'cat_desc_options'				=> 7,
				'cat_desc'						=> $this->request->variable('cat_desc', '', true),
				'cat_name_show'					=> $this->request->variable('cat_name_show', 0),
			);
			generate_text_for_storage($dm_eds_data['cat_desc'], $dm_eds_data['cat_desc_uid'], $dm_eds_data['cat_desc_bitfield'], $dm_eds_data['cat_desc_options'], $this->request->variable('desc_parse_bbcode', false), $this->request->variable('desc_parse_urls', false), $this->request->variable('desc_parse_smilies', false));
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

		$this->template->assign_vars(array(
			'S_MODE_EDIT'				=> true,
			'S_ACTION'					=> $this->u_action . '&amp;action=edit&amp;cat_id=' . $cat_id,
			'S_PARENT_OPTIONS'			=> $parents_list,
			'CAT_NAME'					=> $dm_eds_data['cat_name'],
			'CAT_DESC'					=> $dm_eds_desc_data['text'],
			'CAT_SUB_DIR'				=> $dm_eds_data['cat_sub_dir'],
			'S_DESC_BBCODE_CHECKED'		=> ($dm_eds_desc_data['allow_bbcode']) ? true : false,
			'S_DESC_SMILIES_CHECKED'	=> ($dm_eds_desc_data['allow_smilies']) ? true : false,
			'S_DESC_URLS_CHECKED'		=> ($dm_eds_desc_data['allow_urls']) ? true : false,
			'S_HAS_SUBCATS'				=> $subcategories,
			'S_MODE'					=> 'edit',
			'CAT_NAME_SHOW'				=> $dm_eds_data['cat_name_show'],
			'CAT_NAME_NO_SHOW'			=> $this->user->lang['ACP_SUB_NO_CAT'],
		));
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

		if ($this->request->is_set('submit'))
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
			$sql = 'SELECT cat_sub_dir, cat_name
				FROM ' . $this->dm_eds_cat_table . '
				WHERE cat_id = ' . (int) $cat_id;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$sub_cat_dir = $row['cat_sub_dir'];
			$cat_name = $row['cat_name'];
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

			// Log message
			$this->log_message('LOG_CATEGORY_DELETED', $cat_name, 'ACP_CAT_DELETE_DONE');
		}

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

		if (!$subs_found)
		{
			trigger_error($this->user->lang['ACP_CAT_NOT_EXIST'], E_USER_WARNING);
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'S_MODE_DELETE'				=> true,
			'S_CAT_ACTION'				=> $this->u_action . '&amp;action=delete&amp;dm_eds_id=' . $cat_id,
			'CAT_DELETE'				=> sprintf($this->user->lang['ACP_DEL_CAT'], $catname),
			'S_PARENT_OPTIONS'			=> $this->functions->make_cat_select($thiseds['parent_id'], $cat_id),
			'S_HAS_CHILDREN'			=> ($thiseds['left_id'] + 1 != $thiseds['right_id']) ? true : false,
			'S_HAS_DOWNLOADS'			=> ($thiseds['downloads'] > 0) ? true : false,
			'CAT_NAME'					=> $catname,
			'CAT_DESC'					=> generate_text_for_display($thiseds['cat_desc'], $thiseds['cat_desc_uid'], $thiseds['cat_desc_bitfield'], $thiseds['cat_desc_options']),
			'S_MOVE_DM_EDS_OPTIONS'		=> $this->functions->make_cat_select(false, $cat_id),
			'S_MOVE_IMAGE_OPTIONS'		=> $this->functions->make_cat_select(false, $cat_id, true),
		));
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

		$sql = 'SELECT cat_id, left_id, right_id
			FROM ' . $this->dm_eds_cat_table . "
			WHERE parent_id = {$moving['parent_id']}
				AND " . (($move == 'move_up') ? "right_id < {$moving['right_id']} ORDER BY right_id DESC" : "left_id > {$moving['left_id']} ORDER BY left_id ASC");
		$result = $this->db->sql_query_limit($sql, 1);

		$target = array();
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
	//	redirect($this->u_action . '&amp;parent_id=' . $moving['parent_id']);
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
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_message, time(), array($title));

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
