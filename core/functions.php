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
		$this->php_ext 				= $php_ext;
		$this->root_path 			= $root_path;
		$this->dm_eds_table 		= $dm_eds_table;
		$this->dm_eds_cat_table 	= $dm_eds_cat_table;
		$this->dm_eds_config_table 	= $dm_eds_config_table;
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

			if (((is_array($ignore_id) && in_array($row['cat_id'], $ignore_id)) || $row['cat_id'] == $ignore_id) || ($dm_video))
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
			WHERE cat_id = ' . $cat_id;
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

		// Get video parents
		$cat_parents = $this->get_cat_parents($cat_data);

		// Build navigation links
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

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $cat_data['cat_name'],
			'U_VIEW_FORUM'	=> $this->helper->route('dmzx_downloadsystem_controller_cat', array('id' => $parent_cat_id)),
		));
		return;
	}

	/**
	* Generate the sub categories list
	*/
	public function generate_cat_list($cat_id)
	{
		$start = $this->request->variable('start', 0);

		if (!class_exists('parse_message'))
		{
			include($this->root_path . 'includes/message_parser.' . $this->php_ext);
		}

		// Setup message parser
		$this->message_parser = new \parse_message();

		// Read out config values
		$eds_values = $this->config_values();

		$pagination_downloads = $eds_values['pagination_downloads'];

		// pagination value for categories
		$dls = $pagination_downloads;

		// Total number of category
		$sql = 'SELECT COUNT(cat_id) AS total_cat
			FROM ' . $this->dm_eds_cat_table;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$total_cat = $row['total_cat'];
		$this->db->sql_freeresult($result);

		// Select cat name
		$sql = 'SELECT cat_name
			FROM ' . $this->dm_eds_cat_table. '
			WHERE cat_id = ' . (int) $cat_id;
		$result = $this->db->sql_query($sql);
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
						AND bc2.left_id > bc.left_id )
				LEFT JOIN ' . $this->dm_eds_table . ' bd
					ON ( bd.download_cat_id = bc.cat_id
						OR bd.download_cat_id = bc2.cat_id )
				WHERE bc.parent_id = ' . $cat_id . '
				GROUP BY bc.cat_id
				ORDER BY bc.left_id ASC';
			$result = $this->db->sql_query_limit($sql, $dls, $start);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$row['last_download'] = ($row['last_download']) ? $row['last_download'] : 0;
				$subcats = $last_download_id = $last_download_title = $last_download_chg_time = $last_download = $downloads = $download = $last_download_name = $download_version = $download_title = $download_count = $upload_time = $last_changed_time = '';

				// Do we have sub categories?
				if (($row['left_id'] + 1) != $row['right_id'])
				{
					$sql2 = 'SELECT bc.*
						FROM ' . $this->dm_eds_cat_table . ' bc
						WHERE bc.parent_id = ' . $row['cat_id'] . '
						ORDER BY bc.left_id ASC';
					$result2 = $this->db->sql_query($sql2);

					while ($row2 = $this->db->sql_fetchrow($result2))
					{
						$subcats .= ($subcats) ? ', ' : '';
						$subcats .= '<a class="subforum ' . (((isset($read_info[$row2['cat_id']]) ? $read_info[$row2['cat_id']] : 0) && ($this->user->data['user_id'] != ANONYMOUS)) ? 'unread' : 'read') . '" href="';
						$subcats .= $this->helper->route('dmzx_downloadsystem_controller_cat', array('id' =>	$row2['cat_id']));
						$subcats .= '">' . censor_text($row2['cat_name']) . '</a>';
					}
					$this->db->sql_freeresult($result2);
					$folder_image = 'forum_read_subforum';
					$folder_alt = 'no';
					$l_subcats = $this->user->lang['EDS_SUB_CAT'];
					if ($row['left_id'] + 3 != $row['right_id'])
					{
						$l_subcats = $this->user->lang['EDS_SUB_CATS'];
					}
				}
				else
				{
					$folder_image = 'forum_read';
					$l_subcats = '';
					$folder_alt = 'no';
				}

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
					'CAT_DESC'				=> $this->message_parser->message,
					'CAT_FOLDER_IMG_SRC'	=> $this->user->img($folder_image, $folder_alt, false, '', 'src'),
					'SUBCATS'				=> ($subcats) ? $l_subcats . ': <span style="font-weight: bold;">' . $subcats . '</span>' : '',
				));
			}

			$this->db->sql_freeresult($result);

			$pagination_url = $this->helper->route('dmzx_downloadsystem_controller');

			//Start pagination
			$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_cat, $dls, $start);

			$this->template->assign_vars(array(
				'LAST_POST_IMG'			=> $this->user->img('icon_topic_latest', 'VIEW_LATEST_POST'),
				'EDS_CATEGORIES'		=> ($total_cat == 1) ? $this->user->lang['EDS_CAT'] : sprintf($this->user->lang['EDS_CATS'], $total_cat),
			));
		}
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
}
