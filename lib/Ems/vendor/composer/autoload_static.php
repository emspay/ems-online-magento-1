<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd1d9926663e13dd3945f8c1f9862ccb8
{
    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'Ginger\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Ginger\\' => 
        array (
            0 => __DIR__ . '/..' . '/gingerpayments/ginger-php/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd1d9926663e13dd3945f8c1f9862ccb8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd1d9926663e13dd3945f8c1f9862ccb8::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
