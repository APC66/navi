<?php

namespace App\Blocks;

use App\Fields\Partials\SectionHeaderFields;
use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class Contact extends Block
{
    public $name = 'Contact';

    public $description = 'Mise en page 50/50 avec informations de contact et formulaire.';

    public $category = 'widgets';

    public $icon = 'email-alt';

    public function with()
    {
        return [
            'title_group' => get_field('title_group'),
            'content' => get_field('content'),
            'contact_info' => get_field('contact_info'),
            'form_title' => get_field('form_title'),
            'form_code' => get_field('form_code'),
        ];
    }

    public function fields()
    {
        $contact = new FieldsBuilder('contact');

        $contact
            ->addTab('gauche', ['label' => 'Texte & Infos (Gauche)'])
            ->addGroup('title_group', ['label' => 'Titre de la section'])
            ->addFields(app(SectionHeaderFields::class)->fields())
            ->endGroup()

            ->addWysiwyg('content', [
                'label' => 'Texte d\'introduction',
                'media_upload' => 0,
                'toolbar' => 'full',
                'rows' => 4,
            ])

            ->addWysiwyg('contact_info', [
                'label' => 'Embarquement',
                'media_upload' => 0,
                'toolbar' => 'full',
                'rows' => 4,
            ])
            ->addImage('map', ['label' => 'Carte'])

            ->addTab('droite', ['label' => 'Formulaire (Droite)'])
            ->addText('form_title', [
                'label' => 'Titre au dessus du formulaire',
                'default_value' => 'Envoyez-nous un message',
            ])
            ->addText('form_code', [
                'label' => 'Shortcode du formulaire',
                'instructions' => 'Collez ici le shortcode généré par votre plugin (Contact Form 7, WPForms, etc.). Ex: [contact-form-7 id="123"]',
            ]);

        return $contact->build();
    }

    /**
     * Assets to be enqueued when rendering the block.
     * Cette méthode charge le CSS spécifiquement quand le bloc est présent sur la page.
     */
    public function enqueue() {}
}
