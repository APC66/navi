<?php

namespace App\Fields;

use App\Fields\Partials\PageHeaderFields;
use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class PageSettings extends Field
{
    public function fields()
    {
        $page = new FieldsBuilder('page_settings', [
            'position' => 'acf_after_title',
            'style' => 'seamless',
        ]);

        $page
            ->setLocation('post_type', '==', 'page')
            ->or('page_template', '==', 'template-planning.blade.php')
            ->or('post_type', '==', 'cruise');

        $page
            ->addGroup('page_header', ['label' => 'Configuration de l\'Entête'])
            ->addFields(app(PageHeaderFields::class)->fields())
            ->endGroup();

        return $page->build();
    }
}
