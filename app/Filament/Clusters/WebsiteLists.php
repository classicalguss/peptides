<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class WebsiteLists extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Website Lists';

    protected static ?string $clusterBreadcrumb = 'Website Lists';

    protected static ?int $navigationSort = 2;
}
