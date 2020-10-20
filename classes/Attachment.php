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
        $this->metadata = (array) $attachment->metadata; // Associative arrays becomes objets in JSON.
        $this->src = $attachment->src;
    }

    protected function _save() {

        $ok = true;

        // Save dependencies
        $ok &= $this->save_author();
        $ok = apply_filters( 'kntnt-post-import-save-attachment-dependencies', $ok, $this );

        // Delete pre-existing attachment
        if ( $ok && $this->id_exists() ) {
            Plugin::log( 'An older attachment or post exists with id = %s.', $this->id );
            $deleted_post = wp_delete_post( $this->id, true );
            if ( $deleted_post ) {
                Plugin::log( 'Successfully deleted the older attachment or post with id = %s.', $this->id );
            }
            else {
                self::error( 'Failed to delete the older attachment or post with id = %s.', $this->id );
                $ok = false;
            }
        }

        $file = Plugin::peel_off( '_wp_attached_file', $this->metadata, false );
        if ( $ok && $file ) {
            $dst = Plugin::upload_dir( $file[0] );
            if ( $src = fopen( $this->src, 'r' ) ) {
                if ( file_put_contents( $dst, $src ) ) {
                    Plugin::log( 'Successfully downloaded %s and saved it to %s.', $this->src, $dst );
                }
                else {
                    self::error( 'Failed to save %s to %s.', $this->src, $dst );
                    $ok = false;
                }
            }
            else {
                self::error( 'Failed to download %s to %s.', $this->src, $dst );
                $ok = false;
            }
        }

        // Insert attachment
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
            $response = wp_insert_post( $attachment, true );
            if ( is_wp_error( $response ) ) {
                self::error( 'Failed to insert $attachment with id = %s: %s', $this->id, $response->get_error_messages() );
                $ok = false;
            }
            assert( $response == $this->id );
        }

        return $ok;

    }

    private function save_author() {
        $user = User::get( $this->author );
        if ( $user ) {
            $ok = $user->save();
            if ( ! $ok ) {
                self::error( 'Error while saving author with id = %s. See above.', $user->id );
            }
        }
        else {
            self::error( 'No user with id = %s.', $this->author );
            $ok = false;
        }
        return $ok;
    }

    private function id_exists() {
        global $wpdb;
        return (bool) $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d", $this->id ) );
    }

}