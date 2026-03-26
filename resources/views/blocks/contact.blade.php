<section
  class="{{ $block->classes }} to-primary-1000 relative z-10 overflow-x-clip bg-white bg-gradient-to-b from-[#182646]"
>
  <div class="relative z-10 mx-auto">

    {{-- GRILLE UNIQUE : Sans 'items-start' pour que les deux colonnes aient exactement la même hauteur --}}
    <div class="container mx-auto px-4 grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-20">

      {{-- 1. COLONNE DE GAUCHE (Contient le haut ET le bas) --}}
      <div class="flex h-full w-full flex-col">

        {{-- Partie Haute : Textes --}}
        <div class="pt-8 lg:pt-0 pb-12 lg:pr-8">
          @if ($title_group)
            <x-partials.section-header :group="$title_group" align="text-left" />
          @endif

          @if ($content)
            <div class="contentText mt-6 mb-10">
              {!! $content !!}
            </div>
          @endif
        </div>

        {{-- Partie Basse : Infos de contact avec le fond bg-primary-1000 pleine largeur --}}
        <div class="relative flex-grow py-16 lg:pr-8">
          {{-- L'astuce "Bleed" : Ce div absolu sort du container pour colorer 100% de la largeur de l'écran en bg-primary-1000 --}}
          <div class="bg-primary-1000 absolute inset-y-0 left-1/2 -z-10 w-[5000px] -translate-x-1/2"></div>

          <div class="relative z-10">
            @if ($contact_info)
              <div class="contentText">
                {!! $contact_info !!}
              </div>
              @php
                echo do_shortcode($google_map)
              @endphp
            @endif
          </div>
        </div>
      </div>
      <div class="relative h-full w-full lg:pt-24 pb-16">
        <div class="bg-primary-400 border-primary-800 relative z-20 rounded-[30px] border p-8 shadow-2xl md:p-12 lg:-mt-80 lg:sticky lg:top-32">
          <div class="contact-form-wrapper relative z-10">
            @if ($form_title)
              <h3 class="mb-8 text-center text-lg font-thin text-white md:text-xl">
                {!! $form_title !!}
              </h3>
            @endif

            @if ($form_code)
              {!! do_shortcode($form_code) !!}
            @else
              <div
                class="bg-primary-800/50 border-primary-600 text-primary-200 rounded-xl border border-dashed p-8 text-center"
              >
                Renseignez le shortcode de votre formulaire dans l'éditeur.
              </div>
            @endif
          </div>
        </div>

      </div>

    </div>
  </div>
</section>
