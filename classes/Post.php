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

    public $metadata;

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
        $this->metadata = $post->metadata;
    }

    protected function _save() { }

}