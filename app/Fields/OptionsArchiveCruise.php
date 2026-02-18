<?php

namespace App\Fields;

use App\Fields\Partials\PageHeaderFields;
use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class OptionsArchiveCruise extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $options = new FieldsBuilder('archive_cruise_option', [
            'title' => 'Options de la page d\'archive des croisières',
            'style' => 'seamless',
        ]);

        $options
            ->setLocation('options_page', '==', 'theme-options');

        $options
            ->addGroup('cruise_header', ['label' => 'Configuration de l\'Entête de la page d\'archive des croisières'])
            ->addFields(app(PageHeaderFields::class)->fields())
            ->endGroup();

        return $options->build();
    }
}
