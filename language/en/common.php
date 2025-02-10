<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2016 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, [
	'EDS_BACK_INDEX'					=> 'Back to index',
	'EDS_BACK_LINK'						=> 'Click %shere%s to return to the download index',
	'EDS_CATS_NAME'						=> 'Categories',
	'EDS_CAT_DESC'						=> 'Description',
	'EDS_CAT_NAME'						=> 'Category',
	'EDS_CAT_IMAGE'						=> 'Image',
	'EDS_COST'							=> 'Costs',
	'EDS_COST_ERROR'					=> 'You need more %1$s in order to download this file',
	'EDS_COST_FREE'						=> 'This download is for free',
	'EDS_COST_OK'						=> 'You have enough %1$s to download this file',
	'EDS_DISABLED'						=> 'The Download System is currently deactivated. Please try again later.<br>If you later on still encounter the same, please inform the admin.',
	'EDS_DL_NOEXISTS'					=> 'This download does not exist',
	'EDS_DOWNLOAD'						=> 'Download',
	'EDS_DOWNLOADS'						=> 'Downloads',
	'EDS_DOWNLOAD_FILE'					=> 'Download File',
	'EDS_DOWNLOAD_DONATE'				=> 'Donate',
	'EDS_DOWNLOAD_DONATE_THX'			=> 'Thank you for donating',
	'EDS_DOWNLOAD_DONATE_MES'			=> 'Do you also want to make a small <strong>donation</strong><br>or simply <strong>download</strong> the file?',
	'EDS_DOWNLOAD_START'				=> 'Your download will start shortly!',
	'EDS_DOWNLOAD_NO_PERM'				=> 'No Permission!',
	'EDS_DOWNLOAD_NOT_DOWNLOAD'			=> 'You cannot download this file',
	'EDS_DOWNLOAD_REDIRECT'				=> 'Redirect',
	'EDS_DOWNLOAD_EXPLAIN'				=> 'Click the icon on the right to download the desired file.',
	'EDS_FILE_CLICKS'					=> 'Total Downloads',
	'EDS_FILE_DESC'						=> 'Description',
	'EDS_FILE_TITLE'					=> 'Filename',
	'EDS_FILE_VERSION'					=> 'Version',
	'EDS_FREE'							=> 'Free',
	'EDS_INDEX'							=> 'Index',
	'EDS_LAST_CHANGED_ON'				=> 'Last changed on',
	'EDS_LAST_DOWNLOAD' 				=> '&nbsp;<strong>%1$s</strong><br><br>&nbsp;<span class="downloads-class">Downloaded: %2$s</span><br>&nbsp;Last changed on: %3$s',
	'EDS_LAST_FILE'						=> 'Newest file',
	'EDS_LEGEND'						=> 'Legend',
	'EDS_LEGEND_ERROR'					=> 'You need more %1$s',
	'EDS_LEGEND_FREE'					=> 'Download is free',
	'EDS_LEGEND_NO_DL'					=> 'You are not allowed to download files',
	'EDS_LEGEND_OK'						=> 'You have enough %1$s',
	'EDS_MULTI'							=> '%1$s Downloads',
	'EDS_NO_CAT'						=> '<strong>Sorry! There are currently no categories available.</strong><br><br>',
	'EDS_NO_CAT_IN_UPLOAD'				=> 'Sorry! There are currently no categories available.',
	'EDS_NO_DOWNLOADS'					=> '<strong>Sorry! There are currently no downloads available.</strong><br><br>',
	'EDS_NO_FILES'						=> 'There are no downloads',
	'EDS_NO_ID'							=> 'No id given',
	'EDS_NUMBER_DOWNLOADS'				=> 'Files',
	'EDS_REGULAR_DOWNLOAD'				=> 'Click here to download the selected file',
	'EDS_REQUIRES_POINTS'				=> '<strong>As we have the Ultimate Points Mod installed and you require points for this download,<br>you need to be logged in, before you can download this file!</strong>',
	'EDS_SINGLE'						=> '1 Download',
	'EDS_SUB_CAT'						=> 'Sub category',
	'EDS_SUB_CATS'						=> 'Sub categories',
	'EDS_TITLE'							=> 'Downloads',
	'EDS_TITLE_EXPLAIN'					=> 'Select a category below',
	'EDS_UPLOADED_ON'					=> 'Uploaded on',
	'EDS_UPLOAD'						=> 'Upload',
	'EDS_UPLOADS'						=> 'Download system upload section',
	'EDS_UPLOAD_SECTION'				=> 'Upload section',
	'EDS_UPLOAD_MESSAGE'				=> 'Upload here your file in correct category.',
	'EDS_FILESIZE'						=> 'Filesize',
	'EDS_CAT_NOT_EXIST'					=> 'The selected category does not exist!',
	'EDS_BACK_DOWNLOADS'				=> 'Back to download overview',
	'EDS_NO_PERMISSION'					=> 'You don\'t have the permission to use the Download System',
	'EDS_NO_DOWNLOAD'					=> 'You don\'t have the permission to download files from the Download System',
	'EDS_NO_UPLOAD'						=> 'You don\'t have the permission to use the upload section',
	'EDS_NO_DIRECT_DL'					=> 'You are not allowed to download files',
	'EDS_CAT'							=> '%d category',
	'EDS_CATS'							=> '%d categories',
	'EDS_SUB_CATEGORY'					=> 'and %d subcategory',
	'EDS_SUB_CATEGORIES'				=> 'and %d subcategories',
	'EDS_CURRENT_VERSION'				=> 'Current Version',
	'EDS_NEW_TITLE'						=> 'Title',
	'EDS_NEW_TITLE_EXPLAIN'				=> 'Title for your new download.',
	'EDS_NEW_VERSION'					=> 'Version',
	'EDS_NEW_VERSION_EXPLAIN'			=> 'Version of your download.',
	'EDS_NEW_DESC'						=> 'Description',
	'EDS_NEW_DESC_EXPLAIN'				=> 'Enter here a description for your download.',
	'EDS_NEW_DL_CAT'					=> 'Category',
	'EDS_NEW_DL_CAT_EXPLAIN'			=> 'Select here the category.',
	'EDS_NEW_DOWNLOAD'					=> 'New download',
	'EDS_NEW_FILENAME'					=> 'File name',
	'EDS_NEW_FILENAME_EXPLAIN'			=> 'Select the file to upload.',
	'EDS_NEW_DOWNLOAD_SIZE'				=> 'The maximum size of the file is <strong>%1$s %2$s</strong>! Due to the upload time you might need, this value can be lower!',
	'EDS_SUBCAT_FILE'					=> '1 file',
	'EDS_SUBCAT_FILES'					=> '%1$d files',
	'EDS_DL_IMAGE'						=> 'Image',
	'EDS_UPLOAD_FILE_EXISTS'			=> 'The file you like to upload, does already exist in this category!',
	'EDS_NO_FILENAME'					=> 'You have to enter a file, which belongs to your upload!',
	'EDS_FILE_TOO_BIG'					=> 'The file is bigger, than your host allows!',
	'EDS_CLICK'							=> 'here',
	'EDS_ANNOUNCE_TITLE'				=> '%1$s',
	'EDS_ANNOUNCE_MSG'					=> 'Hello,

we have a new download!

[b]Title:[/b] %1$s
[b]Description:[/b] %2$s
[b]Category:[/b] %3$s
[b]Click %4$s to go to the download page![/b]

Have fun!',
	'EDS_NEW_ADDED'							=> 'Your entry was successfully added to the database',
]);
