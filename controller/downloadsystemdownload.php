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
use phpbb\auth\auth;
use phpbb\db\driver\driver_interface as db_interface;
use phpbb\request\request_interface;

class downloadsystemdownload
{
	/** @var auth */
	protected $auth;

	/** @var db_interface */
	protected $db;

	/** @var request_interface */
	protected $request;

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
	* @param auth					$auth
	* @param db_interface			$db
	* @param request_interface		$request
	* @param string					$root_path
	* @param string					$dm_eds_table
	* @param string					$dm_eds_cat_table
	*
	*/
	public function __construct(
		db_interfaceauth $auth,
		db_interface $db,
		request_interface $request,
		$root_path,
		$dm_eds_table,
		$dm_eds_cat_table
	)
	{
		$this->auth 			= $auth;
		$this->db 				= $db;
		$this->request 			= $request;
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
				throw new http_exception(401, 'EDS_NO_DOWNLOAD');
			}

			if (!$row)
			{
				throw new http_exception(400, 'EDS_DL_NOEXISTS');
			}

			$download_sub_path = $row['cat_sub_dir'];
			$download_filename = $row['download_filename'];

			$url = $this->root_path . 'ext/dmzx/downloadsystem/files/' . $download_sub_path . '/' . $download_filename;

			$sql = 'UPDATE ' . $this->dm_eds_table . '
				SET download_count = download_count + 1
				WHERE download_id = ' . (int) $id;
			$result=$this->db->sql_query($sql);

			$this->db->sql_freeresult($result);

			header('Content-type: application/octet-stream');
			header("Content-disposition: attachment; filename=\"" . $download_filename . "\"");
			header('Content-Length: ' . filesize($url));
			ob_end_flush();
			readfile($url);
		}
		else
		{
			throw new http_exception(400, 'EDS_NO_ID');
		}
	}
}
