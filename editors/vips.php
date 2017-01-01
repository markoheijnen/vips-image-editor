<?php
/**
 * WordPress Vips Image Editor
 *
 * @package WordPress
 * @subpackage Image_Editor
 */

/**
 * WordPress Image Editor Class for Image Manipulation through Vips PHP Module
 *
 * @since 1.0.0
 * @package WordPress
 * @subpackage Image_Editor
 * @uses WP_Image_Editor Extends class
 */
class WP_Image_Editor_Vips extends WP_Image_Editor {

	protected $image = null; // Vips Object

	public function __destruct() {
		if ( $this->image ) {
			// we don't need the original in memory anymore
			$this->image = null;
		}
	}

	/**
	 * Checks to see if current environment supports vips.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return boolean
	 */
	public static function test( $args = array() ) {
		// Test Vips extension and class.
		if ( ! extension_loaded('vips') ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks to see if editor supports the mime-type specified.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $mime_type
	 * @return boolean
	 */
	public static function supports_mime_type( $mime_type ) {
		$extension = strtoupper( self::get_extension( $mime_type ) );

		$supported = array('JPG', 'PNG', 'GIF', 'TIFF', 'WEBP', 'SVG');

		return in_array( $extension, $supported);
	}

	/**
	 * Loads image from $this->file into new VipsImage Object.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return boolean|WP_Error True if loaded; WP_Error on failure.
	 */
	public function load() {
		if ( $this->image ) {
			return true;
		}

		if ( ! is_file( $this->file ) && ! preg_match( '|^https?://|', $this->file ) ) {
			return new WP_Error( 'error_loading_image', __('File doesn&#8217;t exist?'), $this->file );
		}

		$this->image = vips_image_new_from_file( $this->file )['out'];

		if ( ! is_resource( $this->image ) ) {
			return new WP_Error( 'invalid_image', __('File is not an image.'), $this->file );
		}

		// TODO: Select the first frame to handle animated images properly

		$this->mime_type = mime_content_type( $this->file );

		$updated_size = $this->update_size();
		if ( is_wp_error( $updated_size ) ) {
			return $updated_size;
		}

		return true;
	}

	/**
	 * Sets Image Compression quality on a 1-100% scale.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param int $quality Compression Quality. Range: [1,100]
	 * @return boolean|WP_Error
	 */
	public function set_quality( $quality = null ) {
		$quality_result = parent::set_quality( $quality );

		if ( is_wp_error( $quality_result ) ) {
			return $quality_result;
		}
		else {
			$quality = $this->get_quality();
		}

		// TODO: Set quality

		return true;
	}

	/**
	 * Sets or updates current image size.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int $width
	 * @param int $height
	 */
	protected function update_size( $width = null, $height = null ) {
		$size = null;

		if ( ! $width || ! $height ) {
			try {
				$size = array(
					'width'  => vips_image_get( $this->image, 'width' )['out'],
					'height' => vips_image_get( $this->image, 'height' )['out']
				);
			}
			catch ( Exception $e ) {
				return new WP_Error( 'invalid_image', __('Could not read image size'), $this->file );
			}
		}

		if ( ! $width ) {
			$width = $size['width'];
		}

		if ( ! $height ) {
			$height = $size['height'];
		}

		return parent::update_size( $width, $height );
	}

	/**
	 * Resizes current image.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param int $max_w
	 * @param int $max_h
	 * @param boolean $crop
	 * @return boolean|WP_Error
	 */
	public function resize( $max_w, $max_h, $crop = false ) {
		if ( ( $this->size['width'] == $max_w ) && ( $this->size['height'] == $max_h ) ) {
			return true;
		}

		$dims = image_resize_dimensions( $this->size['width'], $this->size['height'], $max_w, $max_h, $crop );
		if ( ! $dims ) {
			return new WP_Error( 'error_getting_dimensions', __('Could not calculate resized image dimensions') );
		}

		list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;

		if ( $crop ) {
			return $this->crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h );
		}

		// TODO: Do resize
		return new WP_Error( 'image_resize_error', '' );

		return $this->update_size( $dst_w, $dst_h );
	}

	/**
	 * Processes current image and saves to disk
	 * multiple sizes from single source.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $sizes { {'width'=>int, 'height'=>int, 'crop'=>bool}, ... }
	 * @return array
	 */
	public function multi_resize( $sizes ) {
		$metadata = array();
		$orig_size = $this->size;

		// TODO: Do multi resize
		foreach ( $sizes as $size => $size_data ) {
			
		}

		return $metadata;
	}

	/**
	 * Crops Image.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|int $src The source file or Attachment ID.
	 * @param int $src_x The start x position to crop from.
	 * @param int $src_y The start y position to crop from.
	 * @param int $src_w The width to crop.
	 * @param int $src_h The height to crop.
	 * @param int $dst_w Optional. The destination width.
	 * @param int $dst_h Optional. The destination height.
	 * @param boolean $src_abs Optional. If the source crop points are absolute.
	 * @return boolean|WP_Error
	 */
	public function crop( $src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false ) {
		if ( $src_abs ) {
			$src_w -= $src_x;
			$src_h -= $src_y;
		}

		// TODO: Check call
		vips_call( 'crop', $this->image, $src_w, $src_h, $src_x, $src_y )['out'];

		return $this->update_size();
	}

	/**
	 * Rotates current image counter-clockwise by $angle.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param float $angle
	 * @return boolean|WP_Error
	 */
	public function rotate( $angle ) {
		// TODO: Do rotate
		return new WP_Error( 'image_rotate_error', '' );

		return $this->update_size();
	}

	/**
	 * Flips current image.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param boolean $horz Horizontal Flip
	 * @param boolean $vert Vertical Flip
	 * @returns boolean|WP_Error
	 */
	public function flip( $horz, $vert ) {
		// TODO: Do flip
		return new WP_Error( 'image_flip_error', '' );

		return true;
	}

	/**
	 * Saves current image to file.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $destfilename
	 * @param string $mime_type
	 * @return array|WP_Error {'path'=>string, 'file'=>string, 'width'=>int, 'height'=>int, 'mime-type'=>string}
	 */
	public function save( $destfilename = null, $mime_type = null ) {
		$saved = $this->_save( $this->image, $destfilename, $mime_type );

		if ( ! is_wp_error( $saved ) ) {
			$this->file      = $saved['path'];
			$this->mime_type = $saved['mime-type'];

			// TODO: Set image format
			return new WP_Error( 'image_save_error', '', $this->file );
		}

		return $saved;
	}

	protected function _save( $image, $filename = null, $mime_type = null ) {
		list( $filename, $extension, $mime_type ) = $this->get_output_format( $filename, $mime_type );

		if ( ! $filename )
			$filename = $this->generate_filename( null, null, $extension );

		// TODO: Store image
		return new WP_Error( 'image_save_error', '', $filename );

		// Set correct file permissions
		$stat  = stat( dirname( $filename ) );
		$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
		@ chmod( $filename, $perms );

		/** This filter is documented in wp-includes/class-wp-image-editor-gd.php */
		return array(
			'path'      => $filename,
			'file'      => wp_basename( apply_filters( 'image_make_intermediate_size', $filename ) ),
			'width'     => $this->size['width'],
			'height'    => $this->size['height'],
			'mime-type' => $mime_type,
		);
	}

	/**
	 * Streams current image to browser.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $mime_type
	 * @return boolean|WP_Error
	 */
	public function stream( $mime_type = null ) {
		list( $filename, $extension, $mime_type ) = $this->get_output_format( null, $mime_type );

		// TODO: Do streaming
		return new WP_Error( 'image_stream_error', '' );

		return true;
	}
}