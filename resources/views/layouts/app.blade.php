<!doctype html>
<html @php(language_attributes())>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(do_action('get_header'))
        @php(wp_head())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('utils.styles')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Elms+Sans&family=Merriweather+Sans:ital,wght@0,300..800;1,300..800&family=Murecho:wght@100..900&display=swap" rel="stylesheet">

    </head>

    <body @php(body_class())>
        @php(wp_body_open())

        <div id="app" class="">
            @include('sections.header')

            <main id="main" class="{{$containerClasses}}">
                <div class="{{ $containerInnerClasses }}">
                    @yield('content')
                </div>
            </main>

            @include('sections.reviews')
            @include('sections.footer')
        </div>

        @php(do_action('get_footer'))
        @php(wp_footer())
        @include('utils.scripts')
    </body>
</html>
