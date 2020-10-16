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

    public $status;

    public $display_name;

    public $roles;

    public $metadata;

    protected function __construct( $user ) {
        $this->id = $user->id;
        $this->login = $user->login;
        $this->pass = $user->pass;
        $this->nicename = $user->nicename;
        $this->email = $user->email;
        $this->url = $user->url;
        $this->registered = $user->registered;
        $this->status = $user->status;
        $this->display_name = $user->display_name;
        $this->roles = $user->roles;
        $this->metadata = $user->metadata;
    }

    protected function _save() {

        $ok = true;

        // TODO: Add dependencies. Delete if it exists. Add this. Return true iff ok.

        return $ok;

    }

}