<?php
/**
 * Comment
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'BuddyKitFileValidator' ) ) {

    class BuddyKitFileValidator {

        var $file = '';

        var $maxsize = 1000000; //1MB default

        var $allowed_extension;

        var $error_messages = array();

        public function __construct($file) {
            $this->file = $file;
            $this->error_messages = array(
                    'invalid_parameters' => esc_attr('Invalid parameters', 'buddykit'),
                    'no_file_sent' => esc_attr('No file snet', 'buddykit'),
                    'exceeded_filesize_limit' => esc_attr('Exceeded size limit for media type', 'buddykit'),
                    'unknown_error' => esc_attr('Exceeded filesize limit', 'buddykit'),
                    'invalid_file_format' => esc_attr('Invalid file format', 'buddykit'),
                );
            return $this;
        }

        public function set_maxsize($maxsize) {
            $this->maxsize = $maxsize * 1000000;
            return $this;
        }

        public function set_allowed_extension( $extensions_array ) {
            $this->allowed_extension = $extensions_array;
            return $this;
        }

        public function validate() {
        
            try {
                
                // Undefined | Multiple Files | $_FILES Corruption Attack
                // If this request falls under any of them, treat it invalid.
                if (
                    !isset($this->file['error']) ||
                    is_array($this->file['error'])
                ) {
                    throw new RuntimeException($this->error_messages['invalid_parameters']);
                }

                // Check $_FILES['upfile']['error'] value.
                switch ( $this->file['error']) {
                    case UPLOAD_ERR_OK:
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        throw new RuntimeException($this->error_messages['no_file_sent']);
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        throw new RuntimeException();
                    default:
                        throw new RuntimeException($this->error_messages['unknown_error']);
                }

                // You should also check filesize here. 1MB 
                if ( $this->file['size'] > $this->maxsize ) {
                    throw new RuntimeException($this->error_messages['exceeded_filesize_limit']);
                }

                // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
                // Check MIME Type by yourself.
                $finfo = new finfo(FILEINFO_MIME_TYPE);

                if (false === $ext = array_search(
                    $finfo->file( $this->file['tmp_name'] ),
                    $this->allowed_extension,
                    true
                )) {
                    throw new RuntimeException($this->error_messages['invalid_file_format']);
                }

                return true;

            } catch (RuntimeException $e) {

                return $e->getMessage();

            }
        }
    }
}