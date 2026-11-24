<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported languages
    |--------------------------------------------------------------------------
    |
    | Every locale offered by the panel language switcher. `dir` drives the
    | document direction so Arabic automatically renders right-to-left.
    |
    */

    'locales' => [
        'id' => ['label' => 'Indonesia', 'native' => 'Bahasa Indonesia', 'dir' => 'ltr'],
        'en' => ['label' => 'English', 'native' => 'English', 'dir' => 'ltr'],
        'ar' => ['label' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl'],
    ],

    'default_locale' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Skins
    |--------------------------------------------------------------------------
    |
    | Colour themes applied at runtime through the `data-skin` attribute. The
    | `color` value is only used to paint the swatch in the picker, the actual
    | palette lives in resources/css/app.css.
    |
    */

    'skins' => [
        'indigo' => ['label' => 'Indigo', 'color' => '#465fff'],
        'white' => ['label' => 'White', 'color' => '#334155'],
        'hijau' => ['label' => 'Hijau', 'color' => '#0f9d58'],
        'baby-blue' => ['label' => 'Baby Blue', 'color' => '#38bdf8'],
        'teal' => ['label' => 'Teal', 'color' => '#14b8a6'],
        'maroon' => ['label' => 'Merah Maroon', 'color' => '#9f1239'],
    ],

    'default_skin' => 'indigo',

    /*
    |--------------------------------------------------------------------------
    | Colour mode
    |--------------------------------------------------------------------------
    |
    | One of light, dark or system. `system` follows the operating system
    | preference and updates live when it changes.
    |
    */

    'modes' => ['light', 'dark', 'system'],

    'default_mode' => 'system',

];
