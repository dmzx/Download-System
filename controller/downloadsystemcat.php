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

class downloadsystemcat
{
	/** @var \dmzx\downloadsystem\core\functions */
	protected $functions;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\pagination */
	protected $pagination;

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

	/**
	* Constructor
	*
	* @param \dmzx\downloadsystem\core\functions		$functions
	* @param \phpbb\template\template		 			$template
	* @param \phpbb\user								$user
	* @param \phpbb\auth\auth							$auth
	* @param \phpbb\db\driver\driver_interface			$db
	* @param \phpbb\request\request		 				$request
	* @param \phpbb\controller\helper		 			$helper
	* @param \phpbb\pagination							$pagination
	* @param string										$php_ext
	* @param string										$root_path
	* @param string										$dm_eds_table
	* @param string										$dm_eds_cat_table
	*
	*/
	public function __construct(
		\dmzx\downloadsystem\core\functions $functions,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\controller\helper $helper,
		\phpbb\pagination $pagination,
		$php_ext,
		$root_path,
		$dm_eds_table,
		$dm_eds_cat_table)
	{
		$this->functions 			= $functions;
		$this->template 			= $template;
		$this->user 				= $user;
		$this->auth 				= $auth;
		$this->db 					= $db;
		$this->request 				= $request;
		$this->helper 				= $helper;
		$this->pagination 			= $pagination;
		$this->php_ext 				= $php_ext;
		$this->root_path 			= $root_path;
		$this->dm_eds_table 		= $dm_eds_table;
		$this->dm_eds_cat_table 	= $dm_eds_cat_table;

		if (!class_exists('parse_message'))
		{
			include($this->root_path . 'includes/message_parser.' . $this->php_ext);
		}
	}

	public function handle_downloadsystemcat()
	{
		// Read out config values
		$eds_values = $this->functions->config_values();

		$start	= $this->request->variable('start', 0);
		$number	= $eds_values['pagination_user'];

		// Setup message parser
		$this->message_parser = new \parse_message();

		/**
		* Retrieve cat id
		*/
		$cat_id = $this->request->variable('id', 0);
		$board_url = generate_board_url() . '/';

		/**
		* We need some information about the cat, we are in
		*/
		// Select cat name
		$sql = 'SELECT cat_name
			FROM ' . $this->dm_eds_cat_table . '
			WHERE cat_id = ' . (int) $cat_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			throw new http_exception(401, 'EDS_CAT_NOT_EXIST');
		}
		else
		{
			$cat_data = $this->functions->get_cat_info($cat_id);
		}

		/**
		* Generate the navigation
		*/
		$this->functions->generate_cat_nav($cat_data);

		// Total number of downloads
		$sql = 'SELECT COUNT(download_id) AS total_downloads
			FROM ' . $this->dm_eds_table . '
			WHERE download_cat_id = ' . (int) $cat_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$total_downloads = $row['total_downloads'];
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
		if ($total_downloads == 0)
		{
			$this->template->assign_vars(array(
				'CAT_NAME'		=> $cat_name,
				'S_NO_FILES'	=> true,
				'MAIN_LINK'		=> $this->helper->route('dmzx_downloadsystem_controller'),
				'U_BACK'		=> append_sid("{$this->root_path}index.$this->php_ext"),
			));
		}
		else
		{
			$sql = 'SELECT d.*, c.*
				FROM ' . $this->dm_eds_table . ' d
				LEFT JOIN ' . $this->dm_eds_cat_table. ' c
					ON d.download_cat_id = c.cat_id
				WHERE c.cat_id = ' . (int) $cat_id . '
				ORDER BY LOWER(d.download_version) DESC';
			$result = $this->db->sql_query_limit($sql, $number, $start);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$dl_id				= $row['download_id'];
				$dl_title			= $row['download_title'];
				$dl_version			= $row['download_version'];
				$dl_clicks			= $row['download_count'];
				$cat_name 			= $row['cat_name'];
				$upload_time 		= $row['upload_time'];
				$last_changed_time 	= $row['last_changed_time'];
				$filesize			= $row['filesize'] > 1048576 ? round($row['filesize']/1048576, 2) . ' MB' : round($row['filesize']/1024) . ' kB';

				if (!$this->auth->acl_get('u_dm_eds_download'))
				{
					$download = '<img src="'. $board_url. 'ext/dmzx/downloadsystem/styles/prosilver/theme/images/' . 'eds_no_download.png" title="' . sprintf($this->user->lang['EDS_NO_PERMISSION']) . '" alt=""></img>';
				}
				else
				{
					$download = '<a href="' . $this->helper->route('dmzx_downloadsystem_controller_download', array('id' =>	$dl_id)) . '"><img src="' . $board_url. 'ext/dmzx/downloadsystem/styles/prosilver/theme/images/' . 'eds_regular_download.png" title="' . $this->user->lang['EDS_REGULAR_DOWNLOAD'] . '" alt=""></a>';
				}

				$this->message_parser->message = $row['download_desc'];
				$this->message_parser->bbcode_bitfield = $row['bbcode_bitfield'];
				$this->message_parser->bbcode_uid = $row['bbcode_uid'];
				$allow_bbcode = $allow_magic_url = $allow_smilies = true;
				$this->message_parser->format_display($allow_bbcode, $allow_magic_url, $allow_smilies);

				$this->template->assign_block_vars('catrow', array(
					'DL_TITLE'			=> $dl_title,
					'DL_VERSION'		=> $dl_version,
					'DL_CLICKS'			=> $dl_clicks,
					'DL_DESC'			=> $this->message_parser->message,
					'DL_UPLOAD_TIME' 	=> $this->user->format_date($upload_time),
					'DL_LAST_CHANGED' 	=> $this->user->format_date($last_changed_time),
					'DL_FILESIZE'		=> $filesize,
					'U_DOWNLOAD'		=> $download,
				));
			}

			$this->db->sql_freeresult($result);

			$pagination_url = $this->helper->route('dmzx_downloadsystem_controller_cat', array('id' =>	$cat_id));
			//Start pagination
			$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_downloads, $number, $start);

			$this->functions->assign_authors();

			$this->template->assign_vars(array(
				'CAT_NAME' 								=> $cat_name,
				'MAIN_LINK'								=> $this->helper->route('dmzx_downloadsystem_controller'),
				'DOWNLOADSYSTEM_FOOTER_VIEW'			=> true,
				'TOTAL_DOWNLOADS'						=> ($total_downloads == 1) ? $this->user->lang['EDS_SINGLE'] : sprintf($this->user->lang['EDS_MULTI'], $total_downloads),
				'L_MAIN_LINK'							=> sprintf($this->user->lang['EDS_BACK_LINK'], '<a href= "' . $this->helper->route('dmzx_downloadsystem_controller') . '">', '</a>'),
			));
		}

		// Send all data to the template file
		return $this->helper->render('showcat_body.html', $this->user->lang('EDS_TITLE') . ' &bull; ' . $cat_name);
	}
}
