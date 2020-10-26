<?php


namespace Kntnt\Posts_Import;


trait Logger {

    public static function info( $message = '', ...$args ) {
        return self::_log( 'INFO', $message, ...$args );
    }

    public static function error( $message = '', ...$args ) {
        return self::_log( 'ERROR', $message, ...$args );
    }

    public static function debug( $message = '', ...$args ) {
        if ( self::is_debugging() ) {
            return self::_log( 'DEBUG', $message, ...$args );
        }
    }

    public static function log( $context, $message = '', ...$args ) {
        if ( in_array( strtoupper( $context ), [ 'INFO', 'ERROR' ] ) || self::is_debugging() ) {
            return self::_log( $context, $message, ...$args );
        }
    }

    // If `$message` isn't a string, its value is printed. If `$message` is
    // a string, it is written with each occurrence of '%s' replaced with
    // the value of the corresponding additional argument converted to string.
    // Any percent sign that should be written must be escaped with another
    // percent sign, that is `%%`. The message is prefixed with [$context]
    // followed by […] where … is the qualified name of the function calling.
    protected static function _log( $context, $message, ...$args ) {
        if ( ! is_string( $message ) ) {
            $args = [ $message ];
            $message = '%s';
        }
        $caller = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );
        $caller = $caller[2]['class'] . '->' . $caller[2]['function'] . '()';
        $message = sprintf( $message, ...array_map( [ Plugin::class, 'stringify' ], $args ) );
        error_log( "[$context][$caller] $message" );
        return [
            'context' => $context,
            'caller' => $caller,
            'message' => $message,
        ];
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

}
