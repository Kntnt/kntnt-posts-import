<?php


namespace Kntnt\Posts_Import;


final class Term extends Abstract_Importer {

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

        // Save dependencies
        $ok = apply_filters( 'kntnt-post-import-save-term-dependencies', $ok, $this );

        if ( ! taxonomy_exists( $this->taxonomy ) ) {
            Plugin::error( 'Failed to insert term with id = %s since its taxonomy %s doesn\'t exists', $this->id, $this->taxonomy );
            $ok = false;
        }

        if ( $ok && $this->id_exists() ) {
            Plugin::log( 'An older term with id = %s.', $this->id );
            $response = wp_delete_term( $this->id, $this->taxonomy );
            if ( is_wp_error( $response ) ) {
                Plugin::error( 'Failed to delete existing term with id = %s: %s', $this->id, $response->get_error_messages() );
                $ok = false;
            }
            else {
                Plugin::log( 'Successfully deleted the older term with id = %s.', $this->id );
                $this->create_id();
            }
        }

        if ( $ok ) {
            $term = [
                'slug' => $this->term->slug,
                'name' => $this->name,
                'parent' => $this->term->parent,
                'description' => $this->term->description,
            ];
            $response = wp_update_term( $this->id, $this->taxonomy, $term );
            if ( is_wp_error( $response ) ) {
                Plugin::error( 'Failed insert term with id = %s: %s', $this->id, $response->get_error_messages() );
                $ok = false;
            }
        }

        if ( wp_term_is_shared( $this->id ) ) {
            Plugin::error( 'Failed to save metadata for term with id = %s: Term meta cannot be added to terms that are shared between taxonomies.', $this->id );
            $ok = false;
        }

        if ( $ok ) {
            foreach ( $this->metadata as $key => $value ) {
                $ok &= add_metadata( 'term', $this->id, $key, $value );
                if ( ! $ok ) {
                    Plugin::error( 'Failed to save all metadata for term with id = %s.', $this->id );
                    break;
                }
            }
        }

        return $ok;

    }

    private function id_exists() {
        global $wpdb;
        return (bool) $wpdb->get_row( $wpdb->prepare( "SELECT tt.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy as tt ON tt.term_id = t.term_id WHERE t.term_id = %d AND tt.taxonomy = %s", $this->id, $this->taxonomy ) );
    }

    private function create_id() {
        global $wpdb;
        $wpdb->insert( $wpdb->terms, [ 'term_id' => $this->id ] );
    }

}