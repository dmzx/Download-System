# Download System Extension

[![Build Status](https://travis-ci.org/dmzx/Download-System.svg?branch=master)](https://travis-ci.org/dmzx/Download-System)

## Install
1. Download the latest release.
2. In the `ext` directory of your phpBB board, create a new directory named `dmzx` (if it does not already exist).
3. Copy the `downloadsystem` folder to `phpBB/ext/dmzx/` (if done correctly, you'll have the main extension class at (your forum root)/ext/dmzx/downloadsystem/composer.json).
4. Navigate in the ACP to `Customise -> Manage extensions`.
5. Look for `Download System` under the Disabled Extensions list, and click its `Enable` link.

## Uninstall
1. Navigate in the ACP to `Customise -> Extension Management -> Extensions`.
2. Look for `Download System` under the Enabled Extensions list, and click its `Disable` link.
3. To permanently uninstall, click `Delete Data` and then delete the `/ext/dmzx/downloadsystem` folder.

## License
[GNU General Public License v2](http://opensource.org/licenses/GPL-2.0)
