<?php


namespace Kntnt\Posts_Import;


class Local_Loader {

    public function run() {
        foreach ( glob( Plugin::plugin_dir( 'local/*.php' ) ) as $file ) {
            include $file;
        }
    }

}