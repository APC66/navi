<?php

namespace App\Options;

use Log1x\AcfComposer\Builder;
use Log1x\AcfComposer\Options as Field;

class Options extends Field
{
    /**
     * The option page menu name.
     *
     * @var string
     */
    public $name = 'Options contact';

    /* *
     * The option page position.
     *
     * @var int
     */
    public $position = 90;

    /**
     * The option page document title.
     *
     * @var string
     */
    public $title = 'Options de contact';

    /**
     * The option page field group.
     */
    public function fields(): array
    {
        $socials = Builder::make('socials');

        $socials
            ->addTab('Réseaux Sociaux')
            ->addRepeater('social_medias')
            ->addSelect('media', [
                'label' => __('Réseau', 'radicle'),
                'choices' => [
                    'facebook' => 'Facebook',
                    'instagram' => 'Instagram',
                    'linkedin' => 'Linkedin',
                    'x' => 'X / Twitter',
                    'youtube' => 'Youtube',
                    'tiktok' => 'TikTok',
                    'pinterest' => 'Pinterest',
                    'google' => 'Google',
                    'tripadvisor' => 'Tripadvisor',
                ],
            ])->addText('other', [
                'label' => __('Classe FontAwesome', 'radicle'),
                'conditional_logic' => [
                    [
                        'field' => 'media',
                        'operator' => '==',
                        'value' => 'other',
                    ],
                ],
            ])->addText('link', [
                'label' => __('Lien', 'radicle'),
            ])
            ->endRepeater()
            ->addText('phone', [
                'label' => __('Téléphone', 'radicle'),
            ])
            ->addText('fax', [
                'label' => __('Fax', 'radicle'),
            ])
            ->addText('email', [
                'label' => __('Email', 'radicle'),
            ])
            ->addText('contact_link', [
                'label' => __('Lien contact', 'radicle'),
                'type' => 'link',
            ])
            ->addGroup('contact_address')
            ->addText('company', [
                'label' => __('Nom', 'radicle'),
            ])
            ->addText('address', [
                'label' => __('Adresse', 'radicle'),
            ])
            ->addText('postal_code', [
                'label' => __('Code Postal', 'radicle'),
            ])
            ->addText('city', [
                'label' => __('Ville', 'radicle'),
            ])
            ->addText('country', [
                'label' => __('Pays', 'radicle'),
            ])
            ->endGroup()

            ->addTab('scripts')
            ->addTextarea('header_scripts', [
                'label' => __('Scripts dans le head', 'radicle'),
                'instructions' => __('Scripts à insérer dans le head de chaque page.', 'radicle'),
            ])
            ->addTextarea('footer_scripts', [
                'label' => __('Scripts avant le body', 'radicle'),
                'instructions' => __('Scripts à insérer avant la balise body de chaque page.', 'radicle'),
            ]);

        return $socials->build();
    }
}
