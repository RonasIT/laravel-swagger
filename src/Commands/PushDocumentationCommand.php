<?php

namespace RonasIT\AutoDoc\Commands;

use Illuminate\Console\Command;
use RonasIT\AutoDoc\Services\SwaggerService;

class PushDocumentationCommand extends Command
{
    protected $signature = 'swagger:push-documentation';
    protected $description = 'Push swagger collected documentation';

    public function handle(): int
    {
        app(SwaggerService::class)->saveProductionData();

        return 0;
    }
}
