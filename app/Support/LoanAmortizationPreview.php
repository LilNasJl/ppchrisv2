<?php

namespace App\Support;

use App\Services\LoanTermsService;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;

class LoanAmortizationPreview
{
    public static function make(string $periodField): Placeholder
    {
        return Placeholder::make('amortization_preview')
            ->label('Amortization Preview')
            ->content(function (Get $get) use ($periodField) {
                $data = [];
                foreach (['terms_basis', 'loan_amount', 'loan_interest', 'interest_rate', 'loan_terms_months', 'schedule', 'payment_amount', $periodField] as $field) {
                    $data[$field] = $get($field);
                }

                return view('filament.pages.partials.loan-amortization-preview', ['preview' => app(LoanTermsService::class)->preview($data, $periodField)]);
            })
            ->columnSpanFull();
    }
}
