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

	/** @var \phpbb\extension\manager */
	protected $extension_manager;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var string */
	protected $php_ext;

	/** @var \phpbb\files\factory */
	protected $files_factory;

	/**
	* Constructor
	*
	* @param \phpbb\user						$user
	* @param \phpbb\template\template			$template
	* @param \phpbb\controller\helper			$helper
	* @param \phpbb\extension\manager 			$extension_manager
	* @param \phpbb\config\config				$config
	* @param string								$php_ext
	* @param \phpbb\files\factory				$files_factory
	*
	*/
	public function __construct(
		\phpbb\user $user,
		\phpbb\template\template $template,
		\phpbb\controller\helper $helper,
		\phpbb\extension\manager $extension_manager,
		\phpbb\config\config $config,
		$php_ext,
		\phpbb\files\factory $files_factory = null)
	{
		$this->user					= $user;
		$this->template				= $template;
		$this->helper 				= $helper;
		$this->extension_manager	= $extension_manager;
		$this->config				= $config;
		$this->php_ext				= $php_ext;
		$this->files_factory 		= $files_factory;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.viewonline_overwrite_location'		=> 'add_page_viewonline',
			'core.user_setup'							=> 'load_language_on_setup',
			'core.page_header'							=> 'page_header',
			'core.page_header_after'					=> 'page_header_after',
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
			'L_DM_EDS'						=> $this->user->lang['EDS_DOWNLOADS'],
			'U_DM_EDS'						=> $this->helper->route('dmzx_downloadsystem_controller'),
			'S_EDS_EXIST'					=> true,
			'DOWNLOADSYSTEM_VERSION'		=> $this->config['download_system_version'],
			'PHPBB_IS_32'					=> ($this->files_factory !== null) ? true : false,
		));
	}

	public function page_header_after($event)
	{
		$page = '';
		$page = $this->page_name();

		if ($page === 'downloadsystem')
		{
			$this->assign_authors();
			$this->template->assign_var('DOWNLOADSYSTEM_FOOTER_VIEW', true);
		}
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

	public function page_name()
	{
		$this_page = explode(".", $this->user->page['page']);
		if ($this_page[0] == 'app')
		{
			$this_page_name = explode("/", $this_page[1]);
			return($this_page_name[1]);
		}
		else
		{
			$this_page_name = $this_page[0];
			return($this_page_name);
		}
	}

	protected function assign_authors()
	{
		$md_manager = $this->extension_manager->create_extension_metadata_manager('dmzx/downloadsystem', $this->template);
		$meta = $md_manager->get_metadata();
		$author_names = array();
		$author_homepages = array();

		foreach (array_slice($meta['authors'], 0, 1) as $author)
		{
			$author_names[] = $author['name'];
			$author_homepages[] = sprintf('<a href="%1$s" title="%2$s">%2$s</a>', $author['homepage'], $author['name']);
		}
		$this->template->assign_vars(array(
			'DOWNLOADSYSTEM_DISPLAY_NAME'		=> $meta['extra']['display-name'],
			'DOWNLOADSYSTEM_AUTHOR_NAMES'		=> implode(' &amp; ', $author_names),
			'DOWNLOADSYSTEM_AUTHOR_HOMEPAGES'	=> implode(' &amp; ', $author_homepages),
		));
	}
}
