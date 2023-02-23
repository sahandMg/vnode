<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseCheckerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check if database is lock or not';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!str_contains(shell_exec('x-ui status'), 'running')) {
            shell_exec('cp /etc/x-ui/x-ui.db /etc/x-ui/x-ui.db.back');
            shell_exec('rm /etc/x-ui/x-ui.db');
            shell_exec('mv /etc/x-ui/x-ui.db.back /etc/x-ui/x-ui.db');
            shell_exec('chmod -R 777 /etc/x-ui/x-ui.db');
            shell_exec('x-ui restart');
        }
    }
}
