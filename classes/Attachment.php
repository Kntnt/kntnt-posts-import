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

    protected function __construct( $attachment ) {
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

    protected function _save() {

        $ok = true;

        $ok &= $this->save_author();
        $ok = apply_filters( 'kntnt-post-import-save-attachment-dependencies', $ok, $this );

        if ( $ok && $this->id_exists() ) {
            Plugin::log( 'Deleting a pre-existing attachment with id = %s.', $this->id );
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
                        Plugin::log( 'Successfully downloaded %s and saved it to %s.', $this->src, $dst );
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
                'meta_input' => $this->metadata,
            ];

            Plugin::log( 'Create attachment with id = %s: %s', $this->id, $attachment );
            $response = wp_insert_post( $attachment, true );
            if ( is_wp_error( $response ) ) {
                Plugin::error( 'Failed to insert $attachment with id = %s: %s', $this->id, $response->get_error_messages() );
                $ok = false;
            }
            assert( $response == $this->id );

        }

        if ( $ok && $is_image ) {
            Plugin::log( 'Generates images for various sizes from "%s".' );
            $subsizes = wp_get_registered_image_subsizes();
            $subsizes = apply_filters( 'intermediate_image_sizes_advanced', $subsizes, $image_metadata, $this->id );
            _wp_make_subsizes( $subsizes, $dst, $image_metadata, $this->id );
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

    private function id_exists() {
        global $wpdb;
        return (bool) $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d", $this->id ) );
    }

}