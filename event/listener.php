<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\user;
use phpbb\template\template;
use phpbb\controller\helper;
use phpbb\config\config;
use phpbb\auth\auth;

class listener implements EventSubscriberInterface
{
	/** @var user */
	protected $user;

	/** @var template */
	protected $template;

	/** @var helper */
	protected $helper;

	/** @var config */
	protected $config;

	/** @var auth */
	protected $auth;

	/** @var string */
	protected $php_ext;

	/**
	* Constructor
	*
	* @param user				$user
	* @param template			$template
	* @param helper				$helper
	* @param config				$config
	* @param auth				$auth
	* @param string				$php_ext
	*
	*/
	public function __construct(
		user $user,
		template $template,
		helper $helper,
		config $config,
		auth $auth,
		$php_ext
	)
	{
		$this->user					= $user;
		$this->template				= $template;
		$this->helper 				= $helper;
		$this->config				= $config;
		$this->auth 				= $auth;
		$this->php_ext				= $php_ext;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.viewonline_overwrite_location'		=> 'add_page_viewonline',
			'core.user_setup'							=> 'load_language_on_setup',
			'core.page_header'							=> 'page_header',
			'core.permissions'							=> 'permissions',
		);
	}

	public function add_page_viewonline($event)
	{
		if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/downloadsystem') === 0 || strrpos($event['row']['session_page'], 'app.' . $this->php_ext . 'downloadsystemcat') === 0)
		{
			$event['location'] = $this->user->lang('EDS_DOWNLOADS');
			$event['location_url'] = $this->helper->route('dmzx_downloadsystem_controller', array('name' => 'index'));
		}

		if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/downloadsystemupload') === 0)
		{
			$event['location'] = $this->user->lang('EDS_UPLOAD_SECTION');
			$event['location_url'] = $this->helper->route('dmzx_downloadsystem_controller_upload', array('name' => 'index'));
		}
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
			'U_DM_EDS'						=> $this->helper->route('dmzx_downloadsystem_controller'),
			'U_DM_EDS_UPLOAD'				=> $this->helper->route('dmzx_downloadsystem_controller_upload'),
			'DM_EDS_USE_UPLOAD'				=> $this->auth->acl_get('u_dm_eds_upload'),
			'S_EDS_EXIST'					=> true,
			'DOWNLOADSYSTEM_VERSION'		=> $this->config['download_system_version'],
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
			'u_dm_eds_upload'	=> array(
				'lang'		=> 'ACL_U_DM_EDS_UPLOAD',
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
