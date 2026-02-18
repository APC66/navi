<section class="pt-16 md:pt-20 pb-32 from-primary-900 to-primary-1000 bg-gradient-to-b">
    <div class="container mx-auto px-4">

        <x-partials.section-header
            highlight="Que disent"
            suffix="nos clients"
            highlight-color="text-white"
            tag="h2"
            size="M"
            align="text-center"
        />


        {!! do_shortcode('[brb_collection id=334]') !!}
        <div class="flex justify-center flex-wrap gap-4 mt-8 md:gap-8">
            <div class="flex justify-center md:justify-end items-center gap-4">
                <x-partials.button
                    title="Voir tous nos avis"
                    url="https://search.google.com/local/reviews?placeid=ChIJowQyHRCAsBIRKT2kP09WyyE"
                    target="_blank"
                    variant="secondary"
                />
            </div>
            <div class="flex justify-center md:justify-start items-center gap-4">
                <x-partials.button
                    title="Laisser un avis"
                    url="https://search.google.com/local/writereview?placeid=ChIJowQyHRCAsBIRKT2kP09WyyE"
                    target="_blank"
                    variant="outline"
                />
            </div>
        </div>
    </div>
</section>
