<?php

namespace App\Filament\Widgets;

use App\Models\PdnsRecord;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DnsRecordTypeChart extends ChartWidget
{
    protected ?string $heading = 'DNS Record Distribution';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $counts = PdnsRecord::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $labels = array_keys($counts);
        $data = array_values($counts);

        if (empty($labels)) {
            $labels = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SOA'];
            $data = [0, 0, 0, 0, 0, 0, 0];
        }

        $colors = [
            '#3b82f6', // blue (A)
            '#6366f1', // indigo (AAAA)
            '#06b6d4', // cyan (CNAME)
            '#f59e0b', // amber (MX)
            '#10b981', // emerald (TXT)
            '#8b5cf6', // purple (NS)
            '#ef4444', // red (SOA)
            '#ec4899', // pink (SRV)
            '#14b8a6', // teal (CAA)
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Records',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 0,
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
