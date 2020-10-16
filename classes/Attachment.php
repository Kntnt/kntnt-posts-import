<?php


namespace Kntnt\Posts_Import;


class Attachment extends Abstract_Importer {

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
        $this->metadata = $attachment->metadata;
    }

    protected function _save() {

        $ok = true;

        // TODO: Add dependencies. Delete if it exists. Add this. Return true iff ok.

        return $ok;

    }

}