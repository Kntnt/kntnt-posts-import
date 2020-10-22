<?php


namespace Kntnt\Posts_Import;


final class Import_Tool {

    public function run() {
        add_management_page( 'Kntnt Posts Import', 'Posts import', 'manage_options', 'kntnt-posts-import', [ $this, 'tool' ] );
    }

    public function tool() {
        Plugin::log();
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized use.', 'kntnt-posts-import' ) );
        }
        if ( $_POST ) {
            if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], Plugin::ns() ) ) {
                // The need for stripslashes() despite that Magic Quotes were
                // deprecated already in PHP 5.4 is due to WordPress backward
                // compatibility. WordPress roll their own version of "magic
                // quotes" because too much core and plugin code have come to
                // rely on the quotes being there. Jeez…
                $this->import( trim( stripslashes_deep( $_POST['import'] ) ) );
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