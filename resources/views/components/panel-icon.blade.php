{{--
    Inline icon renderer backed by App\Support\Panel\IconLibrary so the panel
    views stay free of any Filament dependency.
--}}
@props(['name' => null, 'class' => 'h-5 w-5'])

{!! \App\Support\Panel\IconLibrary::render($name, $class) !!}
