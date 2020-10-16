<?php


namespace Kntnt\Posts_Import;


class Plugin extends Abstract_Plugin {

    use Logger;
    use Includes;

    private static $public_properties = [];

    public static function public_properties_of( $object_or_class_name ) {

        if ( ! is_object( $object_or_class_name ) && isset( self::$public_properties[ $object_or_class_name ] ) ) {
            return self::$public_properties[ $object_or_class_name ];
        }

        if ( is_object( $object_or_class_name ) ) {
            $properties = array_keys( (array) $object_or_class_name );
        }
        else {
            $properties = ( new \ReflectionClass( $object_or_class_name ) )->getProperties( \ReflectionProperty::IS_PUBLIC );
            $properties = array_map( function ( $property ) { return $property->getName(); }, $properties );
            self::$public_properties[ $object_or_class_name ] = $properties;
        }

        return $properties;

    }

    public static function has_properties( $object, $class ) {
        $public_properties = self::public_properties_of( $class );
        foreach ( $public_properties as $property ) {
            if ( ! property_exists( $object, $property ) ) {
                return false;
            }
        }
        return true;
    }

    public static function property_diff( $properties_or_class, $object_or_array_of_properties ) {
        if ( is_string( $properties_or_class ) ) {
            $properties_or_class = self::public_properties_of( $properties_or_class );
        }
        return array_diff( $properties_or_class, self::public_properties_of( $object_or_array_of_properties ) );
    }

    public function classes_to_load() {
        return [
            'admin' => [
                'admin_menu' => [
                    'Tool',
                ],
            ],
        ];
    }

}
