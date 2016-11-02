<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/**
	* Constructor
	*
	* @param \phpbb\user						$user
	* @param \phpbb\template\template			$template
	* @param \phpbb\controller\helper			$helper
	*
	*/
	public function __construct(
		\phpbb\user $user,
		\phpbb\template\template $template,
		\phpbb\controller\helper $helper)
	{
		$this->user			= $user;
		$this->template		= $template;
		$this->helper 		= $helper;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'	=> 'load_language_on_setup',
			'core.page_header'	=> 'page_header',
			'core.permissions'	=> 'permissions',
		);
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'dmzx/downloadsystem',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function page_header($event)
	{
		$this->template->assign_vars(array(
			'L_DM_EDS'		=> $this->user->lang['EDS_DOWNLOADS'],
			'U_DM_EDS'		=> $this->helper->route('dmzx_downloadsystem_controller'),
			'S_EDS_EXIST'	=> true,
		));
	}

	public function permissions($event)
	{
		$event['permissions'] = array_merge($event['permissions'], array(
			'u_dm_eds_use'	=> array(
				'lang'		=> 'ACL_U_DM_EDS_USE',
				'cat'		=> 'Download System'
			),
			'u_dm_eds_download'	=> array(
				'lang'		=> 'ACL_U_DM_EDS_DOWNLOAD',
				'cat'		=> 'Download System'
			),
			'a_dm_eds'		=> array(
				'lang'		=> 'ACL_U_DM_EDS_USE',
				'cat'		=> 'Download System'
			),
		));
		$event['categories'] = array_merge($event['categories'], array(
			'Download System'	=> 'ACL_U_DM',
		));
	}
}
