<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2019 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\downloadsystem;

class ext extends \phpbb\extension\base
{
	public function is_enableable()
	{
		$config = $this->container->get('config');

		return phpbb_version_compare($config['version'], '3.2.0', '>=');
	}
}
