<?php

namespace App\Filament\Widgets\Concerns;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;

use function Filament\Support\generate_icon_html;

trait HasDashboardHeadingIcon
{
    abstract protected function getDashboardHeadingIcon(): string|BackedEnum;

    public function getHeading(): string|Htmlable|null
    {
        $heading = parent::getHeading();

        if (blank($heading)) {
            return $heading;
        }

        $icon = generate_icon_html(
            $this->getDashboardHeadingIcon(),
            attributes: new ComponentAttributeBag([
                'class' => 'ppc-dashboard-summary-icon',
                'aria-hidden' => 'true',
            ]),
        );
        $headingHtml = $heading instanceof Htmlable ? $heading->toHtml() : e($heading);

        return new HtmlString(
            '<span class="ppc-dashboard-summary-heading">'
            .($icon?->toHtml() ?? '')
            .'<span>'.$headingHtml.'</span>'
            .'</span>',
        );
    }
}
