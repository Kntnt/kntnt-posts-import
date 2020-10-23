<?php


namespace Kntnt\Posts_Import;


final class Post extends Abstract_Importer {

    public $id;

    public $slug;

    public $guid;

    public $title;

    public $content;

    public $excerpt;

    public $author;

    public $date;

    public $status;

    public $terms;

    public $attachments;

    public $metadata;

    public static function save_all() {
        krsort( self::$all );
        foreach ( self::$all as $post ) {
            $ok = $post->save();
        }
    }

    protected static $all = [];

    protected function __construct( $post ) {
        $this->id = $post->id;
        $this->slug = $post->slug;
        $this->guid = $post->guid;
        $this->title = $post->title;
        $this->content = $post->content;
        $this->excerpt = $post->excerpt;
        $this->author = $post->author;
        $this->date = $post->date;
        $this->status = $post->status;
        $this->terms = Plugin::objects_to_arrays( $post->terms );
        $this->attachments = $post->attachments;
        $this->metadata = Plugin::objects_to_arrays( $post->metadata );
    }

    protected function _save() {

        $ok = true;

        // Save dependencies
        $ok &= $this->save_author();
        $ok &= $this->save_terms();
        $ok &= $this->save_attachments();
        $ok = apply_filters( 'kntnt-post-import-save-post-dependencies', $ok, $this );

        // Delete pre-existing post
        if ( $ok && $this->id_exists() ) {
            Plugin::log( 'An older post exists with id = %s.', $this->id );
            $deleted_post = wp_delete_post( $this->id, true );
            if ( $deleted_post ) {
                Plugin::log( 'Successfully deleted the older post with id = %s.', $this->id );
            }
            else {
                Plugin::error( 'Failed to delete the older post with id = %s.', $this->id );
                $ok = false;
            }
        }

        // Insert post
        if ( $ok ) {

            $post = [
                'post_type' => 'post',
                'import_id' => $this->id,
                'post_name' => $this->slug,
                'post_title' => $this->title,
                'post_content' => $this->content,
                'post_excerpt' => $this->excerpt,
                'post_author' => $this->author,
                'post_date' => $this->date,
                'post_status' => $this->status,
                'post_category' => Plugin::peel_off( 'category', $this->terms, [] ),
                'tags_input' => Plugin::peel_off( 'post_tag', $this->terms, [] ),
                'tax_input' => $this->terms,
                '_thumbnail_id' => Plugin::peel_off( '_thumbnail_id', $this->metadata, [ 0 => '' ] )[0],
            ];

            Plugin::log( 'Create post with id = %s: %s', $this->id, $post );
            $response = wp_insert_post( $post, true );
            if ( is_wp_error( $response ) ) {
                Plugin::error( 'Failed to insert post with id = %s: %s', $this->id, $response->get_error_messages() );
                $ok = false;
            }
            assert( $response == $this->id );

        }

        if ( $ok ) {
            do_action( 'kntnt-posts-import-post-saved', $this->id, $post );
        }

        if ( $ok ) {
            Plugin::log( "Saving metadata for post with id = %s", $this->id );
            foreach ( $this->metadata as $field => $values ) {
                foreach ( $values as $value ) {
                    if ( update_post_meta( $this->id, $field, $value ) ) {
                        do_action( 'kntnt-posts-import-post-metadata', $field, $value, $this->id );
                    }
                    else {
                        Plugin::error( 'Failed to update post with id = %s with metadata: %s => %s', $this->id, $field, $value );
                        $ok = false;
                    }
                }
            }
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

    private function save_terms() {
        $ok = true;
        foreach ( $this->terms as $taxonomy ) {
            foreach ( $taxonomy as $term_id ) {
                $term = Term::get( $term_id );
                if ( $term ) {
                    if ( ! $term->save() ) {
                        Plugin::error( 'Error while saving term with id = %s. See above.', $term_id );
                        $ok = false;
                    }
                }
                else {
                    Plugin::error( 'No term with id = %s.', $term_id );
                    $ok = false;
                }
            }
        }
        return $ok;
    }

    private function save_attachments() {
        $ok = true;
        foreach ( $this->attachments as $attachment_id ) {
            $attachment = Attachment::get( $attachment_id );
            if ( $attachment ) {
                if ( ! $attachment->save() ) {
                    Plugin::error( 'Error while saving attachment with id = %s. See above.', $attachment_id );
                    $ok = false;
                }
            }
            else {
                Plugin::error( 'No attachment with id = %s.', $attachment_id );
                $ok = false;
            }
        }
        return $ok;
    }

    private function id_exists() {
        global $wpdb;
        return (bool) $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d", $this->id ) );
    }

}