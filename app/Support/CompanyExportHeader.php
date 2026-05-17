<?php

namespace App\Support;

class CompanyExportHeader
{
    public const COMPANY_NAME = 'Philfumes Petroleum Corporation';

    public const ADDRESS_LINE = 'Prk.28-C Kawayanan, Timog, Madaum, Tagum City';

    public const PROVINCE_LINE = 'Davao del Norte';

    public static function logoDataUri(): ?string
    {
        $path = public_path('image/ppcblueblack.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }

    public static function excelHtml(int $colspan): string
    {
        $titleColspan = max($colspan - 1, 1);
        $logo = self::logoDataUri();
        $logoCell = $logo
            ? '<img src="'.e($logo).'" width="72" height="72" alt="Philfumes logo">'
            : '';

        return '<table border="0" style="width:100%;font-family:Arial,Helvetica,sans-serif;color:#000;">'
            .'<tr>'
            .'<td rowspan="3" style="width:90px;text-align:center;vertical-align:middle;">'.$logoCell.'</td>'
            .'<td colspan="'.$titleColspan.'" style="text-align:center;font-size:16px;font-weight:bold;color:#000;">'.e(self::COMPANY_NAME).'</td>'
            .'</tr>'
            .'<tr><td colspan="'.$titleColspan.'" style="text-align:center;color:#000;">'.e(self::ADDRESS_LINE).'</td></tr>'
            .'<tr><td colspan="'.$titleColspan.'" style="text-align:center;color:#000;">'.e(self::PROVINCE_LINE).'</td></tr>'
            .'</table><br>';
    }

    public static function exportTitleHtml(string $title, int $colspan = 1): string
    {
        return '<table border="0" style="width:100%;font-family:Arial,Helvetica,sans-serif;color:#000;">'
            .'<tr><td colspan="'.$colspan.'" style="text-align:center;font-size:13px;font-weight:bold;color:#000;padding:4px 0 8px;">'.e($title).'</td></tr>'
            .'</table>';
    }

    public static function generatedAt(): string
    {
        return now()->format('M d, Y h:i A');
    }

    public static function generatedAtHtml(): string
    {
        return '<br><div style="font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#000;text-align:right;">Date Generated: '
            .e(self::generatedAt())
            .'</div>';
    }

    public static function tableAttributes(): string
    {
        return 'border="1" style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#000;border:1px solid #000;"';
    }

    public static function thStyle(): string
    {
        return 'style="background:#f3f4f6;color:#000;border:1px solid #000;font-weight:bold;text-align:center;padding:4px;"';
    }

    public static function tdStyle(): string
    {
        return 'style="color:#000;border:1px solid #000;padding:4px;"';
    }

    public static function printScript(): string
    {
        return "const title = document.title; document.title = ''; window.addEventListener('afterprint', () => { document.title = title; }, { once: true }); window.print();";
    }
}
