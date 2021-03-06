<?php namespace Fly\Generators\Commands;

use Fly\Generators\Generators\MigrationGenerator;
use Fly\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MigrationGeneratorCommand extends BaseGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new migration.';

    /**
     * Model generator instance.
     *
     * @var Fly\Generators\Generators\MigrationGenerator
     */
    protected $generator;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(MigrationGenerator $generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $name = $this->argument('name');
        $path = $this->getPath();
        $fields = $this->option('fields');

        $created = $this->generator
                        ->parse($name, $fields)
                        ->make($path, null);

        if ($created) {
            $path = $this->generator->path;
        }
        
        $this->call('dump-autoload');

        $this->printResult($created, $path);
    }


    /**
     * Same as base method, but adds the date to the file name
     * @param  boolean $successful
     * @param  string $path
     * @return string
     */
    protected function printResult($successful, $path)
    {
        return parent::printResult($successful, dirname($path).'/'.$this->generator->date.'_'.basename($path));
    }

    /**
     * Get the path to the file that should be generated.
     *
     * @return string
     */
    protected function getPath()
    {
       return $this->option('path') . '/' . ucwords($this->argument('name')) . '.php';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('name', InputArgument::REQUIRED, 'Name of the migration to generate.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('path', null, InputOption::VALUE_OPTIONAL, 'The path to the migrations folder', app_path() . '/database/migrations'),
            array('fields', null, InputOption::VALUE_OPTIONAL, 'Table fields', null)
        );
    }

}
