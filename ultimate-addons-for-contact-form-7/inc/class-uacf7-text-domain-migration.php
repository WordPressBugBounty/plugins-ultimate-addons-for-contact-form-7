<?php
/**
 * Text domain migration.
 *
 * Migrates legacy translation files from:
 *
 * ultimate-addons-cf7
 *
 * to:
 *
 * ultimate-addons-for-contact-form-7
 *
 * This migration is intentionally non-destructive:
 *
 * - Old translation files are NEVER deleted.
 * - Existing new-domain files are NEVER overwritten.
 * - Failed migrations are retried later.
 * - Legacy translations can still be loaded if copying fails.
 *
 * @package UltimateAddonsForContactForm7
 */

defined( 'ABSPATH' ) || exit;

final class UACF7_Text_Domain_Migration {

	/**
	 * Previous, incorrect text domain.
	 */
	private const OLD_DOMAIN = 'ultimate-addons-cf7';

	/**
	 * Correct WordPress.org text domain.
	 */
	private const NEW_DOMAIN = 'ultimate-addons-for-contact-form-7';

	/**
	 * Internal migration version.
	 *
	 * This is NOT required to match your plugin version.
	 *
	 * If another translation migration is ever required in the future,
	 * change this to 1.1.0, 2.0.0, etc.
	 */
	private const MIGRATION_VERSION = '1.0.0';

	/**
	 * Option used to remember successful migration.
	 */
	private const MIGRATION_OPTION = 'uacf7_text_domain_migration_version';

	/**
	 * Safe location for legacy custom translations.
	 *
	 * We intentionally do NOT put migrated legacy translations into:
	 *
	 * wp-content/languages/plugins/
	 *
	 * because WordPress.org translation updates can overwrite files there.
	 */
	private const LEGACY_DIRECTORY = 'uacf7-legacy-translations';

	/**
	 * Main plugin file.
	 *
	 * @var string
	 */
	private static $plugin_file = '';

	/**
	 * Bootstrap migration.
	 *
	 * @param string $plugin_file Main plugin file.
	 *
	 * @return void
	 */
	public static function init( $plugin_file ) {

		self::$plugin_file = $plugin_file;

		/**
		 * Run the actual filesystem migration on the first admin request
		 * after updating the plugin.
		 */
		add_action(
			'admin_init',
			array( __CLASS__, 'maybe_migrate' ),
			5
		);

		/**
		 * Compatibility loader.
		 *
		 * This protects translations even if filesystem migration failed.
		 */
		add_action(
			'init',
			array( __CLASS__, 'load_compatibility_translations' ),
			0
		);

		/**
		 * Extra JIT fallback.
		 *
		 * Useful for locale switching and situations where WordPress attempts
		 * to load the new domain before our normal compatibility loader.
		 */
		add_filter(
			'load_textdomain_mofile',
			array( __CLASS__, 'legacy_mofile_fallback' ),
			10,
			2
		);
	}

	/**
	 * Run migration if it has not already completed.
	 *
	 * @return void
	 */
	public static function maybe_migrate() {

		$current_migration = self::get_migration_version();

		/**
		 * Already migrated.
		 */
		if (
			version_compare(
				$current_migration,
				self::MIGRATION_VERSION,
				'>='
			)
		) {
			return;
		}

		/**
		 * Only a user capable of updating plugins should cause filesystem
		 * migration to run.
		 */
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$success = true;

		/*
		 * ---------------------------------------------------------
		 * 1. Standard WordPress language directory
		 * ---------------------------------------------------------
		 *
		 * OLD:
		 *
		 * wp-content/languages/plugins/
		 * ultimate-addons-cf7-fr_FR.mo
		 *
		 * We DO NOT copy this to:
		 *
		 * wp-content/languages/plugins/
		 * ultimate-addons-for-contact-form-7-fr_FR.mo
		 *
		 * because WordPress.org may overwrite it later.
		 *
		 * Instead it becomes:
		 *
		 * wp-content/languages/uacf7-legacy-translations/
		 * ultimate-addons-for-contact-form-7-fr_FR.mo
		 */
		$wordpress_language_directory =
			trailingslashit( WP_LANG_DIR ) . 'plugins';

		$protected_legacy_directory =
			trailingslashit( WP_LANG_DIR ) . self::LEGACY_DIRECTORY;

		if (
			! self::migrate_directory(
				$wordpress_language_directory,
				$protected_legacy_directory
			)
		) {
			$success = false;
		}

		/*
		 * ---------------------------------------------------------
		 * 2. Loco Translate custom translations
		 * ---------------------------------------------------------
		 *
		 * OLD:
		 *
		 * wp-content/languages/loco/plugins/
		 * ultimate-addons-cf7-fr_FR.mo
		 *
		 * NEW:
		 *
		 * wp-content/languages/loco/plugins/
		 * ultimate-addons-for-contact-form-7-fr_FR.mo
		 *
		 * The Loco custom directory is already intended for custom,
		 * update-safe translations, so keeping the migrated file there
		 * is appropriate.
		 */
		$loco_directory =
			trailingslashit( WP_LANG_DIR ) . 'loco/plugins';

		if (
			is_dir( $loco_directory ) &&
			! self::migrate_directory(
				$loco_directory,
				$loco_directory
			)
		) {
			$success = false;
		}

		/**
		 * Only mark migration completed when every required copy worked.
		 *
		 * If something failed because of permissions, the old files remain
		 * untouched and this migration will retry during a later admin request.
		 */
		if ( $success ) {
			self::set_migration_version(
				self::MIGRATION_VERSION
			);
		}
	}

	/**
	 * Copy old-domain translation files to new-domain filenames.
	 *
	 * This NEVER deletes the source file and NEVER overwrites a target.
	 *
	 * Supported files:
	 *
	 * .mo
	 * .po
	 * .l10n.php
	 *
	 * @param string $source_directory Source directory.
	 * @param string $target_directory Destination directory.
	 *
	 * @return bool
	 */
	private static function migrate_directory(
		$source_directory,
		$target_directory
	) {

		if ( ! is_dir( $source_directory ) ) {
			return true;
		}

		$pattern =
			trailingslashit( $source_directory ) .
			self::OLD_DOMAIN .
			'-*';

		$files = glob( $pattern );

		if ( false === $files || empty( $files ) ) {
			return true;
		}

		$translation_files = array();

		foreach ( $files as $file ) {

			if ( ! is_file( $file ) ) {
				continue;
			}

			$basename = basename( $file );

			if ( ! self::is_supported_translation_file( $basename ) ) {
				continue;
			}

			$translation_files[] = $file;
		}

		if ( empty( $translation_files ) ) {
			return true;
		}

		/**
		 * Create destination only when we actually have something to migrate.
		 */
		if (
			! is_dir( $target_directory ) &&
			! wp_mkdir_p( $target_directory )
		) {
			return false;
		}

		$success = true;

		foreach ( $translation_files as $source_file ) {

			$old_filename = basename( $source_file );

			/**
			 * Everything after the old domain remains unchanged.
			 *
			 * Example:
			 *
			 * ultimate-addons-cf7-de_DE.mo
			 *
			 * becomes:
			 *
			 * ultimate-addons-for-contact-form-7-de_DE.mo
			 */
			$suffix = substr(
				$old_filename,
				strlen( self::OLD_DOMAIN )
			);

			$new_filename =
				self::NEW_DOMAIN .
				$suffix;

			$target_file =
				trailingslashit( $target_directory ) .
				$new_filename;

			/**
			 * Very important:
			 *
			 * Never overwrite an existing translation using the correct
			 * text domain. The existing one could be newer or edited by
			 * the user.
			 */
			if ( file_exists( $target_file ) ) {
				continue;
			}

			if ( ! is_readable( $source_file ) ) {
				$success = false;
				continue;
			}

			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}

			if ( ! $wp_filesystem->is_writable( $target_directory ) ) {
				$success = false;
				continue;
			}

			/**
			 * COPY, never rename.
			 *
			 * Keeping the original file makes rollback completely safe.
			 */
			$copied = copy(
				$source_file,
				$target_file
			);

			if ( ! $copied ) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Check whether this is a translation file we want to preserve.
	 *
	 * @param string $filename Filename.
	 *
	 * @return bool
	 */
	private static function is_supported_translation_file( $filename ) {

		$extensions = array(
			'.mo',
			'.po',
			'.l10n.php',
		);

		foreach ( $extensions as $extension ) {

			if (
				substr(
					$filename,
					-strlen( $extension )
				) === $extension
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Load existing custom/legacy translations under the NEW domain.
	 *
	 * Custom translation is loaded FIRST.
	 *
	 * WordPress's load_textdomain() merges translations when the same domain
	 * has already been loaded, and existing/original translations win when
	 * duplicate strings exist.
	 *
	 * This means:
	 *
	 * user custom translation
	 *     +
	 * official WordPress.org translation
	 *
	 * can coexist.
	 *
	 * @return void
	 */
	public static function load_compatibility_translations() {

		$locale = determine_locale();

		/*
		 * ---------------------------------------------------------
		 * Custom/legacy translation priority
		 * ---------------------------------------------------------
		 *
		 * 1. Newly migrated Loco file
		 * 2. Protected migrated custom translation
		 * 3. Original old Loco translation
		 * 4. Original old wp-content/languages/plugins translation
		 * 5. Old bundled translation, if still present
		 */
		$custom_candidates = array(
			trailingslashit( WP_LANG_DIR ) .
				'loco/plugins/' .
				self::NEW_DOMAIN .
				'-' .
				$locale .
				'.mo',

			trailingslashit( WP_LANG_DIR ) .
				self::LEGACY_DIRECTORY .
				'/' .
				self::NEW_DOMAIN .
				'-' .
				$locale .
				'.mo',

			trailingslashit( WP_LANG_DIR ) .
				'loco/plugins/' .
				self::OLD_DOMAIN .
				'-' .
				$locale .
				'.mo',

			trailingslashit( WP_LANG_DIR ) .
				'plugins/' .
				self::OLD_DOMAIN .
				'-' .
				$locale .
				'.mo',
		);

		/**
		 * Also support an old bundled language file if one happens
		 * to remain in the new release.
		 */
		if ( self::$plugin_file ) {

			$custom_candidates[] =
				trailingslashit(
					plugin_dir_path( self::$plugin_file )
				) .
				'languages/' .
				self::OLD_DOMAIN .
				'-' .
				$locale .
				'.mo';
		}

		/**
		 * Load only the highest-priority custom translation.
		 */
		foreach ( $custom_candidates as $candidate ) {

			if ( self::runtime_translation_exists( $candidate ) ) {

				self::load_translation(
					$candidate,
					$locale
				);

				break;
			}
		}

		/*
		 * ---------------------------------------------------------
		 * Official WordPress.org language pack
		 * ---------------------------------------------------------
		 *
		 * This is loaded AFTER the user's existing custom translation.
		 *
		 * load_textdomain() merges them, while already loaded translations
		 * retain priority.
		 */
		$official_file =
			trailingslashit( WP_LANG_DIR ) .
			'plugins/' .
			self::NEW_DOMAIN .
			'-' .
			$locale .
			'.mo';

		if ( self::runtime_translation_exists( $official_file ) ) {

			self::load_translation(
				$official_file,
				$locale
			);

			return;
		}

		/*
		 * ---------------------------------------------------------
		 * Bundled NEW-domain translation
		 * ---------------------------------------------------------
		 *
		 * Only use this when no WordPress.org language pack exists.
		 */
		if ( self::$plugin_file ) {

			$bundled_file =
				trailingslashit(
					plugin_dir_path( self::$plugin_file )
				) .
				'languages/' .
				self::NEW_DOMAIN .
				'-' .
				$locale .
				'.mo';

			if (
				self::runtime_translation_exists(
					$bundled_file
				)
			) {
				self::load_translation(
					$bundled_file,
					$locale
				);
			}
		}
	}

	/**
	 * JIT fallback for the new text domain.
	 *
	 * WordPress may request:
	 *
	 * wp-content/languages/plugins/
	 * ultimate-addons-for-contact-form-7-fr_FR.mo
	 *
	 * If that does not exist yet, this allows an old/migrated custom
	 * translation to be used instead.
	 *
	 * @param string $mofile Requested MO file.
	 * @param string $domain Text domain.
	 *
	 * @return string
	 */
	public static function legacy_mofile_fallback(
		$mofile,
		$domain
	) {

		if ( self::NEW_DOMAIN !== $domain ) {
			return $mofile;
		}

		/**
		 * If the correct translation already exists, do not interfere.
		 */
		if ( self::runtime_translation_exists( $mofile ) ) {
			return $mofile;
		}

		$filename = basename( $mofile );

		$expected_prefix =
			self::NEW_DOMAIN .
			'-';

		if (
			0 !== strpos(
				$filename,
				$expected_prefix
			)
		) {
			return $mofile;
		}

		/**
		 * Example suffix:
		 *
		 * -fr_FR.mo
		 */
		$suffix = substr(
			$filename,
			strlen( self::NEW_DOMAIN )
		);

		$candidates = array(
			trailingslashit( WP_LANG_DIR ) .
				'loco/plugins/' .
				self::NEW_DOMAIN .
				$suffix,

			trailingslashit( WP_LANG_DIR ) .
				self::LEGACY_DIRECTORY .
				'/' .
				self::NEW_DOMAIN .
				$suffix,

			trailingslashit( WP_LANG_DIR ) .
				'loco/plugins/' .
				self::OLD_DOMAIN .
				$suffix,

			trailingslashit( WP_LANG_DIR ) .
				'plugins/' .
				self::OLD_DOMAIN .
				$suffix,
		);

		if ( self::$plugin_file ) {

			$candidates[] =
				trailingslashit(
					plugin_dir_path( self::$plugin_file )
				) .
				'languages/' .
				self::OLD_DOMAIN .
				$suffix;
		}

		foreach ( $candidates as $candidate ) {

			if (
				self::runtime_translation_exists(
					$candidate
				)
			) {
				return $candidate;
			}
		}

		return $mofile;
	}

	/**
	 * Does an MO/PHP runtime translation exist?
	 *
	 * WordPress 6.5+ supports .l10n.php files and prefers them over .mo.
	 * Calling load_textdomain() with the .mo path lets WordPress automatically
	 * try the corresponding .l10n.php file first.
	 *
	 * @param string $mo_file MO-style filename.
	 *
	 * @return bool
	 */
	private static function runtime_translation_exists( $mo_file ) {

		if ( is_readable( $mo_file ) ) {
			return true;
		}

		global $wp_version;

		/**
		 * .l10n.php support was introduced in WordPress 6.5.
		 */
		if (
			version_compare(
				$wp_version,
				'6.5',
				'>='
			)
		) {

			$l10n_php_file =
				substr(
					$mo_file,
					0,
					-3
				) .
				'.l10n.php';

			if ( is_readable( $l10n_php_file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Load translation file using the NEW text domain.
	 *
	 * @param string $mo_file File path.
	 * @param string $locale  Locale.
	 *
	 * @return bool
	 */
	private static function load_translation(
		$mo_file,
		$locale
	) {

		global $wp_version;

		/**
		 * Third $locale parameter was added in WordPress 6.1.
		 */
		if (
			version_compare(
				$wp_version,
				'6.1',
				'>='
			)
		) {
			return load_textdomain(
				self::NEW_DOMAIN,
				$mo_file,
				$locale
			);
		}

		return load_textdomain(
			self::NEW_DOMAIN,
			$mo_file
		);
	}

	/**
	 * Read migration state.
	 *
	 * Multisite uses a network option because WP_LANG_DIR is shared.
	 *
	 * @return string
	 */
	private static function get_migration_version() {

		if ( is_multisite() ) {

			return (string) get_site_option(
				self::MIGRATION_OPTION,
				'0'
			);
		}

		return (string) get_option(
			self::MIGRATION_OPTION,
			'0'
		);
	}

	/**
	 * Save migration state.
	 *
	 * @param string $version Migration version.
	 *
	 * @return void
	 */
	private static function set_migration_version( $version ) {

		if ( is_multisite() ) {

			update_site_option(
				self::MIGRATION_OPTION,
				$version
			);

			return;
		}

		update_option(
			self::MIGRATION_OPTION,
			$version,
			false
		);
	}
}