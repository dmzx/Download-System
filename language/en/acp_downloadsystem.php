<?php
/**
*
* @package phpBB Extension - Download System
* @copyright (c) 2019 dmzx - https://www.dmzx-web.net
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
	'ACP_ADD'							=> 'Add',
	'ACP_ALL_DOWNLOADS'					=> 'All downloads',
	'ACP_ANNOUNCE_ENABLE'				=> 'Announce new downloads',
	'ACP_ANNOUNCE_ENABLE_EXPLAIN'		=> 'If enabled, announce new downloads in a certain forum.',
	'ACP_ANNOUNCE_LOCK'					=> 'Lock announcement',
	'ACP_ANNOUNCE_LOCK_EXPLAIN'			=> 'If enabled, the topic will be locked.',
	'ACP_ANNOUNCE_ID'					=> 'Announcement forum',
	'ACP_ANNOUNCE_ID_EXPLAIN'			=> 'Enter here the ID of the forum, where you like to announce new downloads.',
	'ACP_ANNOUNCE_MSG'					=> 'Hello,

we have a new download!

[b]Title:[/b] %1$s
[b]Description:[/b] %2$s
[b]Category:[/b] %3$s
[b]Click %4$s to go to the download page![/b]

Have fun!',
	'ACP_ANNOUNCE_SETTINGS'				=> 'Announcement settings',
	'ACP_ANNOUNCE_TITLE'				=> '%1$s',
	'ACP_CAT_NAME_SHOW_YES'				=> 'yes',
	'ACP_CAT_NAME_SHOW_NO'				=> 'no',
	'ACP_NEW_CAT_NAME_SHOW'				=> 'Show on index upload',
	'ACP_NEW_CAT_NAME_SHOW_EXPLAIN'		=> 'Show category on upload section for groups that allowed to upload.<br><strong>Note:</strong> Admins can always see all categories in upload section.',
	'ACP_ANNOUNCE_UP'					=> 'Announce download again',
	'ACP_ANNOUNCE_UP_EXPLAIN'			=> 'Activate, if you like to re-announce the download. The message will be sent as an update information',
	'ACP_ANNOUNCE_UP_MSG'				=> 'Hello,

we have an updated download!

[b]Title:[/b] %1$s

[b]Description:[/b] %2$s

[b]Category:[/b] %3$s

[b]Click %4$s to go to the category![/b]

Have fun!',
	'ACP_ANNOUNCE_UP_TITLE'					=> '[UPD] %1$s',
	'ACP_BASIC'								=> 'Basic settings',
	'ACP_CAT'								=> 'Category',
	'ACP_CAT_SUB'							=> 'Category with subcategory',
	'ACP_CATEGORIES'						=> 'Categories',
	'ACP_CAT_DELETE'						=> 'Delete category',
	'ACP_CAT_DELETE_DONE'					=> 'Your category was successfully deleted',
	'ACP_CAT_DELETE_EXPLAIN'				=> 'Here you can delete a category.',
	'ACP_CAT_EDIT_DONE'						=> 'Your category was successfully updated',
	'ACP_CAT_EXIST'							=> 'The folder name already exists on your webspace!',
	'ACP_CAT_EXPLAIN'						=> 'Enter here the category, where your download should be listed in',
	'ACP_CAT_INDEX'							=> 'Categories Index',
	'ACP_CAT_NAME_ERROR'					=> 'You need to enter a folder name for your category!',
	'ACP_CAT_NEW'							=> 'Add a new category',
	'ACP_CAT_NEW_DONE'						=> 'Your new category was added and the folder created successfully on your webspace!',
	'ACP_CAT_NEW_EXPLAIN'					=> 'Here you can add a new category.',
	'ACP_CAT_NOT_EXIST'						=> 'The requested category does not exist!',
	'ACP_CAT_SELECT'						=> 'Here you can add, edit or delete categories.',
	'ACP_CAT_NO_CATEGORIES'					=> 'No categories yet',
	'ACP_CAT_OF'							=> 'of',
	'ACP_CLICK'								=> 'here',
	'ACP_CONFIG_SUCCESS'					=> 'The configuration was successfully updated',
	'ACP_COPY_NEW'							=> 'Copy as draft',
	'ACP_COST_ERROR'						=> 'You can\'t set negative costs for a download!<br>Enter 0 to make it free or any positive value.',
	'ACP_COST_EXPLAIN'						=> 'Here you can set, how much %1$s the users have to pay for this download. Set 0, to leave the download for free.',
	'ACP_COST_FREE'							=> 'Free',
	'ACP_COST_SHORT'						=> 'Costs',
	'ACP_DELETE_HAS_FILES'					=> 'There are still files in the category!<br>Please delete them or move them to another category first!',
	'ACP_DELETE_SUB_CATS'					=> 'Please delete first your sub categories!',
	'ACP_DEL_CAT'							=> 'Are you sure, you want to delete the category <strong>%1$s</strong>?<br>The physical folder on your web server - if there are no more downloads inside - will be removed too!',
	'ACP_DEL_CAT_EXPLAIN'					=> 'Here you can delete an existing category.',
	'ACP_DEL_DOWNLOAD'						=> 'Delete a download',
	'ACP_DEL_DOWNLOADS_TO'					=> 'Move downloads to',
	'ACP_DEL_DOWNLOAD_YES'					=> 'Delete category including the downloads?',
	'ACP_DEL_SUBS'							=> 'Delete sub-categories',
	'ACP_DEL_SUBS_TO'						=> 'Move sub-categories to',
	'ACP_DEL_SUBS_YES'						=> 'Delete category including the sub-categories?',
	'ACP_DOWNLOADS'							=> 'Downloads',
	'ACP_DOWNLOAD_DELETED'					=> 'Your download was successfully deleted.',
	'ACP_DOWNLOAD_UPDATED'					=> 'Your download was successfully updated',
	'ACP_DOWNLOAD_SYSTEM'					=> 'Download system',
	'ACP_EDIT_CAT'							=> 'Edit category',
	'ACP_EDIT_CAT_EXPLAIN'					=> 'Here you can edit an existing category.',
	'ACP_EDIT_DOWNLOADS'					=> 'Edit downloads',
	'ACP_EDIT_DOWNLOADS_EXPLAIN'			=> 'Here you can edit the selected download.',
	'ACP_EDIT_FILENAME'						=> 'Saved File',
	'ACP_EDIT_FILENAME_EXPLAIN'				=> '<strong>IMPORTANT:</strong> If you change the file name over here, there will be no further check, if the file really exists on your webspace. <strong>You need to upload the new file	via FTP and manually delete the old one!</strong>',
	'ACP_EDIT_SUB_CAT_EXPLAIN'				=> 'The already created subdirectory can\'t be edited. So if you like to have a different subdirectory, you need to delete the current category and create a new one!',
	'ACP_FILE_TOO_BIG'						=> 'The file is bigger, than your host allows!',
	'ACP_DL_ERROR_CATEGORY_IMAGE'			=> 'There was an error with your category image:',
	'ACP_FORUM_ID_ERROR'					=> 'The entered forum ID does not exist!',
	'ACP_EDS_INDEX'							=> 'Download System',
	'ACP_MANAGE_DOWNLOADS_EXPLAIN'			=> 'Here you can add, edit or delete your downloads <code>[URL]</code>.',
	'ACP_MULTI_DOWNLOAD'					=> '%d downloads',
	'ACP_NEED_DATA'							=> 'You need to fill all fields!',
	'ACP_NEW_ADDED'							=> 'Your entry was successfully added to the database',
	'ACP_NEW_CAT'							=> 'New category',
	'ACP_NEW_CAT_DESC'						=> 'Description of the category',
	'ACP_NEW_CAT_DESC_EXPLAIN'				=> 'Enter a useful description of your new category.<br>BB-Codes, smiles and links will be recognised automatically.',
	'ACP_NEW_CAT_NAME'						=> 'Category name',
	'ACP_NEW_CAT_PARENT'					=> 'Parent category',
	'ACP_NEW_COPY_DOWNLOAD'					=> 'New download with copy',
	'ACP_NEW_COPY_DOWNLOAD_EXPLAIN'			=> 'You selected to copy an already existing download for your new download. This will save a little time, especially if you like to upload ie a new version',
	'ACP_NEW_DESC'							=> 'Description',
	'ACP_NEW_DESC_EXPLAIN'					=> 'Enter here a description for your download.',
	'ACP_NEW_DL_CAT'						=> 'Category',
	'ACP_NEW_DL_CAT_EXPLAIN'				=> 'Select here the category, where your download should stay in.',
	'ACP_NEW_DOWNLOAD'						=> 'New download',
	'ACP_NEW_DOWNLOAD_EXPLAIN'				=> 'Here you can add new downloads.',
	'ACP_NEW_DOWNLOAD_SIZE'					=> 'The maximum size of the file, which is allowed by your host, is <strong>%1$s %2$s</strong>! Due to the upload time you might need, this value can be lower!',
	'ACP_NEW_FILENAME'						=> 'File name',
	'ACP_NEW_FILENAME_EXPLAIN'				=> 'Select the file to upload.',
	'ACP_NEW_SUB_CAT_EXPLAIN'				=> 'Enter here the folder name, which you like to use on your webspace for this category (without slashes!).<br>This folder will then be created automatically under your root/ext/dmzx/downloadsystem/files/ folder.<br>Allowed characters are a-z, A-Z, 0-9, the hyphen ( - ) and the underscore ( _ ) signs.',
	'ACP_NEW_SUB_CAT_NAME'					=> 'Path name for the category',
	'ACP_NEW_TITLE'							=> 'Title',
	'ACP_NEW_TITLE_EXPLAIN'					=> 'Enter here the title for your new download.',
	'ACP_NEW_VERSION'						=> 'Version',
	'ACP_NEW_VERSION_EXPLAIN'				=> 'Enter here the version of your download.',
	'ACP_NO_CAT'							=> 'There are no categories available!<br>You first need to create at least one category, before you can start to add downloads!',
	'ACP_NO_CAT_ID'							=> 'No Cat ID',
	'ACP_NO_CAT_PARENT'						=> 'no parent category',
	'ACP_NO_CAT_UPLOAD'						=> 'There are no categories available!<br>You first need to create at least one category, before you can start adding files!',
	'ACP_NO_DOWNLOADS'						=> 'No Downloads yet',
	'ACP_NO_FILENAME'						=> 'You have to enter a file, which belongs to your upload!',
	'ACP_PAGINATION_ACP'					=> 'Set number of pagination on Manage Downloads page in ACP',
	'ACP_PAGINATION_ACP_EXPLAIN'			=> 'Set here, how much entries you want to see on the Manage Downloads page in ACP. <em>Default is 5.</em>',
	'ACP_PAGINATION_DOWNLOADS'				=> 'Set number of pagination on category page',
	'ACP_PAGINATION_DOWNLOADS_EXPLAIN'		=> 'Set here, how much entries you want to see on the category page. <em>Default is 25.</em>',
	'ACP_PAGINATION_ERROR_ACP'				=> 'You cannot set a value smaller than 5!',
	'ACP_PAGINATION_ERROR_USER'				=> 'You cannot set a value smaller than 3!',
	'ACP_PAGINATION_ERROR_DOWNLOADS'		=> 'You cannot set a value smaller than 10!',
	'ACP_PAGINATION_USER'					=> 'Set number of pagination on downloads page',
	'ACP_PAGINATION_USER_EXPLAIN'			=> 'Set here, how much entries you want to see on the downloads page. <em>Default is 3.</em>',
	'ACP_PARENT_OPTION_NAME'				=> 'Select a category',
	'ACP_REALLY_DELETE'						=> 'Are you sure, you want to delete your download?<br>The physical file on your web server will be deleted too!',
	'ACP_SINGLE_DOWNLOAD'					=> '1 download',
	'ACP_SORT_ASC'							=> 'Ascending',
	'ACP_SORT_CAT'							=> 'Category',
	'ACP_SORT_DESC'							=> 'Descending',
	'ACP_SORT_DIRECTION'					=> 'sort direction',
	'ACP_SORT_KEYS'							=> 'sort by ',
	'ACP_SORT_TITLE'						=> 'Title',
	'ACP_SUB_DL_CAT'						=> 'Subcategory',
	'ACP_SUB_NO_CAT'						=> '-----------',
	'ACP_SUB_DL_CAT_EXPLAIN'				=> 'Select here the subcategory.',
	'ACP_SUB_HAS_CAT_EXPLAIN'				=> 'This category has subcategories so cannot be linked to other category.',
	'ACP_UPLOAD_FILE_EXISTS'				=> 'The file you like to upload, does already exist in this category!',
	'ACP_WRONG_CHAR'						=> 'You entered a wrong character in the path name for the category!<br>Following characters are allowed: a-z, A-Z, 0-9, as well the hyphen ( - ) and the underscore ( _ )!',
	'ACP_MANAGE_CONFIG_EXPLAIN'				=> 'Here you can set a few basic values.',
	'ACP_SET_USERNAME'						=> 'Username for a transfer',
	'ACP_SET_USERNAME_EXPLAIN'				=> 'Here you can set a username, to which the download costs should be transferred to. Leave empty, if none should receive the above named costs.',
	'ACP_FTP_OR_UPLOAD'						=> 'You can do only a FTP upload <strong>OR</strong> normal upload!',
	'ACP_NEW_FTP_FILENAME_EXPLAIN'			=> 'Enter here the file name (ie. sample.zip), if you like to use the FTP upload method.',
	'ACP_NEW_FTP_FILENAME'					=> 'FTP file name',
	'ACP_UPLOAD_METHOD'						=> 'Upload Method',
	'ACP_UPLOAD_METHOD_EXPLAIN'				=> 'You can add a new upload via FTP or directly. If you are going to use the FTP upload method, the file needs to be uploaded to the correct category <strong>before</strong> you enter it here! You only can use on or the other method at a time!',
	'ACP_UPLOAD_FILE_NOT_EXISTS'			=> 'The file does not exists in the named category. Since you selected the FTP upload method, this file needs to be uploaded via FTP in the correct directory <strong>BEFORE</strong> you can add it!',
	'ACP_DM_EDS_IMAGE_SIZE'					=> 'Maximum image filesize',
	'ACP_DM_EDS_IMAGE_SIZE_EXPLAIN'			=> 'Maximum size of each file. If this value is 0, the uploadable filesize is only limited by your PHP configuration.<br>Average image filesizes: PNG ~ 2–4 kB, GIF ~ 6–8 kB, JPG ~ 9–12 kB',
	'ACP_DM_EDS_IMAGE_DIR'					=> 'Downloadsystem image upload directory',
	'ACP_DM_EDS_IMAGE_DIR_EXPLAIN'			=> 'Storage path for blog images. Please note that if you change this directory while already having uploaded blog images you need to manually copy the files to their new location.',
	'ACP_DM_EDS_IMAGE_CAT_DIR'				=> 'Category image upload directory',
	'ACP_DM_EDS_IMAGE_CAT_DIR_EXPLAIN'		=> 'Storage path for category images. Please note that if you change this directory while already having uploaded category images you need to manually copy the files to their new location.',
	'ACP_DM_EDS_ALLOW_DL_IMG'				=> 'Allow download file images',
	'ACP_DM_EDS_ALLOW_DL_IMG_EXPLAIN'		=> 'Allow images for files to show.',
	'ACP_DM_EDS_ALLOW_CAT_IMG'				=> 'Allow category images',
	'ACP_DM_EDS_ALLOW_CAT_IMG_EXPLAIN'		=> 'Allow category images to show.',
	'ACP_DM_EDS_ALLOW_BBCODES'				=> 'Allow BBCodes',
	'ACP_DM_EDS_ALLOW_BBCODES_EXPLAIN'		=> 'Allow the usage of BBCodes within the Downloadsystem extension.',
	'DM_EDS_ALLOW_SMILIES'					=> 'Allow smilies',
	'DM_EDS_ALLOW_SMILIES_EXPLAIN'			=> 'Allow the usage of smilies within the Downloadsystem extension.',
	'ACP_DM_EDS_ALLOW_MAGIC_URL'			=> 'Allow links',
	'ACP_DM_EDS_ALLOW_MAGIC_URL_EXPLAIN'	=> 'If disallowed, the <code>[URL]</code> BBCode and automatic/magic URLs are disabled within the Downloadsystem extension.',
	'ACP_NEW_IMAGE_DL'						=> 'Images',
	'ACP_DL_DOWNLOAD_IMAGE'					=> 'Download image',
	'ACP_DL_DOWNLOAD_IMAGE_EXPLAIN'			=> 'Upload an image for this file. Maximum filesize and the file directory is set within the configuration settings.',
	'ACP_DL_DOWNLOAD_IMAGE_CURRENT'			=> 'Current image',
	'ACP_CHANGEDLIMAGE'						=> 'Change image?',
	'ACP_DL_CATEGORY_IMAGE'					=> 'Category image',
	'ACP_DL_CATEGORY_IMAGE_EXPLAIN'			=> 'Upload an image for this file. Maximum filesize and the file directory is set within the configuration settings.',
	'ACP_DL_CATEGORY_IMAGE_CURRENT'			=> 'Current image',
	'ACP_CHANGECATIMAGE'					=> 'Change image?',
	'ACP_DM_CATEGORIES'						=> '',
	'ACP_DM_CATEGORIES_IMAGE'				=> 'Images',
	'ACP_DM_CATEGORIES_NAME'				=> 'Category name',
	'ACP_DM_CATEGORIES_DESC'				=> 'Description',
	'ACP_DM_CATEGORIES_SHOW_INDEX' 			=> 'Show on upload page',
	'ACP_SHOW_DONATION'						=> 'Show donation button before downloading a file',
	'ACP_SHOW_DONATION_EXPLAIN' 			=> 'Will display donation button when downloading a file.',
	'ACP_DONATION_URL'						=> 'URL to donation page',
	'ACP_DONATION_URL_EXPLAIN' 				=> 'Set URL to donation page. Will open in new tab and download will start automatically.',
]);
