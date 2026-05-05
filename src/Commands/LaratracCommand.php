<?php

namespace Laratrac\Laratrac\Commands;

use Illuminate\Console\Command;

class LaratracCommand extends Command
{
    public $signature = 'laratrac';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
