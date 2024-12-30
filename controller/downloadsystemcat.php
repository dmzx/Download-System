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
use phpbb\db\driver\driver_interface as db_interface;
use phpbb\request\request_interface;
use phpbb\controller\helper;
use phpbb\pagination;

class downloadsystemcat
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

	/** @var db_interface */
	protected $db;

	/** @var request_interface */
	protected $request;

	/** @var helper */
	protected $helper;

	/** @var pagination */
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
	* @param functions					$functions
	* @param parser_interface			$parser
	* @param renderer_interface			$renderer
	* @param template		 			$template
	* @param user						$user
	* @param auth						$auth
	* @param db_interface				$db
	* @param request_interface	 		$request
	* @param helper		 				$helper
	* @param pagination					$pagination
	* @param string						$php_ext
	* @param string						$root_path
	* @param string						$dm_eds_table
	* @param string						$dm_eds_cat_table
	*
	*/
	public function __construct(
		functions $functions,
		parser_interface $parser,
		renderer_interface $renderer,
		template $template,
		user $user,
		auth $auth,
		db_interface $db,
		request_interface $request,
		helper $helper,
		pagination $pagination,
		$php_ext,
		$root_path,
		$dm_eds_table,
		$dm_eds_cat_table
	)
	{
		$this->functions 			= $functions;
		$this->parser				= $parser;
		$this->renderer				= $renderer;
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
	}

	public function handle_downloadsystemcat()
	{
		// Read out config values
		$eds_values = $this->functions->config_values();

		$start	= $this->request->variable('start', 0);
		$number	= $eds_values['pagination_user'];

		$json_response = new \phpbb\json_response;

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
		$sql = 'SELECT cat_name, category_image
			FROM ' . $this->dm_eds_cat_table. '
			WHERE cat_id = ' . (int) $cat_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$cat_name = $row['cat_name'];

		$this->db->sql_freeresult($result);

		// Check if there are downloads
		if ($total_downloads == 0)
		{
			$this->template->assign_vars([
				'CAT_NAME'		=> $cat_name,
				'S_NO_FILES'	=> true,
				'MAIN_LINK'		=> $this->helper->route('dmzx_downloadsystem_controller'),
				'U_BACK'		=> append_sid("{$this->root_path}index.$this->php_ext"),
			]);
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
				$dl_filename 		= pathinfo($row['download_filename'], PATHINFO_EXTENSION);

				if (!$this->auth->acl_get('u_dm_eds_download'))
				{
					$download = '<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i title="' . sprintf($this->user->lang['EDS_NO_PERMISSION']) . '" class="fa fa-download fa-stack-1x text-danger" style="color:red;" data-ajax="no_access"></i></span>';
				}
				else
				{
					$download = '<a href="' . $this->helper->route('dmzx_downloadsystem_controller_download', ['id' =>	$dl_id]) . '" title="' . $this->user->lang['EDS_REGULAR_DOWNLOAD'] . '" alt=""><span class="fa-stack fa-2x"><i class="fa fa-download fa-stack-1x" id="' . $dl_id . '" data-ajax="access"></i><span class="eds-ext">' . $dl_filename . '</span></span></a>';
				}

				$dl_image = $row['download_image'];

				$this->template->assign_block_vars('catrow', [
					'DL_TITLE'			=> $dl_title,
					'DL_VERSION'		=> $dl_version,
					'DL_CLICKS'			=> $dl_clicks,
					'DL_DESC'			=> $this->renderer->render(html_entity_decode($row['download_desc'])),
					'DL_UPLOAD_TIME' 	=> $this->user->format_date($upload_time),
					'DL_LAST_CHANGED' 	=> $this->user->format_date($last_changed_time),
					'DL_FILESIZE'		=> $filesize,
					'U_DOWNLOAD'		=> $download,
					'DL_IMAGE'			=> generate_board_url() . '/' . $eds_values['dm_eds_image_dir'] . '/' . $dl_image,
				]);

				$this->template->assign_vars([
					'DL_IMAGE_ALERT'			=> generate_board_url() . '/' . $eds_values['dm_eds_image_dir'] . '/' . $dl_image,
				]);
			}

			$this->db->sql_freeresult($result);

			$pagination_url = $this->helper->route('dmzx_downloadsystem_controller_cat', ['id' =>	$cat_id]);
			//Start pagination
			$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_downloads, $number, $start);

			$this->functions->assign_authors();

			$this->template->assign_vars([
				'CAT_NAME' 						=> $cat_name,
				'EDS_DOWNLOAD_FILE_LINK'		=> $this->helper->route('dmzx_downloadsystem_controller_download'),
				'MAIN_LINK'						=> $this->helper->route('dmzx_downloadsystem_controller'),
				'DOWNLOADSYSTEM_FOOTER_VIEW'	=> true,
				'TOTAL_DOWNLOADS'				=> ($total_downloads == 1) ? $this->user->lang['EDS_SINGLE'] : sprintf($this->user->lang['EDS_MULTI'], $total_downloads),
				'L_MAIN_LINK'					=> sprintf($this->user->lang['EDS_BACK_LINK'], '<a href= "' . $this->helper->route('dmzx_downloadsystem_controller') . '">', '</a>'),
				'S_DM_EDS_ALLOW_DL_IMG'			=> $eds_values['dm_eds_allow_dl_img'],
				'EDS_DOWNLOAD_SHOW_DONATION'	=> $eds_values['show_donation'],
				'EDS_DOWNLOAD_DONATION_URL'		=> $eds_values['donation_url'],
			]);
		}

		// Send all data to the template file
		return $this->helper->render('showcat_body.html', $this->user->lang('EDS_TITLE') . ' &bull; ' . $cat_name);
	}
}
