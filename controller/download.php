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

	/** @var \phpbb\controller\helper */
	protected $helper;

	/**
	* Constructor
	*
	* @param \dmzx\downloadsystem\core\functions		$functions
	* @param \phpbb\template\template		 			$template
	* @param \phpbb\user								$user
	* @param \phpbb\auth\auth							$auth
	* @param \phpbb\request\request		 				$request
	* @param \phpbb\controller\helper					$helper
	*
	*/
	public function __construct(
		\dmzx\downloadsystem\core\functions $functions,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\request\request $request,
		\phpbb\controller\helper $helper)
	{
		$this->functions 		= $functions;
		$this->template 		= $template;
		$this->user 			= $user;
		$this->auth 			= $auth;
		$this->request 			= $request;
		$this->helper 			= $helper;
	}

	public function handle_downloadsystem()
	{
		if (!$this->auth->acl_get('u_dm_eds_use'))
		{
			throw new http_exception(401, 'EDS_NO_PERMISSION');
		}

		$cat_id = $this->request->variable('id', 0);

		// Generate the sub categories list
		$this->functions->generate_cat_list($cat_id);

		// Build navigation link
		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->user->lang('EDS_DOWNLOADS'),
			'U_VIEW_FORUM'	=> $this->helper->route('dmzx_downloadsystem_controller'),
		));

		$this->functions->assign_authors();
		$this->template->assign_var('DOWNLOADSYSTEM_FOOTER_VIEW', true);

		// Send all data to the template file
		return $this->helper->render('index_body.html', $this->user->lang('EDS_TITLE') . ' &bull; ' . $this->user->lang('EDS_INDEX'));
	}
}
