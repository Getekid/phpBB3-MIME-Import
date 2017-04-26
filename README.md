# phpBB Mbox Import Extension

This is the repository for the development of the phpBB Mbox Import Extension.

## Install

1. Download the latest release.
2. Unzip the downloaded release and copy it to the `ext` directory of your phpBB board.
3. Download the [PHP MIME Email Message Parser](https://www.phpclasses.org/package/3169-PHP-Decode-MIME-e-mail-messages.html).
4. Unzip the downloaded class and copy it to the `ext/getekid/mboximport/acp` directory.
5. Navigate in the ACP to `Customise -> Manage extensions`.
6. Look for `Mbox Import` under the Disabled Extensions list, and click its `Enable` link.

## Uninstall

1. Navigate in the ACP to `Customise -> Extension Management -> Extensions`.
2. Look for `Mbox Import` under the Enabled Extensions list, and click its `Disable` link.
3. To permanently uninstall, click `Delete Data` and then delete the `/ext/getekid/mboximport` directory.

*Please note:* Permanently uninstalling the extension will delete all relations between posts and messages from the mbox file.

## Support

* Report bugs and other issues to our [Issue Tracker](https://github.com/phpbb-extensions/boardrules/issues).

## License
[GNU General Public License v2](http://opensource.org/licenses/GPL-2.0)
