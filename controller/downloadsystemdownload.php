<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\controller;

class downloadsystemdownload
{
	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\config\config */
	protected $config;

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

	/**
	* Constructor
	*
	* @param \phpbb\user						$user
	* @param \phpbb\auth\auth					$auth
	* @param \phpbb\db\driver\driver_interface	$db
	* @param \phpbb\request\request		 		$request
	* @param \phpbb\config\config				$config
	* @param									$php_ext
	* @param									$root_path
	* @param									$dm_eds_table
	* @param									$dm_eds_cat_table
	*
	*/
	public function __construct(
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\config\config $config,
		$php_ext,
		$root_path,
		$dm_eds_table,
		$dm_eds_cat_table)
	{
		$this->user 			= $user;
		$this->auth 			= $auth;
		$this->db 				= $db;
		$this->request 			= $request;
		$this->config 			= $config;
		$this->php_ext 			= $php_ext;
		$this->root_path 		= $root_path;
		$this->dm_eds_table 	= $dm_eds_table;
		$this->dm_eds_cat_table = $dm_eds_cat_table;
	}

	public function handle_downloadsystemdownload()
	{
		$id	= $this->request->variable('id', 0, true);

		if ($id)
		{
			$sql = 'SELECT d.*, c.*
				FROM ' . $this->dm_eds_table . ' d
				LEFT JOIN ' . $this->dm_eds_cat_table . ' c
				ON d.download_cat_id = c.cat_id
				WHERE d.download_id = ' . (int) $id;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);

			if (!$this->auth->acl_get('u_dm_eds_download'))
			{
				$message = $this->user->lang['EDS_NO_DOWNLOAD'] . '<br /><br /><a href="' . append_sid("{$this->root_path}index.$this->php_ext") . '">&laquo; ' . $this->user->lang['EDS_BACK_INDEX'] . '</a>';
				trigger_error($message);
			}

			if (!$row)
			{
				trigger_error($this->user->lang['EDS_DL_NOEXISTS']);
			}

			$download_sub_path = $row['cat_sub_dir'];
			$download_filename = $row['download_filename'];
			$download_cost = $row['cost_per_dl'];

			$url = $this->root_path . 'ext/dmzx/downloadsystem/files/' . $download_sub_path . '/' . $download_filename;

			$sql = 'UPDATE ' . $this->dm_eds_table . '
				SET download_count = download_count + 1
				WHERE download_id = ' . $id;
			$result=$this->db->sql_query($sql);

			$this->db->sql_freeresult($result);

			header('Content-type: application/octet-stream');
			header("Content-disposition: attachment; filename=\"" . $download_filename . "\"");
			header('Content-Length: ' . filesize($url));
			readfile($url);
		}
		else
		{
			trigger_error($this->user->lang['EDS_NO_ID']);
		}
	}

	/**
	* Get a browser friendly UTF-8 encoded filename
	*/
	private function header_filename($file)
	{
		$user_agent = (!empty($_SERVER['HTTP_USER_AGENT'])) ? htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']) : '';

		// There be dragons here.
		// Not many follows the RFC...
		if (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Safari') !== false || strpos($user_agent, 'Konqueror') !== false)
		{
			return "filename=" . rawurlencode($file);
		}

		// follow the RFC for extended filename for the rest
		return "filename*=UTF-8''" . rawurlencode($file);
	}
}
