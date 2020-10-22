<?php


namespace Kntnt\Posts_Import;


trait Logger {

    // If `$message` isn't a string, its value is printed. If `$message` is
    // a string, it is written with each occurrence of '%s' replaced with
    // the value of the corresponding additional argument converted to string.
    // Any percent sign that should be written must be escaped with another
    // percent sign, that is `%%`. This method do nothing if debug flag isn't
    // set.
    public static function log( $message = '', ...$args ) {
        if ( self::is_debugging() ) {
            self::_log( $message, ...$args );
        }
    }

    // If `$message` isn't a string, its value is printed. If `$message` is
    // a string, it is written with each occurrence of '%s' replaced with
    // the value of the corresponding additional argument converted to string.
    // Any percent sign that should be written must be escaped with another
    // percent sign, that is `%%`. This method works independent of
    // the debug flag.
    public static function error( $message = '', ...$args ) {
        self::_log( $message, ...$args );
    }

    public static final function stringify( $val ) {
        if ( is_null( $val ) ) {
            $out = 'NULL';
        }
        else if ( is_bool( $val ) ) {
            $out = $val ? 'TRUE' : 'FALSE';
        }
        else if ( is_array( $val ) || is_object( $val ) ) {
            $out = print_r( $val, true );
        }
        else {
            $out = (string) $val;
        }
        return $out;
    }

    protected static function _log( $message = '', ...$args ) {
        if ( ! is_string( $message ) ) {
            $args = [ $message ];
            $message = '%s';
        }
        $caller = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );
        $caller = $caller[2]['class'] . '->' . $caller[2]['function'] . '()';
        foreach ( $args as &$arg ) {
            $arg = self::stringify( $arg );
        }
        $message = sprintf( $message, ...$args );
        error_log( "$caller: $message" );
    }

}