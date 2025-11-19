<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cms:publish')]
class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all of the Orbit resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('vendor:publish', [
            '--tag'   => 'orchid-assets',
            '--force' => true,
        ]);
    }
}
