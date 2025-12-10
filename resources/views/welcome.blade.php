@extends('layouts.app')

@section('content')
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/@highlightjs/cdn-assets@11.7.0/styles/github.min.css">
        <style>
            main p code {
                padding: 0.075rem 0.25rem;
                background: var(--wp--preset--color--indigo-500);
                color: white;
            }
            main td code {
                padding: 0.075rem 0.25rem;
                background: var(--wp--preset--color--gray-200);
                color: black;
            }
        </style>
    @endpush

    <x-heading level="h1" size="3xl" class="mb-6 flex gap-6 items-center">
        <x-icon-radicle class="w-24 h-24" />
        <b>Welcome to Radicle</b>
    </x-heading>

    <p class="my-6"><x-button href="https://github.com/roots/radicle/tree/main/docs">Radicle Docs</x-button></p>
    <p class="my-6">This route is used for demonstration purposes. It is registered from <code>routes/web.php</code> and the template is located at <code>resources/views/welcome.blade.php</code>.</p>

    <x-heading level="h2" size="2xl" class="my-6">Components</x-heading>
    <p class="my-6">Components live in the <code>resources/views/components</code> directory.</p>

    <x-heading level="h3" size="xl" class="my-4" id="typography">Typography</x-heading>
    <p class="my-4">Typography components for consistent text styling across the site.</p>

    <div class="my-6 bg-gray-100 p-6">
        <x-heading level="h4" size="lg" class="mb-4">Headings</x-heading>

        <pre class="mb-6"><code class="language-blade">&lt;x-heading level="h1"&gt;Page Title&lt;/x-heading&gt;
&lt;x-heading level="h2"&gt;Section Title&lt;/x-heading&gt;
&lt;x-heading level="h3"&gt;Subsection&lt;/x-heading&gt;</code></pre>

        <div class="space-y-2">
            <x-heading level="h1">Heading 1 (4xl)</x-heading>
            <x-heading level="h2">Heading 2 (3xl)</x-heading>
            <x-heading level="h3">Heading 3 (2xl)</x-heading>
            <x-heading level="h4">Heading 4 (xl)</x-heading>
            <x-heading level="h5">Heading 5 (lg)</x-heading>
            <x-heading level="h6">Heading 6 (base)</x-heading>
        </div>

        <x-heading level="h4" size="lg" class="mb-4 mt-8">Links</x-heading>

        <pre class="mb-6"><code class="language-blade">&lt;x-link href="/page"&gt;Default link&lt;/x-link&gt;
&lt;x-link href="/page" variant="unstyled"&gt;Unstyled link&lt;/x-link&gt;
&lt;x-link href="https://example.com" external&gt;External link&lt;/x-link&gt;</code></pre>

        <div class="space-y-2">
            <p><x-link href="#" variant="default">Default link</x-link></p>
            <p><x-link href="#" variant="unstyled">Unstyled link</x-link></p>
            <p><x-link href="#" external>External link</x-link></p>
        </div>

        <x-heading level="h4" size="lg" class="mb-4 mt-8">Lists</x-heading>

        <pre class="mb-6"><code class="language-blade">&lt;x-list type="ul" spacing="normal"&gt;
    &lt;x-list-item&gt;First item&lt;/x-list-item&gt;
    &lt;x-list-item&gt;Second item&lt;/x-list-item&gt;
&lt;/x-list&gt;

&lt;x-list type="ol" spacing="tight"&gt;
    &lt;x-list-item&gt;Step one&lt;/x-list-item&gt;
    &lt;x-list-item&gt;Step two&lt;/x-list-item&gt;
&lt;/x-list&gt;</code></pre>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="font-medium mb-2">Unordered List:</p>
                <x-list type="ul" spacing="normal">
                    <x-list-item>First item</x-list-item>
                    <x-list-item>Second item</x-list-item>
                    <x-list-item>Third item</x-list-item>
                </x-list>
            </div>
            <div>
                <p class="font-medium mb-2">Ordered List:</p>
                <x-list type="ol" spacing="normal">
                    <x-list-item>First item</x-list-item>
                    <x-list-item>Second item</x-list-item>
                    <x-list-item>Third item</x-list-item>
                </x-list>
            </div>
        </div>
    </div>

    <x-heading level="h3" size="xl" class="my-4" id="buttons">Buttons</x-heading>
    <p class="my-4">The <code>core/button</code> block is rendered with the <code>x-button</code> Blade component.</p>

    <x-table
        :columns="['Attribute', 'Default', 'Options']"
        :rows="[
            ['<code>variant</code>', '<code>primary</code>', '<code>primary</code>, <code>outline</code>'],
            ['<code>size</code>', '<code>base</code>', '<code>xs</code>, <code>sm</code>, <code>base</code>, <code>lg</code>'],
            ['<code>element</code>', '<code>a</code>', '<code>a</code>, <code>button</code>'],
        ]"
    />

    <div class="my-6 bg-gray-100 p-6">
        <pre class="mb-6"><code class="language-blade">&lt;x-button href="#"&gt;Button&lt;/x-button&gt;</code></pre>
        <x-button href="#">Button</x-button>

        <pre class="my-6"><code class="language-blade">&lt;x-button variant="outline" href="#"&gt;Outline Button&lt;/x-button&gt;</code></pre>
        <x-button variant="outline" href="#">Outline Button</x-button>

        <pre class="my-6"><code class="language-blade">&lt;x-button variant="inverse" href="#"&gt;Inverse Button&lt;/x-button&gt;</code></pre>
        <div class="bg-black p-4 inline-block">
            <x-button variant="inverse" href="#">Inverse Button</x-button>
        </div>
    </div>

    <x-heading level="h3" size="xl" class="my-6" id="modals">Modals</x-heading>
    <p class="my-4">The <code>radicle/modal</code> block is rendered with the <code>x-modal</code> Blade component.</p>

    <div class="my-6 bg-gray-100 p-6 relative">
        <pre class="mb-6"><code class="language-blade">&lt;x-modal title="Modal Title"&gt;
    &lt;x-slot name="button"&gt;Open modal&lt;/x-slot&gt;
    Example modal
&lt;/x-modal&gt;</code></pre>

        <x-modal title="Modal Title">
            <x-slot name="button">Open modal</x-slot>
            Example modal
        </x-modal>
    </div>

    @push('scripts')
        <script defer src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
        <script defer src="https://unpkg.com/highlightjs-blade/dist/blade.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', (event) => {
                document.querySelectorAll('pre code').forEach((el) => {
                    hljs.highlightElement(el);
                });
            });
        </script>
    @endpush
@endsection
