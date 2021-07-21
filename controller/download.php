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
use phpbb\template\template;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\request\request_interface;
use phpbb\controller\helper;

class download
{
	/** @var functions */
	protected $functions;

	/** @var template */
	protected $template;

	/** @var user */
	protected $user;

	/** @var auth */
	protected $auth;

	/** @var request_interface */
	protected $request;

	/** @var helper */
	protected $helper;

	/**
	* Constructor
	*
	* @param functions				$functions
	* @param template		 		$template
	* @param user					$user
	* @param auth					$auth
	* @param request_interface	 	$request
	* @param helper					$helper
	*
	*/
	public function __construct(
		functions $functions,
		template $template,
		user $user,
		auth $auth,
		request_interface $request,
		helper $helper
	)
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
		$this->template->assign_block_vars('navlinks', [
			'FORUM_NAME'	=> $this->user->lang('EDS_DOWNLOADS'),
			'U_VIEW_FORUM'	=> $this->helper->route('dmzx_downloadsystem_controller'),
        ]);

		$this->functions->assign_authors();
		$this->template->assign_var('DOWNLOADSYSTEM_FOOTER_VIEW', true);

		// Send all data to the template file
		return $this->helper->render('index_body.html', $this->user->lang('EDS_TITLE') . ' &bull; ' . $this->user->lang('EDS_INDEX'));
	}
}
