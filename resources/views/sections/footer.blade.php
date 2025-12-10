<footer class="{{ $containerClasses }} mt-12">
    <div class="{{ $containerInnerClasses }}">
        @php(dynamic_sidebar('sidebar-footer'))

        <p class="my-6">
            <x-link href="https://roots.io/radicle/" weight="bold" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2">
                <x-icon-radicle class="w-4 h-4" />
                Built with Radicle
            </x-link>
        </p>
    </div>
</footer>
