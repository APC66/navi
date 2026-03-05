<?php

namespace App\View\Components\Partials;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Socials extends Component
{
    /**
     * Socials links
     *
     * @var array
     */
    public ?Collection $socialsLinks = null;

    /**
     * FontAwesome Default Class for socials icons
     */
    private array $defaultIcons = [
        'facebook' => 'bi-facebook',
        'x' => 'si-x',
        'instagram' => 'bi-instagram',
        'linkedin' => 'bi-linkedin ',
        'youtube' => 'bi-youtube',
        'pinterest' => 'bi-pinterest',
        'tiktok' => 'bi-tiktok',
        'google' => 'bi-google',
    ];

    /**
     * Icons Overwrite
     */
    public ?array $icons;

    /**
     * Socials icons to render
     * [socialLink => iconClass]
     */
    private ?array $renderSocials = [];

    /**
     * Icon Class
     */
    public ?string $linkClass;

    /**
     * Container Class
     */
    public ?string $containerClass;

    /**
     * Use BladeIcons ?
     */
    public bool $useBlade = false;

    /**
     * Create a new component instance.
     */
    public function __construct(?array $icons = [], ?string $linkClass = '', ?string $containerClass = '', ?bool $useBlade = false)
    {
        $this->useBlade = $useBlade;
        $this->containerClass = $containerClass;
        $this->linkClass = $linkClass;
        $this->icons = $icons;

        if (have_rows('social_medias', 'options')) {
            $this->socialsLinks = collect(get_field('social_medias', 'options'));
            $this->setSocialIcons();
        }
    }

    public function setSocialIcons(): void
    {
        foreach ($this->socialsLinks as $social) {
            $this->renderSocials[$social['media']]['link'] = $social['link'];
            if ($this->useBlade === true) {
                if (in_array($social['media'], $this->icons)) {
                    $this->renderSocials[$social['media']]['type'] = 'blade';
                    $this->renderSocials[$social['media']]['icon'] = $social['media'];
                } else {
                    $this->renderSocials[$social['media']]['type'] = 'icon';
                    $this->renderSocials[$social['media']]['icon'] = $this->defaultIcons[$social['media']];
                }
            } else {
                if ($social['media'] !== 'other') {
                    $this->renderSocials[$social['media']]['type'] = 'icon';
                    $this->renderSocials[$social['media']]['icon'] = $this->defaultIcons[$social['media']];
                } else {
                    $this->renderSocials[$social['media']]['type'] = 'icon';
                    $this->renderSocials[$social['link']] = $social['other'];
                }
            }
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.partials.socials', [
            'socials' => $this->renderSocials,
            'containerClasses' => $this->containerClass,
            'iconClasses' => $this->linkClass,
        ]);
    }
}
