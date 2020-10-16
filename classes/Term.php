<?php


namespace Kntnt\Posts_Import;


class Term extends Abstract_Importer {

    public $id;

    public $slug;

    public $name;

    public $parent;

    public $taxonomy;

    public $description;

    public $metadata;

    protected function __construct( $term ) {
        $this->id = $term->id;
        $this->slug = $term->slug;
        $this->name = $term->name;
        $this->parent = $term->parent;
        $this->taxonomy = $term->taxonomy;
        $this->description = $term->description;
        $this->metadata = $term->metadata;
    }

    protected function _save() {

        $ok = true;

        // TODO: Add dependencies. Delete if it exists. Add this. Return true iff ok.

        return $ok;

    }

}