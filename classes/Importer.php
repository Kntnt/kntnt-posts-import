<?php


namespace Kntnt\Posts_Import;


class Importer {

    public static function start( $import ) {

        $url = admin_url( 'admin-ajax.php' );
        $url = add_query_arg( [
            'action' => Plugin::ns(),
            '_wpnonce' => wp_create_nonce( Plugin::ns() ),
        ], $url );

        $args = [
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
            'blocking' => false,
            'timeout' => 0.1,
            'cookies' => $_COOKIE,
            'body' => [ 'import' => $import ],
        ];

        return wp_remote_post( $url, $args );

    }

    public function run() {
        add_action( 'wp_ajax_' . Plugin::ns(), [ $this, 'handle' ] );
    }

    public function handle() {

        // See https://ma.ttias.be/php-session-locking-prevent-sessions-blocking-in-requests/
        session_write_close();

        if ( ! isset( $_REQUEST['_wpnonce'] ) || false === wp_verify_nonce( $_REQUEST['_wpnonce'], Plugin::ns() ) ) {
            Plugin::error( 'Failed to verify nonce.' );
            wp_die( - 1, 403 );
        }

        // Load local code.
        do_action( 'kntnt-posts-import-add-local-code' );

        // The need for stripslashes() despite that Magic Quotes were
        // deprecated already in PHP 5.4 is due to WordPress backward
        // compatibility. WordPress roll their own version of "magic
        // quotes" because too much core and plugin code have come to
        // rely on the quotes being there. Jeez…
        $import = trim( stripslashes_deep( $_POST['import'] ) );

        @ini_set( 'max_execution_time', '0' );
        $t = time();
        Plugin::info( 'Begins importing posts.' );
        $this->import( $import );
        Plugin::info( 'Finished importing posts. It took %s seconds.', time() - $t );
        wp_die();

    }

    private function import( $import ) {
        if ( $import &&
             ( $import = json_decode( $import ) ) !== null &&
             ! ( $property_diff = Plugin::property_diff( [ 'attachments', 'users', 'post_terms', 'posts' ], $import ) ) ) {

            Term::import( $import->post_terms );
            User::import( $import->users );
            Attachment::import( $import->attachments );
            Post::import( $import->posts );

            Post::save_all();

        }
        else {
            if ( $import === '' ) {
                Plugin::error( __( 'No data to import.', 'kntnt-posts-import' ) );
            }
            else if ( $import === null ) {
                Plugin::error( __( 'JSON error message: %s', 'kntnt-posts-import' ), json_last_error_msg() );
            }
            else if ( $property_diff ) {
                Plugin::error( sprintf( _n( 'Missing property %s.', '%s are missing properties.', count( $property_diff ), $domain = 'kntnt-posts-import' ), join( ', ', $property_diff ) ) );
            }
            else {
                Plugin::error( 'Bad programmer!' ); // Will never happen ;-)
            }
        }
    }

}
