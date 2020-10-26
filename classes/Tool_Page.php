<?php


namespace Kntnt\Posts_Import;


final class Tool_Page {

    static $messages = [];

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

        Plugin::debug();

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized use.', 'kntnt-posts-import' ) );
        }

        @ini_set( 'upload_max_size', '0' );
        @ini_set( 'post_max_size', '0' );
        @ini_set( 'max_execution_time', '0' );

        if ( $_POST ) {
            if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], Plugin::ns() ) ) {
                if ( $_FILES['import_file']['error'] ) {
                    $this->log( 'ERROR', self::$file_upload_errors[ $_FILES['import_file']['error'] ] );
                }
                else if ( 'application/json' != $_FILES['import_file']['type'] ) {
                    $this->log( 'ERROR', __( 'You must upload a JSON-file.', 'kntnt-posts-import' ) );
                }
                else {
                    Plugin::debug( 'Uploaded "%s".', $_FILES['import_file']['name'] );
                    if ( $import = file_get_contents( $_FILES['import_file']['tmp_name'] ) ) {
                        $response = Importer::start( $import );
                        if ( is_wp_error( $response ) ) {
                            $this->log( 'ERROR', __( 'Failed to start background import: %s', 'kntnt-posts-import' ), $response->get_error_messages() );
                        }
                        else {
                            $this->log( 'INFO', __( 'Successfully started background import. Check log file.', 'kntnt-posts-import' ) );
                        }
                    }
                    else {
                        $this->log( 'ERROR', __( 'Failed to read the uploaded file.', 'kntnt-posts-import' ) );
                    }
                }
            }
            else {
                $this->log( 'ERROR', __( "The form has expired. Please, try again.", 'kntnt-posts-import' ) );
            }
        }

        $this->render_page();

    }

    public function render_page() {

        Plugin::debug();

        Plugin::load_from_includes( 'tool.php', [
            'ns' => Plugin::ns(),
            'title' => get_admin_page_title(),
            'submit_button_text' => __( 'Import', 'kntnt-posts-import' ),
            'messages' => self::$messages,
        ] );

    }

    private function log( $context, $message, ...$args ) {
        $message = sprintf( $message, ...array_map( [ Plugin::class, 'stringify' ], $args ) );
        self::$messages[] = [
            'context' => $context,
            'message' => $message,
        ];
        Plugin::log( $context, $message );
    }

}