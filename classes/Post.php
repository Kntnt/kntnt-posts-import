<?php


namespace Kntnt\Posts_Import;


class Post extends Abstract_Importer {

    public $id;

    public $slug;

    public $guid;

    public $title;

    public $content;

    public $excerpt;

    public $author;

    public $date;

    public $terms;

    public $attachments;

    public $metadata;

    public static function save_all() {
        krsort( self::$all );
        foreach ( self::$all as $post ) {
            $ok = $post->save();
        }
    }

    protected function __construct( $post ) {
        $this->id = $post->id;
        $this->slug = $post->slug;
        $this->guid = $post->guid;
        $this->title = $post->title;
        $this->content = $post->content;
        $this->excerpt = $post->excerpt;
        $this->author = $post->author;
        $this->date = $post->date;
        $this->terms = $post->terms;
        $this->attachments = $post->attachments;
        $this->metadata = $post->metadata;
    }

    protected function _save() {

        $ok = true;

        // Save dependencies
        $ok &= $this->save_author();
        $ok &= $this->save_terms();
        $ok &= $this->save_attachments();

        // TODO: Delete if it exists. Add this. Return true iff ok.

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
            self::error( 'No author with id = %s.', $this->author );
            $ok = false;
        }
        return $ok;
    }

    private function save_terms() {
        $ok = true;
        foreach ( $this->terms as $term_id ) {
            $term = Term::get( $term_id );
            if ( $term ) {
                if ( ! $term->save() ) {
                    self::error( 'Error while saving term with id = %s. See above.', $term_id );
                    $ok = false;
                }
            }
            else {
                self::error( 'No term with id = %s.', $term_id );
                $ok = false;
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
                    self::error( 'Error while saving attachment with id = %s. See above.', $attachment_id );
                    $ok = false;
                }
            }
            else {
                self::error( 'No attachment with id = %s.', $attachment_id );
                $ok = false;
            }
        }
        return $ok;
    }

}