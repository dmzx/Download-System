<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\controller;

class download
{
	/** @var \dmzx\downloadsystem\core\functions */
	protected $functions;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var string */
	protected $php_ext;

	/** @var string phpBB root path */
	protected $root_path;

	/**
	* Constructor
	*
	* @param \dmzx\downloadsystem\core\functions		$functions
	* @param \phpbb\template\template		 			$template
	* @param \phpbb\user								$user
	* @param \phpbb\auth\auth							$auth
	* @param \phpbb\request\request		 				$request
	* @param											$php_ext
	* @param											$root_path
	*
	*/
	public function __construct(
		\dmzx\downloadsystem\core\functions $functions,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\request\request $request,
		$php_ext,
		$root_path)
	{
		$this->functions 		= $functions;
		$this->template 		= $template;
		$this->user 			= $user;
		$this->auth 			= $auth;
		$this->request 			= $request;
		$this->php_ext 			= $php_ext;
		$this->root_path 	= $root_path;
	}

	public function handle_downloadsystem()
	{
		/**
		* Check, if user may see the DM EDS
		*/
		if (!$this->auth->acl_get('u_dm_eds_use'))
		{
			$message = $this->user->lang['EDS_NO_PERMISSION'] . '<br /><br /><a href="' . append_sid("{$this->root_path}index.$this->php_ext") . '">&laquo; ' . $this->user->lang['EDS_BACK_INDEX'] . '</a>';
			trigger_error($message);
		}

		$cat_id = $this->request->variable('id', 0);
		$this->functions->generate_cat_list($cat_id);

		/**
		* The output of the page
		*/
		page_header($this->user->lang['EDS_TITLE'] . ' &bull; ' . $this->user->lang['EDS_INDEX']);

		$this->template->set_filenames(array(
			'body' => 'index_body.html'
		));

		page_footer();
	}
}
