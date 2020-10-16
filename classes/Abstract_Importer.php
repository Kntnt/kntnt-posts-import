<?php


namespace Kntnt\Posts_Import;


abstract class Abstract_Importer {

    protected static $all = [];

    private static $errors = [];

    private $unsaved = true;

    public static function import( $objects ) {
        foreach ( $objects as $object ) {
            if ( ! Plugin::has_properties( $object, static::class ) ) {
                $diff = Plugin::property_diff( static::class, $object );
                if ( ! isset( $object->id ) ) {
                    $message = sprintf( _n( '%s is missing for an object %s.', '%s are missing for an object %s', count( $diff ), $domain = 'kntnt-posts-import' ), join( ', ', $diff ), static::class );
                }
                else {
                    $message = sprintf( _n( '%s is missing property for the object %s with id = %s.', '%s are missing properties for the object %s with id = %s.', count( $diff ), $domain = 'kntnt-posts-import' ), join( ', ', $diff ), static::class, $object->id );
                }
                self::$errors[] = $message;
                Plugin::error( $message );
                Plugin::error( $object );
                continue;
            }
            self::$all[ $object->id ] = new static( $object );
            Plugin::log( 'Created %s from %s', static::class, $object );
        }
    }

    public static function get( $id ) {
        return isset( self::$all[ $id ] ) ? self::$all[ $id ] : null;
    }

    public static function errors() {
        return self::$errors;
    }

    public function save() {
        if ( $this->unsaved ) {
            $this->_save();
        }
    }

    protected abstract function _save();

}