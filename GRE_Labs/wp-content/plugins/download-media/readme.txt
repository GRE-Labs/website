=== Download Media ===
Contributors: jean-david
Tags: media, image, download, library, post thumbnails
Requires at least: 4.4
Tested up to: 6.3
Requires PHP: 5.3.0
Stable tag: 1.4.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allows medias in the media library to be direclty download one by one or in bulk.

== Description ==

Download Media allows you to directly download medias from the media library to your device in one click.

You can download medias one by one, or in bulk.

= Need Help? Found A Bug? Want To Contribute Code? =

Support for this plugin is provided via the [WordPress.org forums](https://wordpress.org/support/plugin/download-media).

The source code for this plugin is available on [GitHub](https://github.com/JeanDavidDaviet/download-media).

== List of hook available ==

**Filters**

* *download_media_settings_cap* - Set default capability for plugin's settings modifications
* *download_media_output_dir* - Set default output directory for plugin's zip files
* *download_media_cron_intervals* - Allow the modification of the plugin's cron intervals
* *download_media_cron_daily_second* - Allow the modification of the daily cron interval of time
* *download_media_cron_weekly_second* - Allow the modification of the weekly cron interval of time
* *download_media_cron_montly_second* - Allow the modification of the monthly cron interval of time
* *download_media_zip_directory* - Set default zip files directory location

== Installation ==

1. Go to your admin area and select Plugins → Add New from the menu.
2. Search for "Download Media".
3. Click install.
4. Click activate.
5. Navigate to Media → Library

== Screenshots ==

1. List view - Link to download under each image
2. List view - Bulk download
3. Grid view - Link to download over each image (on hover)
4. Media preview popup - Button to download on bottom
5. Plugin settings

== ChangeLog ==

= Version 1.4.2 =
* Fix the download link not working with non image files

= Version 1.4.1 =
* Fix the not found directory of the zip output
* Add a filter to change the zip output default directory

= Version 1.4 =
* Add a dropdown choice to select every medias of the WordPress site

= Version 1.3.3 =
* Fix a typo leading PHP to think the ZipArchive class wasn't installed

= Version 1.3.2 =
* Fix namespace issue causing fatal error when using the bulk downloader

= Version 1.3.1 =
* Add a download link in the edit attachment postmeta box

= Version 1.3 =
* Add filter hooks
* Add plugin screenshots

= Version 1.2 =
* Add Grid UI media download
* Refactor classes

= Version 1.1 =
* use DownloadMedia class for plugin containerisation
* fix should delete option not updating cron job

= Version 1.0.0 =
* Initial release.
