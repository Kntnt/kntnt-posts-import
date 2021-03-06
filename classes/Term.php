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

    public $default;

    protected static $all = [];

    protected static function dependencies_exists( $term ) {
        static $taxonomies = null;
        if ( is_null( $taxonomies ) ) {
            $taxonomies = get_taxonomies();
        }
        return in_array( $term->taxonomy, $taxonomies );
    }

    protected function __construct( $term ) {

        // Restore associative arrays that was exported as objects.
        $term->metadata = Plugin::objects_to_arrays( $term->metadata );

        // Allow developers to modify imported data.
        $term = apply_filters( 'kntnt-posts-import-term', $term );

        // Allow developers to check additional dependencies.
        $ok = true;
        $ok = apply_filters( 'kntnt-posts-import-term-dependencies-check', $ok, $term );
        if ( ! $ok ) {
            Plugin::error( 'Can\'t import term with id = %s since not all dependencies are satisfied.', $term->id );
        }

        $this->id = $term->id;
        $this->slug = $term->slug;
        $this->name = $term->name;
        $this->parent = $term->parent;
        $this->taxonomy = $term->taxonomy;
        $this->description = $term->description;
        $this->metadata = Plugin::objects_to_arrays( $term->metadata );
        $this->default = $term->default;

        // Always save the default term.
        if ( $term->default ) {
            $key = 'category' == $term->taxonomy ? 'default_category' : "default_term_{$term->taxonomy}";
            $old_id = get_option( $key );
            update_option( $key, 9999 ); // Set a non-existing term as default term while saving.
            $ok = $this->save();
            update_option( $key, $ok ? $term->id : $old_id );
        }

    }

    protected function _save() {

        $ok = true;

        // Allow developers save additional dependencies.
        $ok = apply_filters( 'kntnt-post-import-save-term-dependencies', $ok, $this );

        if ( ! taxonomy_exists( $this->taxonomy ) ) {
            Plugin::error( 'Failed to insert term with id = %s since its taxonomy %s doesn\'t exists', $this->id, $this->taxonomy );
            $ok = false;
        }

        if ( $ok ) {

            if ( $this->id_exists() ) {
                Plugin::debug( 'Deleting a pre-existing term with id = %s.', $this->id );
                $response = wp_delete_term( $this->id, $this->taxonomy );
                if ( is_wp_error( $response ) ) {
                    $ok = false;
                    Plugin::error( 'Failed to delete existing term with id = %s: %s', $this->id, $response->get_error_message() );
                }
            }

            if ( $ok ) {
                Plugin::debug( 'Create an empty term with id = %s.', $this->id );
                $ok = $this->create_id();
                if ( ! $ok ) {
                    Plugin::error( 'Failed to create term with id = %s.', $this->id );
                }
            }

        }

        if ( $ok ) {

            $term = [
                'slug' => $this->slug,
                'name' => $this->name,
                'parent' => $this->parent,
                'description' => $this->description,
            ];

            Plugin::debug( 'Update term with id = %s', $this->id );
            $response = wp_update_term( $this->id, $this->taxonomy, $term );
            if ( is_wp_error( $response ) ) {
                $ok = false;
                Plugin::error( 'Failed insert term with id = %s: %s', $this->id, $response->get_error_message() );
            }

        }

        if ( $ok ) {
            do_action( 'kntnt-posts-import-term-saved', $this->id, $term );
        }

        if ( wp_term_is_shared( $this->id ) ) {
            $ok = false;
            Plugin::error( 'Failed to save metadata for term with id = %s: Term meta cannot be added to terms that are shared between taxonomies.', $this->id );
        }

        if ( $ok ) {
            Plugin::debug( "Saving metadata for term with id = %s", $this->id );
            foreach ( $this->metadata as $field => $values ) {
                foreach ( $values as $value ) {
                    if ( add_metadata( 'term', $this->id, $field, $value ) ) {
                        do_action( 'kntnt-posts-import-term-metadata', $field, $value, $this );
                    }
                    else {
                        Plugin::error( 'Failed to update term with id = %s with metadata: %s => %s', $this->id, $field, $value );
                        $ok = false;
                    }
                }
            }
        }

        if ( $ok ) {
            Plugin::info( 'Term %s was successfully created.', $this->id );
        }
        else {
            Plugin::info( 'Term %s couldn\'t be created.', $this->id );
        }

        return $ok;

    }

    /** @noinspection SqlResolve */
    private function id_exists() {
        global $wpdb;
        return (bool) $wpdb->get_row( $wpdb->prepare( "SELECT tt.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy as tt ON tt.term_id = t.term_id WHERE t.term_id = %d AND tt.taxonomy = %s", $this->id, $this->taxonomy ) );
    }

    private function create_id() {
        global $wpdb;
        $ok = (bool) $wpdb->insert( $wpdb->terms, [ 'term_id' => $this->id ] );
        $ok &= (bool) $wpdb->insert( $wpdb->term_taxonomy, [ 'term_id' => $this->id, 'term_taxonomy_id' => $this->id, 'taxonomy' => $this->taxonomy ] );
        return $ok;
    }

}