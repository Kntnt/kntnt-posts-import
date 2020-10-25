<?php


namespace Kntnt\Posts_Import;


abstract class Abstract_Importer {

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
                Plugin::error( $message );
                continue;
            }
            static::$all[ $object->id ] = new static( $object );
            Plugin::info( 'Created %s with id %s', static::class, $object->id );
        }
    }

    public static function get( $id ) {
        return isset( static::$all[ $id ] ) ? static::$all[ $id ] : null;
    }

    public function save() {
        $ok = true;
        if ( $this->unsaved ) {
            $this->unsaved = false;
            $type = strtolower( substr( static::class, strrpos( static::class, '\\' ) + 1 ) );
            Plugin::info( "Saving %s with id = %s", $type, $this->id );
            $ok = $this->_save();
            if ( ! $ok ) {
                Plugin::error( 'Error while saving %s with id = %s. See above.', $type, $this->id );
            }
        }
        return $ok;
    }

    protected abstract function _save();

}