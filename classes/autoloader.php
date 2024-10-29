<?php 
/**
 * Autoloader.
 *
 * @since   1.0.1
 *
 * @package Generic\Autoloader
 * 
 * @link https://gist.github.com/schlessera/708c10c3abd924cba27f3c588056e823
 */
namespace PISOL\REVIEW\AUTOLOADER;

use RuntimeException;

class Autoloader {

	/**
	 * Array containing the registered namespace structures
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $namespaces = [];

	/**
	 * Destructor for the Autoloader class.
	 *
	 * The destructor automatically unregisters the autoload callback function
	 * with the SPL autoload system.
	 *
	 * @since  1.0.0
	 */
	public function __destruct() {
		$this->unregister();
	}

	/**
	 * Registers the autoload callback with the SPL autoload system.
	 *
	 * @since    1.0.0
	 */
	public function register() {
		spl_autoload_register( [ $this, 'autoload' ] );
	}

	/**
	 * Unregisters the autoload callback with the SPL autoload system.
	 *
	 * @since    1.0.0
	 */
	public function unregister() {
		spl_autoload_unregister( [ $this, 'autoload' ] );
	}

	/**
	 * Add a specific namespace structure with our custom autoloader.
	 *
	 * @since  1.0.0
	 *
	 * @param string  $root        Root namespace name.
	 * @param string  $base_dir    Directory containing the class files.
	 * @param string  $prefix      Prefix to be added before the class.
	 * @param string  $suffix      Suffix to be added after the class.
	 * @param boolean $lowercase   Whether the class should be changed to
	 *                             lowercase.
	 * @param boolean $underscores Whether the underscores should be changed to
	 *                             hyphens.
	 * @return self
	 */
	public function add_namespace(
		$root,
		$base_dir,
		$prefix = '',
		$suffix = '.php',
		$lowercase = false,
		$underscores = false
	) {
		$this->namespaces[] = [
			'root'        => $this->normalize_root( (string) $root ),
			'base_dir'    => trailingslashit( (string) $base_dir ),
			'prefix'      => (string) $prefix,
			'suffix'      => (string) $suffix,
			'lowercase'   => (bool) $lowercase,
			'underscores' => (bool) $underscores,
		];

		usort($this->namespaces, function($a, $b) {
			return strlen($b['root']) - strlen($a['root']);
		});

		return $this;
	}

	/**
	 * The autoload function that gets registered with the SPL Autoloader
	 * system.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class The class that got requested by the spl_autoloader.
	 * @throws RuntimeException If the associated file is not readable.
	 */
	public function autoload( $class ) {

		// Iterate over namespaces to find a match.
		foreach ( $this->namespaces as $namespace ) {

			// Move on if the object does not belong to the current namespace.
			if ( 0 !== strpos( $class, $namespace['root'] ) ) {
				continue;
			}

			// Remove namespace root level to correspond with root filesystem.
			$filename = str_replace(
				$namespace['root'], '',
				$class
			);

			// Replace the namespace separator "\" by the system-dependent
			// directory separator.
			$filename = str_replace(
				'\\', DIRECTORY_SEPARATOR,
				$filename
			);

			// Remove a leading backslash from the class name.
			$filename = $this->remove_leading_backslash( $filename );

			// Change to lower case if requested.
			if ( $namespace['lowercase'] ) {
				$filename = strtolower( $filename );
			}

			// Change underscores into hyphens if requested.
			if ( $namespace['underscores'] ) {
				$filename = str_replace( '_', '-', $filename );
			}

			// Add base_dir, prefix and suffix.
			$filepath = $namespace['base_dir']
			            . $namespace['prefix']
			            . $filename
			            . $namespace['suffix'];

			// Throw an exception if the file does not exist or is not readable.
			if ( ! is_readable( $filepath ) ) {
				throw new RuntimeException(
					esc_html(sprintf(
						'Could not autoload class "%1$s", the corresponding file "%2$s" is not readable.',
						$class,
						$filepath
					))
				);
			}

			require( $filepath );
			return;
		}
	}

	/**
	 * Normalize a namespace root.
	 *
	 * @since 1.0.0
	 *
	 * @param string $root Namespace root that needs to be normalized.
	 * @return string Normalized namespace root.
	 */
	protected function normalize_root( $root ) {
		$root = $this->remove_leading_backslash( $root );
		return $this->add_trailing_backslash( $root );
	}

	/**
	 * Remove a leading backslash from a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string String to remove the leading backslash from.
	 * @return string Modified string.
	 */
	protected function remove_leading_backslash( $string ) {
		return ltrim( $string, '\\' );
	}

	/**
	 * Make sure a string ends with a trailing backslash.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string String to check the trailing backslash of.
	 * @return string Modified string.
	 */
	protected function add_trailing_backslash( $string ) {
		return rtrim( $string, '\\' ) . '\\';
	}
}

$auto_loader = new Autoloader();

$auto_loader->add_namespace(
    'PISOL\\REVIEW\\BASE\\',
    PISOL_REVIEW_PATH.'classes/',
    'class-',
    '.php',
    true,
    false
);

$auto_loader->add_namespace(
    'PISOL\\REVIEW\\FRONT\\',
    PISOL_REVIEW_PATH.'public/',
    'class-',
    '.php',
    true,
    false
);



$auto_loader->add_namespace(
    'PISOL\\REVIEW\\ADMIN\\',
    PISOL_REVIEW_PATH.'admin/',
    'class-',
    '.php',
    true,
    false
);

$auto_loader->register();
