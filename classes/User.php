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
        $this->metadata = (array) $user->metadata; // Associative arrays becomes objets in JSON.
    }

    protected function _save() {

        $ok = true;

        $ok = apply_filters( 'kntnt-post-import-save-user-dependencies', $ok, $this );

        if ( $ok && get_current_user_id() != $this->id ) {

            if ( $this->id_exists() ) {
                Plugin::log( 'Deleting a pre-existing user with id = %s.', $this->id );
                $ok = wp_delete_user( $this->id );
                if ( ! $ok ) {
                    self::error( 'Failed to delete the pre-existing user with id = %s.', $this->id );
                }
            }

            if ( $ok ) {
                Plugin::log( 'Create an empty user with id = %s.', $this->id );
                $ok = $this->create_id();
                if ( ! $ok ) {
                    self::error( 'Failed to create user with id = %s.', $this->id );
                }
            }

        }

        if ( $ok ) {

            $user = [
                'ID' => $this->id,
                'user_login' => $this->login,
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
                'metadata' => $this->metadata,
            ];

            Plugin::log( 'Update user with id = %s: %s', $this->id, $user );
            $response = wp_insert_user( $user );
            if ( is_wp_error( $response ) ) {
                $ok = false;
                self::error( 'Failed to insert user with id = %s: %s', $this->id, $response->get_error_message() );
            }

        }

        if ( $ok ) {
            Plugin::log( "Saving metadata for user with id = %s", $this->id );
            foreach ( $this->metadata as $key => $value ) {
                $ok &= add_metadata( 'user', $this->id, $key, $value );
                if ( ! $ok ) {
                    self::error( 'Failed to save all metadata for user with id = %s.', $this->id );
                    break;
                }
            }
        }

        return $ok;

    }

    private function id_exists() {
        global $wpdb;
        return (bool) $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE ID = %d", $this->id ) );
    }

    private function create_id() {
        global $wpdb;
        return (bool) $wpdb->insert( $wpdb->users, [ 'ID' => $this->id ] );
    }

}