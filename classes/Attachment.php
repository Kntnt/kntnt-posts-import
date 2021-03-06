<?php


namespace Kntnt\Posts_Import;


final class Attachment extends Abstract_Importer {

    public $id;

    public $slug;

    public $guid;

    public $mime_type;

    public $title;

    public $content;

    public $excerpt;

    public $author;

    public $date;

    public $metadata;

    public $src;

    protected static $all = [];

    protected static function dependencies_exists( $attachment ) {

        $ok = true;

        if ( ! User::get( $attachment->author ) ) {
            Plugin::error( 'Author with id = %s is missing.', $attachment->author );
            $ok = false;
        }

        return $ok;

    }

    protected function __construct( $attachment ) {

        // Restore associative arrays that was exported as objects.
        $attachment->metadata = Plugin::objects_to_arrays( $attachment->metadata );

        // Allow developers to modify imported data.
        $attachment = apply_filters( 'kntnt-posts-import-attachment', $attachment );

        // Allow developers to check additional dependencies.
        $ok = true;
        $ok = apply_filters( 'kntnt-posts-import-attachment-dependencies-check', $ok, $attachment );
        if ( ! $ok ) {
            Plugin::error( 'Can\'t import attachment with id = %s since not all dependencies are satisfied.', $attachment->id );
        }

        $this->id = $attachment->id;
        $this->slug = $attachment->slug;
        $this->guid = $attachment->guid;
        $this->mime_type = $attachment->mime_type;
        $this->title = $attachment->title;
        $this->content = $attachment->content;
        $this->excerpt = $attachment->excerpt;
        $this->author = $attachment->author;
        $this->date = $attachment->date;
        $this->metadata = Plugin::objects_to_arrays( $attachment->metadata );
        $this->src = $attachment->src;

    }

    private static function author_exists( $attachment ) {
        $ok = true;
        if ( ! User::get( $attachment->author ) ) {
            Plugin::error( 'Author with id = %s is missing.', $attachment->author );
            $ok = false;
        }
        return $ok;
    }

    protected function _save() {

        $ok = true;

        // Save dependencies.
        $ok &= $this->save_author();

        // Allow developers save additional dependencies.
        $ok = apply_filters( 'kntnt-post-import-save-attachment-dependencies', $ok, $this );

        if ( $ok && $this->id_exists() ) {
            Plugin::debug( 'Deleting a pre-existing attachment with id = %s.', $this->id );
            $ok = (bool) wp_delete_post( $this->id, true );
            if ( ! $ok ) {
                Plugin::error( 'Failed to delete the older attachment or post with id = %s.', $this->id );
            }
        }

        if ( $ok ) {
            $file = Plugin::peel_off( '_wp_attached_file', $this->metadata, false );
            if ( $file && isset( $file[0] ) ) {
                $file = $file[0];
            }
            else {
                $ok = false;
                Plugin::error( 'Attachment with id = %s has no file.', $this->id );
            }
        }

        if ( $ok ) {
            $dst = Plugin::upload_dir( $file );
            if ( wp_mkdir_p( $dir = dirname( $dst ) ) ) {
                if ( $src = @fopen( $this->src, 'r' ) ) {
                    if ( file_put_contents( $dst, $src ) ) {
                        Plugin::debug( 'Successfully downloaded %s and saved it to %s.', $this->src, $dst );
                    }
                    else {
                        Plugin::error( 'Failed to save %s to %s.', $this->src, $dst );
                        $ok = false;
                    }
                }
                else {
                    Plugin::error( 'Failed to download %s to %s.', $this->src, $dst );
                    $ok = false;
                }
            }
            else {
                Plugin::error( 'Failed to create directory %s.', $dir );
                $ok = false;
            }
        }

        $is_image = preg_match( '@^image/@', $this->mime_type ) && file_is_displayable_image( $dst );

        if ( $ok && $is_image ) {
            $image_metadata = Plugin::peel_off( '_wp_attachment_metadata', $this->metadata, [] );
        }

        if ( $ok ) {

            $attachment = [
                'post_type' => 'attachment',
                'import_id' => $this->id,
                'post_name' => $this->slug,
                'post_title' => $this->title,
                'post_content' => $this->content,
                'post_excerpt' => $this->excerpt,
                'post_author' => $this->author,
                'post_date' => $this->date,
                'post_status' => 'inherit',
                'post_mime_type' => $this->mime_type,
                'file' => $file,
            ];

            Plugin::debug( 'Create attachment with id = %s', $this->id );
            $response = wp_insert_post( $attachment, true );
            if ( is_wp_error( $response ) ) {
                Plugin::error( 'Failed to insert $attachment with id = %s: %s', $this->id, $response->get_error_messages() );
                $ok = false;
            }
            assert( $response == $this->id );

        }

        if ( $ok ) {
            do_action( 'kntnt-posts-import-user-saved', $this->id, $attachment );
        }

        if ( $ok ) {
            Plugin::debug( "Saving metadata for attachment with id = %s", $this->id );
            foreach ( $this->metadata as $field => $values ) {
                foreach ( $values as $value ) {
                    if ( add_metadata( 'post', $this->id, $field, $value ) ) {
                        do_action( 'kntnt-posts-import-attachment-metadata', $field, $value, $this );
                    }
                    else {
                        Plugin::error( 'Failed to update attachment with id = %s with metadata: %s => %s', $this->id, $field, $value );
                        $ok = false;
                    }
                }
            }
        }

        if ( $ok && $is_image ) {
            Plugin::debug( 'Generates images for various sizes from "%s".' );
            $subsizes = wp_get_registered_image_subsizes();
            $subsizes = apply_filters( 'intermediate_image_sizes_advanced', $subsizes, $image_metadata, $this->id );
            _wp_make_subsizes( $subsizes, $dst, $image_metadata, $this->id );
        }

        if ( $ok ) {
            Plugin::info( 'Attachment %s was successfully created.', $this->id );
        }
        else {
            Plugin::info( 'Attachment %s couldn\'t be created.', $this->id );
        }

        return $ok;

    }

    private function save_author() {
        $user = User::get( $this->author );
        if ( $user ) {
            $ok = $user->save();
            if ( ! $ok ) {
                Plugin::error( 'Error while saving author with id = %s. See above.', $user->id );
            }
        }
        else {
            Plugin::error( 'No user with id = %s.', $this->author );
            $ok = false;
        }
        return $ok;
    }

    /** @noinspection SqlResolve */
    private function id_exists() {
        global $wpdb;
        return (bool) $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d", $this->id ) );
    }

}