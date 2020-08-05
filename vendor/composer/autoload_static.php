<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitdb3e0a72762256e87974fb9fbf2821c5
{
    public static $prefixLengthsPsr4 = array (
        'Q' => 
        array (
            'Qcloud\\Sms\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Qcloud\\Sms\\' => 
        array (
            0 => __DIR__ . '/..' . '/qcloudsms/qcloudsms_php/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitdb3e0a72762256e87974fb9fbf2821c5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitdb3e0a72762256e87974fb9fbf2821c5::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
