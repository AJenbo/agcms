<?php

namespace App;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigExtensions extends AbstractExtension
{
    /**
     * @return array<int, TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('trans', 'gettext'),
            new TwigFilter('money', function (string $amount, bool $thousan = true, int $decimals = 2): string {
                $conv = localeconv();
                $decimal_point = $conv['mon_decimal_point'];
                $thousands_sep = '';
                if ($thousan) {
                    $thousands_sep = $conv['mon_thousands_sep'];
                }

                return number_format($amount, $decimals, $decimal_point, $thousands_sep);
            }),
        ];
    }
}
