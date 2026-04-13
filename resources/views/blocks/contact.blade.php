<section
  class="{{ $block->classes }} to-primary-1000 relative z-10 overflow-x-clip bg-white bg-gradient-to-b from-[#182646]"
>
  <div class="relative z-10 mx-auto">
    <div class="container mx-auto grid grid-cols-1 gap-12 px-4 lg:grid-cols-2 lg:gap-20">
      {{-- 1. COLONNE DE GAUCHE --}}
      <div class="flex h-full w-full flex-col">
        {{-- Partie Haute : Textes --}}
        <div class="pt-8 pb-12 lg:pt-0 lg:pr-8">
          @if ($title_group)
            <div data-aos="fade-up" data-aos-duration="600">
              <x-partials.section-header :group="$title_group" align="text-left" />
            </div>
          @endif

          @if ($content)
            <div
              class="contentText mt-6 mb-10"
              data-aos="fade-up"
              data-aos-duration="600"
              data-aos-delay="150"
            >
              {!! $content !!}
            </div>
          @endif
        </div>

        {{-- Partie Basse : Infos de contact --}}
        <div class="relative flex-grow py-16 lg:pr-8">
          <div
            class="bg-primary-1000 absolute inset-y-0 left-1/2 -z-10 w-[5000px] -translate-x-1/2"
          ></div>

          <div class="relative z-10">
            @if ($contact_info)
              <div
                class="contentText"
                data-aos="fade-up"
                data-aos-duration="600"
                data-aos-delay="100"
              >
                {!! $contact_info !!}
              </div>
              <div data-aos="fade-up" data-aos-duration="600" data-aos-delay="200">
                @php
                  echo do_shortcode($google_map);
                @endphp
              </div>
            @endif
          </div>
        </div>
      </div>

      {{-- 2. COLONNE DE DROITE : Formulaire --}}
      <div
        class="relative h-full w-full pb-16 lg:pt-24"
        data-aos="fade-left"
        data-aos-duration="700"
        data-aos-delay="200"
      >
        <div
          class="bg-primary-400 border-primary-800 relative z-20 rounded-[30px] border p-8 shadow-2xl md:p-12 lg:sticky lg:top-32 lg:-mt-80"
        >
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
