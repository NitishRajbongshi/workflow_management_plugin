<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit74065dd5603b99aecaeabeb415d0a56a
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit74065dd5603b99aecaeabeb415d0a56a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit74065dd5603b99aecaeabeb415d0a56a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit74065dd5603b99aecaeabeb415d0a56a::$classMap;

        }, null, ClassLoader::class);
    }
}
