<x-modal title="{{ $heading }}">
  <x-slot:button>
    {{ $buttonText }}
  </x-slot>

  {!! $blockContent !!}
</x-modal>
