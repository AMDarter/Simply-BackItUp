# Simply-BackItUp

**Simply-BackItUp** is a WordPress plugin designed to simplify the process of backing up your website. Currently under development, this plugin aims to provide a user-friendly interface and powerful features to ensure your website's data is secure and easily restorable.

## Features (Planned)

- **One-Click Backup:** Easily back up your entire WordPress site with just one click.
- **Scheduled Backups:** Schedule automatic backups to run at regular intervals.
- **Cloud Storage Integration:** Save backups directly to your preferred cloud storage provider (e.g., Google Drive, Dropbox, AWS S3).
- **Restore with Ease:** Quickly restore your website from any backup.
- **Backup Management:** Manage and delete old backups to save space.
- **Email Notifications:** Receive email notifications on successful or failed backups.
- **Custom Backup Options:** Select specific files and databases to include in your backup.

## Developer Guide

### Checksum Validation
By default, Simply BackItUp handles checksum validation for the core WordPress files to ensure file integrity during backups. However, you can customize which files are included or excluded during the checksum validation process using the WordPress `add_filter` functionality. This allows you to either **add custom files** to be validated or **ignore specific files** that you do not want to validate as part of the backup process.

When backing up files, it's important to ensure that they haven't been altered, corrupted, or tampered with by an unauthorized third party. To verify file integrity, MD5 hashing can be used to generate a unique "fingerprint" for the file. If the file changes, even slightly, the MD5 checksum will differ, signaling potential tampering with the file.

#### Example 1: Adding Custom Files for Validation
To generate a checksum for custom files during the backup process, use PHP's built-in `md5_file()` function. This function reads a file and returns an MD5 hash that can then be compared during validation.

Do not call `md5_file()` dynamically during runtime to verify file integrity. The purpose of verification is to compare the file against a pre-calculated checksum. Pre-generate the hashes for critical files and store them in a secure non-public area. This way, you can reference the stored "fingerprint" during validation.

For core WordPress file checksums, you can reference the official WordPress Checksums API, which provides checksum values for specific WordPress versions and locales.

Here's how you can add custom files for MD5 checksum validation:

```php
/**
 * Add custom files to the list of checksums for validation.
 */
add_filter('simplybackitup_filter_checksums', function ($checksums) {
    // Add custom files with their respective checksums
    $customFiles = [
        'wp-content/my-custom-plugin/plugin.php' => '8111a8d605183b921cb237a1406afcd9',
        'wp-content/my-custom-theme/functions.php' => 'a7a6f1bc16e1f5c9bdd1b08d95151d11'
    ];

    // Merge custom files into the existing list of checksums
    return array_merge($checksums, $customFiles);
});
```

#### Example 2: Ignoring Files from Validation

You can ignore certain files (like `readme.html`, `wp-config-sample.php`, or specific directories) by filtering out their checksums using the `simplybackitup_filter_checksums` filter.

```php
/**
 * Filter the list of known checksums to exclude certain files from validation.
 */
add_filter('simplybackitup_filter_checksums', function ($checksums) {
    // List of files or directories to ignore during the checksum validation
    $ignoreFiles = [
        'readme.html', // Ignore the readme file
        'license.txt', // Ignore the license file
        'wp-config-sample.php', // Ignore sample config file
    ];

    // Filter out ignored files from the checksums array
    return array_filter($checksums, function ($file) use ($ignoreFiles) {
        foreach ($ignoreFiles as $pattern) {
            if (preg_match("|^{$pattern}|", $file)) {
                return false;  // Ignore files matching these patterns
            }
        }
        return true; // Keep the rest
    });
});
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
