<section class="from-primary-900 to-primary-1000 bg-gradient-to-b pt-16 pb-32 md:pt-20">
  <div class="container mx-auto px-4">
    <div
      @if(!is_singular('cruise'))
        data-aos="fade-up"
        data-aos-duration="600"
      @endif
    >
      <x-partials.section-header
        highlight="Que disent"
        suffix="nos clients"
        highlight-color="text-white"
        tag="h2"
        size="M"
        align="text-center"
      />
    </div>

    <div
      @if(!is_singular('cruise'))
        data-aos="fade-up"
        data-aos-duration="700"
        data-aos-delay="150"
      @endif
    >
      {!! do_shortcode('[brb_collection id=4213]') !!}
    </div>

    <div
      class="mt-8 flex flex-wrap justify-center gap-4 md:gap-8"
      @if(!is_singular('cruise'))
        data-aos="fade-up"
        data-aos-duration="600"
        data-aos-delay="250"
      @endif
    >
      <div class="flex items-center justify-center gap-4 md:justify-end">
        <x-partials.button
          title="Voir tous nos avis"
          url="https://search.google.com/local/reviews?placeid=ChIJmZqbmqpXsBIROqfQvrjwUVU"
          target="_blank"
          variant="secondary"
        />
      </div>
      <div class="flex items-center justify-center gap-4 md:justify-start">
        <x-partials.button
          title="Laisser un avis"
          url="https://search.google.com/local/writereview?placeid=ChIJmZqbmqpXsBIROqfQvrjwUVU"
          target="_blank"
          variant="outline"
        />
      </div>
    </div>
  </div>
</section>
