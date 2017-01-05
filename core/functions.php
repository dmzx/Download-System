<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\core;

class functions
{
	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var \phpbb\extension\manager */
	protected $extension_manager;

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

	/**
	* Constructor
	*
	* @param \phpbb\template\template		 	$template
	* @param \phpbb\user						$user
	* @param \phpbb\db\driver\driver_interface	$db
	* @param \phpbb\controller\helper		 	$helper
	* @param \phpbb\request\request		 		$request
	* @param \phpbb\config\config				$config
	* @param \phpbb\pagination					$pagination
	* @param \phpbb\extension\manager 			$extension_manager
	* @param									$php_ext
	* @param									$root_path
	* @param									$dm_eds_table
	* @param									$dm_eds_cat_table
	* @param									$dm_eds_config_table
	*
	*/
	public function __construct(
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\request\request $request,
		\phpbb\config\config $config,
		\phpbb\pagination $pagination,
		\phpbb\extension\manager $extension_manager,
		$php_ext, $root_path,
		$dm_eds_table,
		$dm_eds_cat_table,
		$dm_eds_config_table)
	{
		$this->template 			= $template;
		$this->user 				= $user;
		$this->db 					= $db;
		$this->helper 				= $helper;
		$this->request 				= $request;
		$this->config 				= $config;
		$this->pagination 			= $pagination;
		$this->extension_manager	= $extension_manager;
		$this->php_ext 				= $php_ext;
		$this->root_path 			= $root_path;
		$this->dm_eds_table 		= $dm_eds_table;
		$this->dm_eds_cat_table 	= $dm_eds_cat_table;
		$this->dm_eds_config_table 	= $dm_eds_config_table;

		if (!function_exists('submit_post'))
		{
			include($this->root_path . 'includes/functions_posting.' . $this->php_ext);
		}
		if (!class_exists('parse_message'))
		{
			include($this->root_path . 'includes/message_parser.' . $this->php_ext);
		}
		if (!defined('PHPBB_USE_BOARD_URL_PATH'))
		{
			define('PHPBB_USE_BOARD_URL_PATH', true);
		}
	}

	/**
	* Create the select categories list
	*/
	public function make_cat_select($select_id = false, $ignore_id = false, $dm_video = false, $ignore_acl = false, $ignore_nonpost = false, $ignore_emptycat = true, $only_acl_post = false, $return_array = false)
	{
		// No permissions yet
		$acl = ($ignore_acl) ? '' : (($only_acl_post) ? 'f_post' : array('f_list', 'a_forum', 'a_forumadd', 'a_forumdel'));

		// This query is the same as the jumpbox query
		$sql = 'SELECT cat_id, cat_name, parent_id, left_id, right_id
			FROM ' . $this->dm_eds_cat_table . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql, 600);

		$right = 0;
		$padding_store = array('0' => '');
		$padding = '';
		$forum_list = ($return_array) ? array() : '';

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['left_id'] < $right)
			{
				$padding .= '&nbsp; &nbsp;';
				$padding_store[$row['parent_id']] = $padding;
			}
			else if ($row['left_id'] > $right + 1)
			{
				$padding = (isset($padding_store[$row['parent_id']])) ? $padding_store[$row['parent_id']] : '';
			}

			$right = $row['right_id'];
			$disabled = false;

			if (((is_array($ignore_id) && in_array($row['cat_id'], $ignore_id)) || $row['cat_id'] == $ignore_id) || ($row['parent_id']))
			{
				$disabled = true;
			}

			if ($return_array)
			{
				$selected = (is_array($select_id)) ? ((in_array($row['cat_id'], $select_id)) ? true : false) : (($row['cat_id'] == $select_id) ? true : false);
				$forum_list[$row['cat_id']] = array_merge(array('padding' => $padding, 'selected' => ($selected && !$disabled), 'disabled' => $disabled), $row);
			}
			else
			{
				$selected = (is_array($select_id)) ? ((in_array($row['cat_id'], $select_id)) ? ' selected="selected"' : '') : (($row['cat_id'] == $select_id) ? ' selected="selected"' : '');
				$forum_list .= '<option value="' . $row['cat_id'] . '"' . (($disabled) ? ' disabled="disabled" class="disabled-option"' : $selected) . '>' . $padding . $row['cat_name'] . '</option>';
			}
		}
		$this->db->sql_freeresult($result);
		unset($padding_store);

		return $forum_list;
	}

	/**
	* Get the category details
	*/
	public function get_cat_infos($cat_id)
	{
		$sql = 'SELECT *
			FROM ' . $this->dm_eds_cat_table . "
			WHERE cat_id = $cat_id";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			trigger_error("Cat #$cat_id does not exist", E_USER_ERROR);
		}

		return $row;
	}

	/**
	* Get the category branch
	*/
	public function get_cat_branch($cat_id, $type = 'all', $order = 'descending', $include_cat = true)
	{
		switch ($type)
		{
			case 'parents':
				$condition = 'a1.left_id BETWEEN a2.left_id AND a2.right_id';
			break;

			case 'children':
				$condition = 'a2.left_id BETWEEN a1.left_id AND a1.right_id';
			break;

			default:
				$condition = 'a2.left_id BETWEEN a1.left_id AND a1.right_id OR a1.left_id BETWEEN a2.left_id AND a2.right_id';
			break;
		}

		$rows = array();

		$sql = 'SELECT a2.*
			FROM ' . $this->dm_eds_cat_table . ' a1
			LEFT JOIN ' . $this->dm_eds_cat_table . " a2 ON ($condition)
			WHERE a1.cat_id = $cat_id
				ORDER BY a2.left_id " . (($order == 'descending') ? 'ASC' : 'DESC');
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!$include_cat && $row['cat_id'] == $cat_id)
			{
				continue;
			}

			$rows[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	* Returns cat parents as an array
	*/
	public function get_cat_parents(&$cat_data)
	{
		$cat_parents = array();
		if ($cat_data['parent_id'] > 0)
		{
			if ($cat_data['cat_parents'] == '')
			{
				$sql = 'SELECT cat_id, cat_name
					FROM ' . $this->dm_eds_cat_table . '
					WHERE left_id < ' . $cat_data['left_id'] . '
						AND right_id > ' . $cat_data['right_id'] . '
					ORDER BY left_id ASC';
				$result = $this->db->sql_query($sql);
				while ($row = $this->db->sql_fetchrow($result))
				{
					$row['cat_type'] = 1;
					$cat_parents[$row['cat_id']] = array($row['cat_name'], (int) $row['cat_type']);
				}
				$this->db->sql_freeresult($result);
				$cat_data['cat_parents'] = serialize($cat_parents);
				$sql = 'UPDATE ' . $this->dm_eds_cat_table . "
					SET cat_parents = '" . $this->db->sql_escape($cat_data['cat_parents']) . "'
					WHERE parent_id = " . $cat_data['parent_id'];
				$this->db->sql_query($sql);
			}
			else
			{
				return;
			}
		}
		return $cat_parents;
	}

	public function get_cat_info($cat_id)
	{
		$sql = 'SELECT *
			FROM ' . $this->dm_eds_cat_table . '
			WHERE cat_id = ' . (int) $cat_id;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$cat_data = $row;
		}
		return $cat_data;
	}

	/**
	* Generate the navigation bar
	*/
	public function generate_cat_nav(&$cat_data)
	{
		$parent_cat_id = $this->request->variable('id', 0);

		// Get category parents
		$cat_parents = $this->get_cat_parents($cat_data);

		// Build navigation link
		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->user->lang('EDS_DOWNLOADS'),
			'U_VIEW_FORUM'	=> $this->helper->route('dmzx_downloadsystem_controller'),
		));

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $cat_data['cat_name'],
			'U_VIEW_FORUM'	=> $this->helper->route('dmzx_downloadsystem_controller_cat', array('id' => $parent_cat_id)),
		));

		if (!empty($cat_parents))
		{
			foreach ($cat_parents as $parent_cat_id => $parent_data)
			{
				list ($parent_name, $parent_type) = array_values($parent_data);
				$this->template->assign_block_vars('navlinks', array(
					'FORUM_NAME'	=> $parent_name,
					'U_VIEW_FORUM'	=> $this->helper->route('dmzx_downloadsystem_controller_cat', array('id' => $parent_cat_id)),
				));
			}
		}
		return;
	}

	/**
	* Generate the sub categories list
	*/
	public function generate_cat_list($cat_id)
	{
		$start = $this->request->variable('start', 0);

		// Setup message parser
		$this->message_parser = new \parse_message();

		// Read out config values
		$eds_values = $this->config_values();

		$pagination_downloads = $eds_values['pagination_downloads'];

		// pagination value for categories
		$dls = $pagination_downloads;

		// Total number of category
		$sql = 'SELECT COUNT(cat_id) AS total_cat
			FROM ' . $this->dm_eds_cat_table . '
			WHERE parent_id = ' . (int) $cat_id;
		$result = $this->db->sql_query($sql, 60);
		$row = $this->db->sql_fetchrow($result);
		$total_cat = $row['total_cat'];
		$this->db->sql_freeresult($result);

		// Total number of subcategory
		$sql = 'SELECT COUNT(cat_id) AS total_sub_cat
			FROM ' . $this->dm_eds_cat_table . '
			WHERE parent_id > 0';
		$result = $this->db->sql_query($sql, 60);
		$row = $this->db->sql_fetchrow($result);
		$total_sub_cat = $row['total_sub_cat'];
		$this->db->sql_freeresult($result);

		// Select cat name
		$sql = 'SELECT cat_name
			FROM ' . $this->dm_eds_cat_table. '
			WHERE cat_id = ' . (int) $cat_id;
		$result = $this->db->sql_query($sql, 60);
		$row = $this->db->sql_fetchrow($result);
		$cat_name = $row['cat_name'];
		$this->db->sql_freeresult($result);

		// Check if there are downloads
		if ($total_cat == 0)
		{
			$this->template->assign_vars(array(
				'CAT_NAME'		=> $cat_name,
				'S_NO_CAT'		=> true,
				'MAIN_LINK'		=> $this->helper->route('dmzx_downloadsystem_controller'),
				'U_BACK'		=> append_sid("{$this->root_path}index.$this->php_ext"),
			));
		}
		else
		{
			$sql = 'SELECT bc.*, bd.*, COUNT(bd.download_id) AS number_downloads, MAX(bd.last_changed_time) AS last_download
				FROM ' . $this->dm_eds_cat_table . ' bc
				LEFT JOIN ' . $this->dm_eds_cat_table . ' bc2
					ON ( bc2.left_id < bc.right_id
						AND bc2.left_id > bc.left_id
						AND bc2.cat_id = ' . (int) $cat_id . ' )
				LEFT JOIN ' . $this->dm_eds_table . ' bd
					ON ( bd.download_cat_id = bc.cat_id
						OR bd.download_cat_id = bc2.cat_id	)
				WHERE bc.parent_id = ' . (int) $cat_id . '
				GROUP BY bc.cat_id
				ORDER BY bc.left_id ASC';
			$result = $this->db->sql_query_limit($sql, $dls, $start, 60);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$row['last_download'] = ($row['last_download']) ? $row['last_download'] : 0;
				$subcats = $last_download_id = $last_download_title = $last_download_chg_time = $last_download = $downloads = $download = $last_download_name = $download_version = $download_title = $download_count = $upload_time = $last_changed_time = '';

				// Do we have sub categories?
				if (($row['left_id'] + 1) != $row['right_id'])
				{
					$sql2 = 'SELECT bc.*, bd.*, COUNT(bd.download_id) AS number_downloads_files
						FROM ' . $this->dm_eds_cat_table . ' bc
						LEFT JOIN ' . $this->dm_eds_table . ' bd
							ON ( bd.download_cat_id = bc.cat_id )
						WHERE bc.parent_id = ' . $row['cat_id'] . '
						GROUP BY bc.cat_id
						ORDER BY bc.left_id ASC';
					$result2 = $this->db->sql_query($sql2, 60);

					while ($row2 = $this->db->sql_fetchrow($result2))
					{
						$number_downloads_files = ($row2['number_downloads_files'] == 1) ? $this->user->lang['EDS_SUBCAT_FILE'] : sprintf($this->user->lang['EDS_SUBCAT_FILES'], $row2['number_downloads_files']);

						$subcats .= ($subcats) ? ', ' : '';
						$subcats .= '<a class="subforum ' . (((isset($read_info[$row2['cat_id']]) ? $read_info[$row2['cat_id']] : 0) && ($this->user->data['user_id'] != ANONYMOUS)) ? 'unread' : 'read') . '" href="';
						$subcats .= $this->helper->route('dmzx_downloadsystem_controller_cat', array('id' =>	$row2['cat_id']));
						$subcats .= '">' . censor_text($row2['cat_name']) . '</a> <span class="small"><em>(' . $number_downloads_files . ')</em></span>';
					}
					$this->db->sql_freeresult($result2);

					$l_subcats = $this->user->lang['EDS_SUB_CAT'];
					if ($row['left_id'] + 3 != $row['right_id'])
					{
						$l_subcats = $this->user->lang['EDS_SUB_CATS'];
					}
				}
				else
				{
					$l_subcats = '';
				}

				$board_url = generate_board_url() . '/';
				$folder_image = (($row['left_id'] + 1) != $row['right_id']) ? '<img src="'. $board_url. 'adm/images/icon_subfolder.gif" alt="' . $this->user->lang['SUBFORUM'] . '" />' : '<img src="'. $board_url. 'adm/images/icon_folder.gif" alt="' . $this->user->lang['FOLDER'] . '" />';

				if ($row['last_download'])
				{
					$sql2 = 'SELECT *
						FROM ' . $this->dm_eds_table . '
						WHERE last_changed_time = ' . $row['last_download'];
					$result2 = $this->db->sql_query($sql2, 20);

					while ($row2 = $this->db->sql_fetchrow($result2))
					{
						$last_download_name = $row2['download_title'];
						$last_download_version = $row2['download_version'];
						$last_download_count = '<span style="font-weight: bold;">' . $row2['download_count'] . '</span>';
						$last_download_up_date = $row2['upload_time'];
						$last_download_chg_time = $this->user->format_date($row2['last_changed_time']);
					}

					if (!empty($last_download_version))
					{
						$downloads = $last_download_name . ' v' . $last_download_version;
					}
					else
					{
						$downloads = $last_download_name;
					}

					$last_download = sprintf($this->user->lang['EDS_LAST_DOWNLOAD'], $downloads, $last_download_count, $last_download_chg_time);
				}
				else
				{
					$last_download = $this->user->lang['EDS_NO_DOWNLOADS'];
				}

				$this->message_parser->message = $row['cat_desc'];
				$this->message_parser->bbcode_bitfield = $row['bbcode_bitfield'];
				$this->message_parser->bbcode_uid = $row['bbcode_uid'];
				$allow_bbcode = $allow_magic_url = $allow_smilies = true;
				$this->message_parser->format_display($allow_bbcode, $allow_magic_url, $allow_smilies);

				// Send the results to the template
				$this->template->assign_block_vars('catrow', array(
					'LAST_DOWNLOAD'			=> $last_download,
					'NUMBER_DOWNLOADS'		=> $row['number_downloads'],
					'CAT_NAME'				=> censor_text($row['cat_name']),
					'U_EDS_CAT'				=> $this->helper->route('dmzx_downloadsystem_controller_cat', array('id' =>	$row['cat_id'])),
					'CAT_DESC'				=> generate_text_for_display($row['cat_desc'], $row['cat_desc_uid'], $row['cat_desc_bitfield'], $row['cat_desc_options']),
					'CAT_FOLDER_IMG_SRC'	=> $folder_image,
					'SUBCATS'				=> ($subcats) ? $l_subcats . ': <span style="font-weight: bold;">' . $subcats . '</span>' : '',
				));
			}

			$this->db->sql_freeresult($result);

			$pagination_url = $this->helper->route('dmzx_downloadsystem_controller');

			//Start pagination
			$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_cat, $dls, $start);

			$this->template->assign_vars(array(
				'LAST_POST_IMG'			=> $this->user->img('icon_topic_latest', 'VIEW_LATEST_POST'),
				'EDS_CATEGORIES'		=> ($total_cat == 1) ? sprintf($this->user->lang['EDS_CAT'], $total_cat) : sprintf($this->user->lang['EDS_CATS'], $total_cat),
				'EDS_SUB_CAT_SHOW'		=> ($total_sub_cat == 0) ? false : true,
				'EDS_SUB_CATEGORIES'	=> ($total_sub_cat == 1) ? sprintf($this->user->lang['EDS_SUB_CATEGORY'], $total_sub_cat) : sprintf($this->user->lang['EDS_SUB_CATEGORIES'], $total_sub_cat),
			));
		}
	}

	/**
	* Assign authors
	*/
	public function assign_authors()
	{
		$md_manager = $this->extension_manager->create_extension_metadata_manager('dmzx/downloadsystem', $this->template);
		$meta = $md_manager->get_metadata();
		$author_names = array();
		$author_homepages = array();

		foreach (array_slice($meta['authors'], 0, 1) as $author)
		{
			$author_names[] = $author['name'];
			$author_homepages[] = sprintf('<a href="%1$s" title="%2$s">%2$s</a>', $author['homepage'], $author['name']);
		}
		$this->template->assign_vars(array(
			'DOWNLOADSYSTEM_DISPLAY_NAME'		=> $meta['extra']['display-name'],
			'DOWNLOADSYSTEM_AUTHOR_NAMES'		=> implode(' &amp; ', $author_names),
			'DOWNLOADSYSTEM_AUTHOR_HOMEPAGES'	=> implode(' &amp; ', $author_homepages),
		));

		return;
	}

	/**
	* Read out config values
	*/
	public function config_values()
	{
		$sql = 'SELECT *
			FROM ' . $this->dm_eds_config_table;
		$result = $this->db->sql_query($sql);
		$eds_values = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $eds_values;
	}

	/**
	* Post download announcement
	*/
	public function create_announcement($download_subject, $download_msg, $forum_id)
	{
		// Read out config values
		$eds_values = $this->config_values();

		$lock = $eds_values['announce_lock_enable'];

		$this->message_parser = new \parse_message();

		$subject =	$download_subject;
		$text =	$download_msg;

		// Do not try to post message if subject or text is empty
		if (empty($subject) || empty($text))
		{
			return;
		}

		$this->message_parser->message = $text;

		// Grab md5 'checksum' of new message
		$message_md5 = md5($this->message_parser->message);

		$this->message_parser->parse(true, true, true, true, false, true, true);

		$sql = 'SELECT forum_name
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . (int) $forum_id;
		$result = $this->db->sql_query($sql);
		$forum_name = $this->db->sql_fetchfield('forum_name');
		$this->db->sql_freeresult($result);

		$data = array(
			'forum_id'			=> $forum_id,
			'icon_id'			=> false,
			'poster_id' 		=> $this->user->data['user_id'],
			'enable_bbcode'		=> true,
			'enable_smilies'	=> true,
			'enable_urls'		=> true,
			'enable_sig'		=> true,
			'message'			=> $this->message_parser->message,
			'message_md5'		=> $message_md5,
			'attachment_data'	=> 0,
			'filename_data'		=> 0,
			'bbcode_bitfield'	=> $this->message_parser->bbcode_bitfield,
			'bbcode_uid'		=> $this->message_parser->bbcode_uid,
			'poster_ip'			=> $this->user->ip,
			'post_edit_locked'	=> 0,
			'topic_title'		=> $subject,
			'topic_status'		=> $lock,
			'notify_set'		=> false,
			'notify'			=> true,
			'post_time' 		=> time(),
			'forum_name'		=> $forum_name,
			'enable_indexing'	=> true,
			'force_approved_state'	=> true,
			'force_visibility' 	=> true,
			'attr_id'			=> 0,
		);
		$poll = array();

		submit_post('post', $subject, '', POST_NORMAL, $poll, $data);
	}

	/**
	* Allowed Extensions
	*/
	public function allowed_extensions()
	{
		// Here you can add additional extensions
		// Always use lower and upper case extensions

		$allowed_extensions = array();

		// Archive extenstions
		$allowed_extensions[] = 'zip';
		$allowed_extensions[] = 'ZIP';
		$allowed_extensions[] = 'rar';
		$allowed_extensions[] = 'RAR';
		$allowed_extensions[] = '7z';
		$allowed_extensions[] = '7Z';
		$allowed_extensions[] = 'ace';
		$allowed_extensions[] = 'ACE';
		$allowed_extensions[] = 'gtar';
		$allowed_extensions[] = 'GTAR';
		$allowed_extensions[] = 'gz';
		$allowed_extensions[] = 'GZ';
		$allowed_extensions[] = 'tar';
		$allowed_extensions[] = 'TAR';
		// Text files
		$allowed_extensions[] = 'txt';
		$allowed_extensions[] = 'TXT';
		// Documents
		$allowed_extensions[] = 'doc';
		$allowed_extensions[] = 'DOC';
		$allowed_extensions[] = 'docx';
		$allowed_extensions[] = 'DOCX';
		$allowed_extensions[] = 'xls';
		$allowed_extensions[] = 'XLS';
		$allowed_extensions[] = 'xlsx';
		$allowed_extensions[] = 'XLSX';
		$allowed_extensions[] = 'ppt';
		$allowed_extensions[] = 'PPT';
		$allowed_extensions[] = 'pptx';
		$allowed_extensions[] = 'PPTX';
		$allowed_extensions[] = 'pdf';
		$allowed_extensions[] = 'PDF';
		// Real Media files
		$allowed_extensions[] = 'ram';
		$allowed_extensions[] = 'RAM';
		$allowed_extensions[] = 'rm';
		$allowed_extensions[] = 'RM';
		// Windows Media files
		$allowed_extensions[] = 'wma';
		$allowed_extensions[] = 'WMA';
		$allowed_extensions[] = 'wmv';
		$allowed_extensions[] = 'WMV';
		// Flash files
		$allowed_extensions[] = 'swf';
		$allowed_extensions[] = 'SWF';
		// Quick time files
		$allowed_extensions[] = 'mov';
		$allowed_extensions[] = 'MOV';
		$allowed_extensions[] = 'mp4';
		$allowed_extensions[] = 'MP4';
		// Different files
		$allowed_extensions[] = 'mp3';
		$allowed_extensions[] = 'MP3';
		$allowed_extensions[] = 'mpeg';
		$allowed_extensions[] = 'MPEG';
		$allowed_extensions[] = 'mpg';
		$allowed_extensions[] = 'MPG';
		// Picture files
		$allowed_extensions[] = 'png';
		$allowed_extensions[] = 'PNG';
		$allowed_extensions[] = 'jpg';
		$allowed_extensions[] = 'JPG';
		$allowed_extensions[] = 'jpeg';
		$allowed_extensions[] = 'JPEG';
		$allowed_extensions[] = 'gif';
		$allowed_extensions[] = 'GIF';
		$allowed_extensions[] = 'tif';
		$allowed_extensions[] = 'TIF';
		$allowed_extensions[] = 'tiff';
		$allowed_extensions[] = 'TIFF';

		return $allowed_extensions;
	}
}
