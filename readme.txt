=== Simply BackItUp ===
Contributors: amdarter
Tags: backup, site backup, database backup, scheduled backups, restore, wordpress backup
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Easily backup your WordPress site with a few clicks.

== Description ==

**Simply BackItUp** is a user-friendly WordPress plugin designed to simplify the process of backing up your website. With just a few clicks, you can create a complete backup of your site, ensuring your data is secure and easily restorable.

**Important Requirements and Limitations:**

- **PHP 8.0 or higher**
- **Memory Limit:** Minimum of **256MB** PHP memory limit recommended.
- **No Multisite Support:** This plugin **does not support WordPress Multisite installations**.

**Features:**

- **One-Click Backup:** Easily back up your entire WordPress site with just one click.
- **Scheduled Backups:** Schedule automatic backups to run at regular intervals.
- **Cloud Storage Integration:** Seamlessly back up your WordPress site to FTP servers or directly to AWS S3.
- **Restore with Ease:** Quickly restore your website from any backup.
- **Backup Management:** Manage and delete old backups to save space.
- **Email Notifications:** Receive email notifications on successful or failed backups.
- **Custom Backup Options:** Select specific files and databases to include in your backup.

**Benefits:**

- **User-Friendly Interface:** Intuitive design makes it easy for anyone to back up their site.
- **Secure Backups:** Ensures your data is safe from accidental loss or malicious attacks.
- **Flexibility:** Customize your backup settings to suit your specific needs.

For more information, visit the [Simply BackItUp GitHub Repository](https://github.com/amdarter/Simply-BackItUp).

== Installation ==

1. **Verify Requirements:**
   - Ensure your server is running **PHP 8.0** or higher.
   - Confirm that you have at least **256MB** of PHP memory limit.
   - **Note:** This plugin **does not support WordPress Multisite installations**.

2. **Upload the Plugin Files:**

   - Upload the `simply-backitup` folder to the `/wp-content/plugins/` directory using FTP or your hosting provider's file manager.

3. **Activate the Plugin:**

   - Go to the 'Plugins' menu in WordPress and click 'Activate' next to **Simply BackItUp**.

4. **Configure Settings:**

   - Navigate to the 'BackItUp' menu item in the WordPress admin dashboard.
   - Configure your backup settings as desired.

5. **Start Backing Up:**
   - Use the 'Save & Backup Now' button to create your first backup.

== Frequently Asked Questions ==

= What are the minimum requirements to use Simply BackItUp? =

- **PHP Version:** Your server must be running PHP 8.0 or higher.
- **Memory Limit:** A minimum of 256MB PHP memory limit is recommended for optimal performance.

= Is Simply BackItUp free? =

Yes, Simply BackItUp is currently free to use with all its core features.

= How do I restore a backup? =

Navigate to the 'BackItUp' interface, select the backup you wish to restore, and click 'Restore'. Follow the on-screen instructions to complete the restoration process.

= Can I schedule automatic backups? =

Yes, you can set up scheduled backups in the plugin settings. Choose your preferred frequency (daily, weekly, monthly).

= Which cloud storage providers are supported? =

Currently, Simply BackItUp supports FTP and AWS S3.

= How do I receive email notifications? =

In the plugin settings, enter the email address where you'd like to receive notifications. You can choose to be notified on successful backups, failures, or both.

= Is there a limit to the number of backups I can store? =

There's no limit within the plugin itself. However, storage limitations depend on your cloud storage service.

= How do I customize the what is included in the backup? =

In the backup settings, you can select to Backup Files, Database, Plugins, Themes, and uploads.

== Screenshots ==

**Main Backup Interface** - The primary interface where you can initiate backups.


![Main Backup Interface](assets/screenshot-1.png)

== Changelog ==

= 1.0 =

- Initial release of Simply BackItUp.
- Features include one-click backup, scheduled backups, restore functionality, backup management, email notifications, and custom backup options.

== Upgrade Notice ==

= 1.0 =
This is the initial release of Simply BackItUp. No upgrade steps are necessary.

== License ==

This plugin is licensed under the [MIT License](https://opensource.org/licenses/MIT).

== Developer Documentation ==

For developers interested in contributing or customizing Simply BackItUp, please visit our GitHub repository:

[Simply BackItUp on GitHub](https://github.com/amdarter/Simply-BackItUp)

== Additional Information ==

**Support:**

If you have any issues or questions, please use the [support forum](https://wordpress.org/support/plugin/simply-backitup/) on WordPress.org or contact [anthonymdarter@gmail.com](mailto:anthonymdarter@gmail.com).

**Credits:**

Developed by [Anthony M. Darter](http://yourwebsite.com/).

---
