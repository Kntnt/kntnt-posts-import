<?php


namespace Kntnt\Posts_Import;


final class User extends Abstract_Importer {

    public $id;

    public $login;

    public $pass;

    public $nicename;

    public $email;

    public $url;

    public $registered;

    public $display_name;

    public $role;

    public $first_name;

    public $last_name;

    public $nickname;

    public $description;

    public $rich_editing;

    public $syntax_highlighting;

    public $comment_shortcuts;

    public $admin_color;

    public $show_admin_bar_front;

    public $locale;

    public $metadata;

    protected static $all = [];

    protected function __construct( $user ) {
        $this->id = $user->id;
        $this->login = $user->login;
        $this->pass = $user->pass;
        $this->nicename = $user->nicename;
        $this->email = $user->email;
        $this->url = $user->url;
        $this->registered = $user->registered;
        $this->display_name = $user->display_name;
        $this->role = $user->role;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->nickname = $user->nickname;
        $this->description = $user->description;
        $this->rich_editing = $user->rich_editing;
        $this->syntax_highlighting = $user->syntax_highlighting;
        $this->comment_shortcuts = $user->comment_shortcuts;
        $this->admin_color = $user->admin_color;
        $this->show_admin_bar_front = $user->show_admin_bar_front;
        $this->locale = $user->locale;
        $this->metadata = Plugin::objects_to_arrays( $user->metadata );
    }

    protected function _save() {

        $ok = true;

        // Allow developers save additional dependencies.
        $ok = apply_filters( 'kntnt-post-import-save-user-dependencies', $ok, $this );
        if ( ! $ok ) {
            Plugin::error( 'A `kntnt-post-import-save-user-dependencies` filter has failed.' );
        }

        if ( $ok ) {

            if ( $this->id_exists() ) {
                Plugin::info( 'Deleting a pre-existing user with id = %s.', $this->id );
                $ok = wp_delete_user( $this->id );
                if ( ! $ok ) {
                    Plugin::error( 'Failed to delete the pre-existing user with id = %s.', $this->id );
                }
            }

            if ( $ok ) {
                Plugin::info( 'Create an empty user with id = %s.', $this->id );
                $ok = $this->create_id();
                if ( ! $ok ) {
                    Plugin::error( 'Failed to create user with id = %s.', $this->id );
                }
            }

        }

        if ( $ok ) {

            $user = [
                'ID' => $this->id,
                'user_login' => $this->login, // Is required despite it is already set by create_id().
                'user_pass' => $this->pass,
                'user_nicename' => $this->nicename,
                'user_email' => $this->email,
                'user_url' => $this->url,
                'user_registered' => $this->registered,
                'display_name' => $this->display_name,
                'role' => $this->role,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'nickname' => $this->nickname,
                'description' => $this->description,
                'rich_editing' => $this->rich_editing,
                'syntax_highlighting' => $this->syntax_highlighting,
                'comment_shortcuts' => $this->comment_shortcuts,
                'admin_color' => $this->admin_color,
                'show_admin_bar_front' => $this->show_admin_bar_front,
                'locale' => $this->locale,
            ];

            Plugin::info( 'Update user with id = %s', $this->id );
            $response = wp_insert_user( $user );
            if ( is_wp_error( $response ) ) {
                $ok = false;
                Plugin::error( 'Failed to insert user with id = %s: %s', $this->id, $response->get_error_message() );
            }

        }

        if ( $ok ) {
            do_action( 'kntnt-posts-import-user-saved', $this->id, $user );
        }

        if ( $ok ) {
            Plugin::info( "Saving metadata for user with id = %s", $this->id );
            foreach ( $this->metadata as $field => $values ) {
                foreach ( $values as $value ) {
                    if ( add_metadata( 'user', $this->id, $field, $value ) ) {
                        do_action( 'kntnt-posts-import-user-metadata', $field, $value, $this->id );
                    }
                    else {
                        Plugin::error( 'Failed to update user with id = %s with metadata: %s => %s', $this->id, $field, $value );
                        $ok = false;
                    }
                }
            }
        }

        return $ok;

    }

    /** @noinspection SqlResolve */
    private function id_exists() {
        global $wpdb;
        return (bool) $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE ID = %d", $this->id ) );
    }

    private function create_id() {
        global $wpdb;
        // The user property user_login isn't updated by wp_insert_user(),
        // and must therefore be inserted in the database with the ID.
        return (bool) $wpdb->insert( $wpdb->users, [ 'ID' => $this->id, 'user_login' => $this->login ] );
    }

}