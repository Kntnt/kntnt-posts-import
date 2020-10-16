<?php


namespace Kntnt\Posts_Import;


class Tool {

    private $errors = [];

    public function run() {
        add_management_page( 'Kntnt Posts import', 'Posts import', 'manage_options', 'kntnt-posts-import', [ $this, 'tool' ] );
    }

    public function tool() {

        Plugin::log();

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized use.', 'kntnt-posts-import' ) );
        }

        if ( $_POST ) {

            if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], Plugin::ns() ) ) {
                return;
            }

            // The need for stripslashes() despite that Magic Quotes were
            // deprecated already in PHP 5.4 is due to WordPress backward
            // compatibility. WordPress roll their own version of "magic
            // quotes" because too much core and plugin code have come to
            // rely on the quotes being there. Jeez…
            $import = stripslashes_deep( trim( $_POST['import'] ) );

            if ( $import &&
                 ( $import = json_decode( $import ) ) !== null &&
                 ! ( $property_diff = Plugin::property_diff( [ 'attachments', 'users', 'post_terms', 'posts' ], $import ) ) ) {
                /*
                                Attachment::import( $import->attachments );
                                $this->errors = array_merge( $this->errors, Attachment::errors() );

                                User::import( $import->users );
                                $this->errors = array_merge( $this->errors, User::errors() );
                */
                Term::import( $import->post_terms );
                $this->errors = array_merge( $this->errors, Term::errors() );
                /*
                                Post::import( $import->posts );
                                $this->errors = array_merge( $this->errors, Post::errors() );
                */
            }
            else {
                if ( $import === '' ) {
                    $message = __( 'No data to import.', 'kntnt-posts-import' );
                    $this->errors[] = $message;
                    Plugin::error( $message );
                }
                else if ( $import === null ) {
                    $message = sprintf( __( 'JSON error message: %s', 'kntnt-posts-import' ), json_last_error_msg() );
                    $this->errors[] = $message;
                    Plugin::error( $message );
                }
                else if ( $property_diff ) {
                    $message = sprintf( _n( '%s is missing property.', '%s are missing properties.', count( $property_diff ), $domain = 'kntnt-posts-import' ), join( ', ', $property_diff ) );
                    $this->errors[] = $message;
                    Plugin::error( $message );
                    Plugin::log( array_keys( (array) $import ) );
                }
                else {
                    $message = 'Bad programmer!'; // Will never happens ;-)
                    $this->errors[] = $message;
                    Plugin::error( $message );
                }
            }

            Post::save_all();

        }

        $this->render_page();

    }

    public
    function render_page() {

        Plugin::log();

        Plugin::load_from_includes( 'tool.php', [
            'ns' => Plugin::ns(),
            'title' => __( 'Kntnt Posts import', 'kntnt-posts-import' ),
            'submit_button_text' => __( 'Import', 'kntnt-posts-import' ),
            'errors' => $this->errors,
        ] );

    }

}