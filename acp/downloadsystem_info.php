<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem\acp;

class downloadsystem_info
{
	function module()
	{
		return [
			'filename'		=> 'acp_dm_eds',
			'title'			=> 'ACP_DM_EDS',
			'modes'			=> [
				'config'		=> ['title' => 'ACP_MANAGE_CONFIG', 'auth' => 'ext_dmzx/downloadsystem && acl_a_dm_eds', 'cat' => ['ACP_DM_EDS']],
				'downloads'		=> ['title' => 'ACP_MANAGE_DOWNLOADS', 'auth' => 'ext_dmzx/downloadsystem && acl_a_dm_eds', 'cat' => ['ACP_DM_EDS']],
				'categories'	=> ['title' => 'ACP_MANAGE_CATEGORIES', 'auth' => 'ext_dmzx/downloadsystem && acl_a_dm_eds', 'cat' => ['ACP_DM_EDS']],
			],
		];
	}
}
