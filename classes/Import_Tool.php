<?php


namespace Kntnt\Posts_Import;


final class Import_Tool {

    static $file_upload_errors;

    public function __construct() {
        self::$file_upload_errors = [
            UPLOAD_ERR_OK => __( 'There is no error, the file uploaded with success', 'kntnt-posts-import' ),
            UPLOAD_ERR_INI_SIZE => __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini', 'kntnt-posts-import' ),
            UPLOAD_ERR_FORM_SIZE => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'kntnt-posts-import' ),
            UPLOAD_ERR_PARTIAL => __( 'The uploaded file was only partially uploaded', 'kntnt-posts-import' ),
            UPLOAD_ERR_NO_FILE => __( 'No file was uploaded', 'kntnt-posts-import' ),
            UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder', 'kntnt-posts-import' ),
            UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'kntnt-posts-import' ),
            UPLOAD_ERR_EXTENSION => __( 'A PHP extension stopped the file upload.', 'kntnt-posts-import' ),
        ];
    }

    public function run() {
        add_management_page( 'Kntnt Posts Import', 'Posts import', 'manage_options', 'kntnt-posts-import', [ $this, 'tool' ] );
    }

    public function tool() {

        Plugin::log();

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized use.', 'kntnt-posts-import' ) );
        }

        @ini_set( 'upload_max_size', '0' );
        @ini_set( 'post_max_size', '0' );
        @ini_set( 'max_execution_time', '0' );

        if ( $_POST ) {
            if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], Plugin::ns() ) ) {
                if ( $_FILES['import_file']['error'] ) {
                    Plugin::error( self::$file_upload_errors[ $_FILES['import_file']['error'] ] );
                }
                else if ( 'application/json' != $_FILES['import_file']['type'] ) {
                    Plugin::error( __( 'You must upload a JSON-file.', 'kntnt-posts-import' ) );
                }
                else {
                    Plugin::log( 'Uploaded "%s" to "%s".', $_FILES['import_file']['name'], $_FILES['import_file']['tmp_name'] );
                    $this->import( file_get_contents( $_FILES['import_file']['tmp_name'] ) );
                }
            }
            else {
                Plugin::error( __( "Couldn't import; the form has expired. Please, try again.", 'kntnt-posts-import' ) );
            }
        }

        $this->render_page();

    }

    public function render_page() {

        Plugin::log();

        Plugin::load_from_includes( 'tool.php', [
            'ns' => Plugin::ns(),
            'title' => get_admin_page_title(),
            'submit_button_text' => __( 'Import', 'kntnt-posts-import' ),
            'errors' => Plugin::errors(),
        ] );

    }

    private function import( $import ) {

        if ( $import &&
             ( $import = json_decode( $import ) ) !== null &&
             ! ( $property_diff = Plugin::property_diff( [ 'attachments', 'users', 'post_terms', 'posts' ], $import ) ) ) {

            Attachment::import( $import->attachments );
            User::import( $import->users );
            Term::import( $import->post_terms );
            Post::import( $import->posts );

        }
        else {
            if ( $import === '' ) {
                $message = __( 'No data to import.', 'kntnt-posts-import' );
                Plugin::error( $message );
            }
            else if ( $import === null ) {
                $message = sprintf( __( 'JSON error message: %s', 'kntnt-posts-import' ), json_last_error_msg() );
                Plugin::error( $message );
            }
            else if ( $property_diff ) {
                $message = sprintf( _n( 'Missing property %s.', '%s are missing properties.', count( $property_diff ), $domain = 'kntnt-posts-import' ), join( ', ', $property_diff ) );
                Plugin::error( $message );
                Plugin::log( array_keys( (array) $import ) );
            }
            else {
                $message = 'Bad programmer!'; // Will never happen ;-)
                Plugin::error( $message );
            }
        }

        Post::save_all();

    }

}