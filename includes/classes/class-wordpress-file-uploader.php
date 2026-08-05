<?php
/**
 * WordPress Standard File Uploader
 *
 * A WordPress-native file uploader class that handles file uploads,
 * validation, and processing using WordPress standard functions.
 *
 * @package WP_Ulike_Pro
 * @since 1.9.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WP_Ulike_Pro_WordPress_File_Uploader {

	/**
	 * Upload configuration
	 *
	 * @var array
	 */
	private $config = array();

	/**
	 * File data from $_FILES
	 *
	 * @var array
	 */
	private $file = null;

	/**
	 * WordPress uploads directory (stored to prevent recursion)
	 *
	 * @var array
	 */
	private $uploads_dir = null;

	/**
	 * Flag to prevent filter recursion
	 *
	 * @var bool
	 */
	private $filter_applied = false;

	/**
	 * Constructor
	 *
	 * @param array $file $_FILES array element
	 * @param array $config Upload configuration
	 */
	public function __construct( $file, $config = array() ) {
		$this->file = $file;
		$this->config = wp_parse_args( $config, $this->get_default_config() );
	}

	/**
	 * Get default configuration
	 *
	 * @return array
	 */
	private function get_default_config() {
		return array(
			'max_size'      => 5, // MB
			'allowed_types' => array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ),
			'upload_dir'    => null, // If null, uses WordPress default uploads directory
			'replace'       => false, // Replace existing file or generate unique name
			'filename'      => null, // Custom filename (without extension), null = random
			'user_id'       => 0, // User ID for filename generation (optional)
			'quality'       => 90, // Image quality for processing
			'max_width'     => null, // Max width for image resizing
			'max_height'    => null, // Max height for image resizing
		);
	}

	/**
	 * Upload file
	 *
	 * @return array|WP_Error Upload result with 'file', 'url', 'name', 'size', 'type' keys, or WP_Error on failure
	 */
	public function upload() {
		// Validate file
		$validation = $this->validate();
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Prepare upload directory
		$upload_dir_result = $this->prepare_upload_directory();
		if ( is_wp_error( $upload_dir_result ) ) {
			return $upload_dir_result;
		}

		// Prepare upload overrides
		$upload_overrides = $this->get_upload_overrides();

		// Load WordPress file functions if not already loaded
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		// Store uploads directory before filtering to prevent recursion
		$this->uploads_dir = wp_get_upload_dir();

		// Filter upload directory if custom directory is set
		if ( ! empty( $this->config['upload_dir'] ) ) {
			add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ), 999, 1 );
		}

		try {
			$upload_result = wp_handle_upload( $this->file, $upload_overrides );
		} catch ( \Exception $e ) {
			if ( ! empty( $this->config['upload_dir'] ) ) {
				remove_filter( 'upload_dir', array( $this, 'filter_upload_dir' ), 999 );
			}
			return new \WP_Error( 'upload_exception', $e->getMessage() );
		} catch ( \Error $e ) {
			if ( ! empty( $this->config['upload_dir'] ) ) {
				remove_filter( 'upload_dir', array( $this, 'filter_upload_dir' ), 999 );
			}
			return new \WP_Error( 'upload_error', $e->getMessage() );
		}

		// Remove filter
		if ( ! empty( $this->config['upload_dir'] ) ) {
			remove_filter( 'upload_dir', array( $this, 'filter_upload_dir' ), 999 );
		}

		// Check for upload errors
		if ( isset( $upload_result['error'] ) ) {
			return new \WP_Error( 'upload_failed', $upload_result['error'] );
		}

		// Process image if needed
		if ( $this->is_image( $upload_result['file'] ) ) {
			$image_result = $this->process_image( $upload_result['file'] );
			if ( is_wp_error( $image_result ) ) {
				// Image processing failed, but file was uploaded
				// Log the error but don't fail the upload
			}
		}

		// Build response
		$file_type = wp_check_filetype( $upload_result['file'] );
		$file_name = basename( $upload_result['file'] );

		return array(
			'file'      => $upload_result['file'],
			'url'       => $upload_result['url'],
			'name'      => $file_name,
			'title'     => pathinfo( $file_name, PATHINFO_FILENAME ),
			'size'      => filesize( $upload_result['file'] ),
			'size2'     => $this->format_file_size( filesize( $upload_result['file'] ) ),
			'type'      => $file_type['type'],
			'extension' => $file_type['ext'],
		);
	}

	/**
	 * Validate file
	 *
	 * @return true|WP_Error
	 */
	private function validate() {
		// Check for upload errors
		if ( ! empty( $this->file['error'] ) ) {
			$error_messages = array(
				UPLOAD_ERR_INI_SIZE   => esc_html__( 'File exceeds upload_max_filesize in php.ini.', 'wp-ulike-pro' ),
				UPLOAD_ERR_FORM_SIZE  => esc_html__( 'File exceeds MAX_FILE_SIZE in form.', 'wp-ulike-pro' ),
				UPLOAD_ERR_PARTIAL    => esc_html__( 'File was only partially uploaded.', 'wp-ulike-pro' ),
				UPLOAD_ERR_NO_FILE    => esc_html__( 'No file uploaded.', 'wp-ulike-pro' ),
				UPLOAD_ERR_NO_TMP_DIR => esc_html__( 'Missing temporary folder.', 'wp-ulike-pro' ),
				UPLOAD_ERR_CANT_WRITE => esc_html__( 'Failed to write file to disk.', 'wp-ulike-pro' ),
				UPLOAD_ERR_EXTENSION  => esc_html__( 'File upload stopped by extension.', 'wp-ulike-pro' ),
			);
			$message = isset( $error_messages[ $this->file['error'] ] )
				? $error_messages[ $this->file['error'] ]
				: esc_html__( 'Unknown upload error.', 'wp-ulike-pro' );
			return new \WP_Error( 'upload_error', $message );
		}

		// Check file size
		$max_size = $this->config['max_size'] * 1024 * 1024; // Convert MB to bytes
		if ( $this->file['size'] > $max_size ) {
			return new \WP_Error(
				'file_too_large',
				sprintf(
					esc_html__( 'File is too large. Maximum size is %s.', 'wp-ulike-pro' ),
					size_format( $max_size )
				)
			);
		}

		// Security: Check file was actually uploaded (not fake path)
		if ( ! is_uploaded_file( $this->file['tmp_name'] ) ) {
			return new \WP_Error(
				'invalid_upload',
				esc_html__( 'Invalid file upload. Security check failed.', 'wp-ulike-pro' )
			);
		}

		// Validate file type by extension
		$file_type = wp_check_filetype( $this->file['name'] );
		$allowed_types = array_map( 'strtolower', $this->config['allowed_types'] );

		if ( empty( $file_type['ext'] ) || ! in_array( strtolower( $file_type['ext'] ), $allowed_types, true ) ) {
			return new \WP_Error(
				'invalid_file_type',
				esc_html__( 'Invalid file type. Allowed types: ', 'wp-ulike-pro' ) . implode( ', ', $allowed_types )
			);
		}

		// Security: Check for double extension (e.g., image.php.jpg, malicious.jpg.php)
		$filename_parts = explode( '.', strtolower( $this->file['name'] ) );
		if ( count( $filename_parts ) > 2 ) {
			// Check if any part before the last is a dangerous extension
			$dangerous_extensions = array( 'php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'js', 'html', 'htm', 'asp', 'aspx', 'jsp' );
			$filename_without_last_ext = implode( '.', array_slice( $filename_parts, 0, -1 ) );
			foreach ( $dangerous_extensions as $danger_ext ) {
				if ( substr( $filename_without_last_ext, -strlen( $danger_ext ) - 1 ) === '.' . $danger_ext ) {
					return new \WP_Error(
						'double_extension',
						esc_html__( 'File contains dangerous double extension. Security check failed.', 'wp-ulike-pro' )
					);
				}
			}
		}

		// Security: disallow dangerous extensions
		$disallowed_extensions = array( 'php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'js', 'html', 'htm', 'asp', 'aspx', 'jsp' );
		if ( in_array( strtolower( $file_type['ext'] ), $disallowed_extensions, true ) ) {
			return new \WP_Error(
				'disallowed_file_type',
				esc_html__( 'File type is not allowed for security reasons.', 'wp-ulike-pro' )
			);
		}

		// Security: Validate actual file content (MIME type) - CRITICAL for security
		// Don't trust client-provided MIME type, check actual file content
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$real_mime_type = finfo_file( $finfo, $this->file['tmp_name'] );
			finfo_close( $finfo );
		} elseif ( function_exists( 'mime_content_type' ) ) {
			$real_mime_type = mime_content_type( $this->file['tmp_name'] );
		} else {
			// Fallback: use WordPress function
			$real_mime_type = wp_check_filetype( $this->file['tmp_name'] );
			$real_mime_type = isset( $real_mime_type['type'] ) ? $real_mime_type['type'] : '';
		}

		// Define allowed MIME types for images
		$allowed_mime_types = array(
			'image/jpeg',
			'image/jpg',
			'image/png',
			'image/gif',
			'image/bmp',
			'image/webp',
			'image/x-png', // Alternative PNG MIME type
			'image/pjpeg', // Alternative JPEG MIME type
		);

		// Validate MIME type matches expected image type
		if ( ! in_array( strtolower( $real_mime_type ), $allowed_mime_types, true ) ) {
			return new \WP_Error(
				'invalid_mime_type',
				esc_html__( 'File MIME type does not match image format. Security check failed.', 'wp-ulike-pro' )
			);
		}

		// Security: Verify file is actually an image (using getimagesize)
		// This prevents malicious files with image extensions from being uploaded
		$image_info = @getimagesize( $this->file['tmp_name'] );
		if ( false === $image_info || empty( $image_info[2] ) ) {
			return new \WP_Error(
				'invalid_image',
				esc_html__( 'File is not a valid image. Security check failed.', 'wp-ulike-pro' )
			);
		}

		// Verify image type matches extension
		$image_type_mapping = array(
			IMAGETYPE_JPEG => array( 'jpg', 'jpeg' ),
			IMAGETYPE_PNG  => array( 'png' ),
			IMAGETYPE_GIF  => array( 'gif' ),
			IMAGETYPE_BMP  => array( 'bmp' ),
			IMAGETYPE_WEBP => array( 'webp' ),
		);

		$detected_type = $image_info[2];
		if ( ! isset( $image_type_mapping[ $detected_type ] ) ) {
			return new \WP_Error(
				'unsupported_image_type',
				esc_html__( 'Image type is not supported.', 'wp-ulike-pro' )
			);
		}

		if ( ! in_array( strtolower( $file_type['ext'] ), $image_type_mapping[ $detected_type ], true ) ) {
			return new \WP_Error(
				'mime_extension_mismatch',
				esc_html__( 'File extension does not match image type. Security check failed.', 'wp-ulike-pro' )
			);
		}

		return true;
	}

	/**
	 * Prepare upload directory
	 *
	 * @return true|WP_Error
	 */
	private function prepare_upload_directory() {
		if ( empty( $this->config['upload_dir'] ) ) {
			return true; // Use WordPress default
		}

		$upload_dir = trailingslashit( wp_normalize_path( $this->config['upload_dir'] ) );

		if ( ! is_dir( $upload_dir ) ) {
			$created = wp_mkdir_p( $upload_dir );
			if ( ! $created ) {
				return new \WP_Error(
					'cannot_create_dir',
					esc_html__( 'Unable to create upload directory.', 'wp-ulike-pro' )
				);
			}
		}

		if ( ! is_writable( $upload_dir ) ) {
			return new \WP_Error(
				'not_writable',
				esc_html__( 'Upload directory is not writable.', 'wp-ulike-pro' )
			);
		}

		return true;
	}

	/**
	 * Get upload overrides
	 *
	 * @return array
	 */
	private function get_upload_overrides() {
		$overrides = array(
			'test_form' => false, // Skip form validation
		);

		// Set unique filename callback
		$uploader = $this; // Store reference for closure
		$overrides['unique_filename_callback'] = function( $dir, $name, $ext ) use ( $uploader ) {
			// Remove extension from name to prevent double extension
			$name_without_ext = pathinfo( $name, PATHINFO_FILENAME );

			// Generate filename
			if ( ! empty( $uploader->config['filename'] ) ) {
				// Use custom filename (e.g., when replacing existing file)
				$filename = sanitize_file_name( $uploader->config['filename'] );
			} else {
				// Generate filename following best practices: {user_id}-{hash}.{ext}
				// Format used by popular services (Facebook, Twitter, Instagram style)
				// This provides:
				// - Security: hash prevents guessing other users' avatars
				// - Organization: user ID prefix makes it easy to find user's avatar
				// - Uniqueness: hash ensures no collisions
				// - Privacy: hash prevents reverse lookup of original filename

				$user_id = ! empty( $uploader->config['user_id'] ) ? (int) $uploader->config['user_id'] : 0;

				// Generate secure random hash (16 characters for good entropy)
				$hash = $uploader->generate_random_filename( 16 );

				if ( $user_id > 0 ) {
					// Format: {user_id}-{hash}
					// Example: 123-abc123def456ghij.jpg
					$filename = $user_id . '-' . $hash;
				} else {
					// Fallback: just hash if no user ID (shouldn't happen for avatars)
					$filename = $hash;
				}
			}

			// Ensure we don't have double extension
			if ( substr( strtolower( $filename ), -strlen( $ext ) ) === strtolower( $ext ) ) {
				$filename = substr( $filename, 0, -strlen( $ext ) );
			}

			// If replace is false, ensure unique filename (shouldn't happen with hash, but safe)
			if ( ! $uploader->config['replace'] ) {
				$full_path = trailingslashit( $dir ) . $filename . $ext;
				if ( file_exists( $full_path ) ) {
					// Very unlikely with hash, but handle it
					$unique_filename = wp_unique_filename( $dir, $filename . $ext );
					// Remove extension from unique filename to add it back
					$filename = pathinfo( $unique_filename, PATHINFO_FILENAME );
				}
			}

			return $filename . $ext;
		};

		return $overrides;
	}

	/**
	 * Generate random filename
	 * Uses cryptographically secure random generation for better security
	 *
	 * @param int $length Length of random string
	 * @return string
	 */
	private function generate_random_filename( $length = 12 ) {
		// Use WordPress secure random generation if available (WordPress 5.6+)
		if ( function_exists( 'wp_generate_password' ) ) {
			// Generate more characters than needed to ensure we have enough after filtering
			$password = wp_generate_password( $length * 2, false );
			// Remove special characters, keep only alphanumeric
			$filename = preg_replace( '/[^a-zA-Z0-9]/', '', $password );
			// Trim to desired length
			$filename = substr( $filename, 0, $length );

			// If somehow we got less characters, pad with more secure random
			if ( strlen( $filename ) < $length ) {
				$additional = wp_generate_password( $length - strlen( $filename ), false );
				$filename .= preg_replace( '/[^a-zA-Z0-9]/', '', $additional );
				$filename = substr( $filename, 0, $length );
			}

			return $filename;
		}

		// Fallback: Use random_bytes for cryptographically secure random (PHP 7+)
		if ( function_exists( 'random_bytes' ) ) {
			try {
				$bytes = random_bytes( (int) ceil( $length / 2 ) );
				$filename = bin2hex( $bytes );
				// Convert to alphanumeric only
				$filename = preg_replace( '/[^a-zA-Z0-9]/', '', $filename );
				return substr( $filename, 0, $length );
			} catch ( \Exception $e ) {
				// Fall through to wp_rand() if random_bytes fails
			}
		}

		// Last resort: wp_rand() (less secure but better than nothing)
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$filename = '';
		$max = strlen( $characters ) - 1;

		for ( $i = 0; $i < $length; $i++ ) {
			$filename .= $characters[ wp_rand( 0, $max ) ];
		}

		return $filename;
	}

	/**
	 * Filter upload directory to use custom path
	 *
	 * @param array $upload_dir WordPress upload directory array
	 * @return array Modified upload directory array
	 */
	public function filter_upload_dir( $upload_dir ) {
		// Prevent recursion
		if ( $this->filter_applied ) {
			return $upload_dir;
		}

		if ( empty( $this->config['upload_dir'] ) ) {
			return $upload_dir;
		}

		$custom_dir = trailingslashit( wp_normalize_path( $this->config['upload_dir'] ) );
		$uploads = $this->uploads_dir;

		if ( empty( $uploads ) ) {
			// Fallback (shouldn't happen)
			$uploads = wp_get_upload_dir();
			$this->uploads_dir = $uploads;
		}

		$uploads_basedir = untrailingslashit( wp_normalize_path( $uploads['basedir'] ) );
		$custom_dir_clean = untrailingslashit( $custom_dir );

		// Calculate relative path from WordPress uploads
		if ( strpos( $custom_dir_clean, $uploads_basedir ) === 0 ) {
			// Custom dir is within WordPress uploads directory
			$relative_path = str_replace( $uploads_basedir, '', $custom_dir_clean );
			$relative_path = trim( $relative_path, '/' );

			$upload_dir['path']   = $custom_dir;
			$upload_dir['url']    = trailingslashit( $uploads['baseurl'] ) . $relative_path;
			$upload_dir['subdir'] = '/' . $relative_path;
		} else {
			// Custom dir is outside WordPress uploads (absolute path)
			$abspath = untrailingslashit( wp_normalize_path( ABSPATH ) );
			if ( strpos( $custom_dir_clean, $abspath ) === 0 ) {
				$relative_path = str_replace( $abspath, '', $custom_dir_clean );
				$relative_path = trim( $relative_path, '/' );
				$upload_dir['url'] = trailingslashit( site_url() ) . $relative_path;
			} else {
				// Fallback
				$slug = defined( 'WP_ULIKE_SLUG' ) ? WP_ULIKE_SLUG : 'wp-ulike';
				$upload_dir['url'] = trailingslashit( $uploads['baseurl'] ) . $slug . '/avatars';
			}

			$upload_dir['path']   = $custom_dir;
			$upload_dir['subdir'] = '/' . basename( $custom_dir_clean );
		}

		// Keep basedir and baseurl unchanged to prevent recursion
		$upload_dir['basedir'] = $uploads['basedir'];
		$upload_dir['baseurl'] = $uploads['baseurl'];

		$this->filter_applied = true;
		return $upload_dir;
	}

	/**
	 * Process image (resize, optimize)
	 *
	 * @param string $file_path Full path to image file
	 * @return true|WP_Error
	 */
	private function process_image( $file_path ) {
		if ( ! $this->is_image( $file_path ) ) {
			return true; // Not an image, skip processing
		}

		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$editor = wp_get_image_editor( $file_path );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		$max_width  = ! empty( $this->config['max_width'] ) ? (int) $this->config['max_width'] : null;
		$max_height = ! empty( $this->config['max_height'] ) ? (int) $this->config['max_height'] : null;
		$quality    = ! empty( $this->config['quality'] ) ? (int) $this->config['quality'] : 90;

		if ( empty( $max_width ) && empty( $max_height ) ) {
			// No resizing needed, just set quality
			$editor->set_quality( $quality );
			$saved = $editor->save( $file_path );
			return is_wp_error( $saved ) ? $saved : true;
		}

		// Get original dimensions
		$original_size = $editor->get_size();
		$new_width  = $max_width;
		$new_height = $max_height;

		// Calculate new dimensions if resizing needed
		if ( $max_width && $max_height ) {
			// Resize to fit within max dimensions while maintaining aspect ratio
			$ratio = min( $max_width / $original_size['width'], $max_height / $original_size['height'] );
			if ( $ratio < 1 ) {
				$new_width  = (int) round( $original_size['width'] * $ratio );
				$new_height = (int) round( $original_size['height'] * $ratio );
			} else {
				// Image is smaller than max dimensions, keep original
				$new_width  = $original_size['width'];
				$new_height = $original_size['height'];
			}
		} elseif ( $max_width && ! $max_height ) {
			// Only width specified
			if ( $original_size['width'] > $max_width ) {
				$new_width  = $max_width;
				$new_height = (int) round( $original_size['height'] * ( $max_width / $original_size['width'] ) );
			}
		} elseif ( ! $max_width && $max_height ) {
			// Only height specified
			if ( $original_size['height'] > $max_height ) {
				$new_height = $max_height;
				$new_width  = (int) round( $original_size['width'] * ( $max_height / $original_size['height'] ) );
			}
		}

		// Resize if needed
		if ( ( $new_width && $new_width !== $original_size['width'] ) ||
			 ( $new_height && $new_height !== $original_size['height'] ) ) {
			$resize_result = $editor->resize( $new_width, $new_height, false );
			if ( is_wp_error( $resize_result ) ) {
				return $resize_result;
			}
		}

		// Set quality and save
		$editor->set_quality( $quality );
		$saved = $editor->save( $file_path );

		return is_wp_error( $saved ) ? $saved : true;
	}

	/**
	 * Check if file is an image
	 *
	 * @param string $file_path File path
	 * @return bool
	 */
	private function is_image( $file_path ) {
		$file_type = wp_check_filetype( $file_path );
		$image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp' );
		return in_array( strtolower( $file_type['ext'] ), $image_extensions, true );
	}

	/**
	 * Format file size to human readable format
	 *
	 * @param int $bytes File size in bytes
	 * @return string Formatted file size
	 */
	private function format_file_size( $bytes ) {
		if ( $bytes >= 1073741824 ) {
			return number_format( $bytes / 1073741824, 2 ) . ' GB';
		} elseif ( $bytes >= 1048576 ) {
			return number_format( $bytes / 1048576, 2 ) . ' MB';
		} elseif ( $bytes >= 1024 ) {
			return number_format( $bytes / 1024, 2 ) . ' KB';
		} else {
			return $bytes . ' bytes';
		}
	}
}

