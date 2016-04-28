<?php

namespace Laracasts\Generators\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PivotMigrationMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:migration:pivot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration pivot class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Migration';

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
    }

    /**
     * Retrieves the desired action.
     * If no action was set in the option, create action will be returned.
     *
     * @return string
     */
    protected function getAction()
    {
        if ($this->option('action')) {
            return strtolower($this->option('action'));
        }
        else {
            return 'create';
        }
    }

    /**
     * Parse the name and format.
     *
     * @param  string $name
     * @return string
     */
    protected function parseName($name)
    {
        $tables = array_map('str_singular', $this->getSortedTableNames());
        $name = implode('', array_map('ucwords', $tables));

        $actionName = ucfirst($this->getAction());

        return "{$actionName}{$name}PivotTable";
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../stubs/pivot_'.$this->getAction().'.stub';
    }

    /**
     * Get the destination class path.
     *
     * @param  string $name
     * @return string
     */
    protected function getPath($name = null)
    {
        $filename = ($this->option('filename'))
            ? $this->option('filename')
            : date('Y_m_d_His').'_'.$name.'.php';

        $path = ($this->option('path'))
            ? base_path().$this->option('path').'/'.$filename
            : base_path().'/database/migrations/'.$filename;

        return $path;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @return string
     */
    protected function buildClass($name = null)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replacePivotTableName($stub)
            ->replaceSchema($stub)
            ->replaceClass($stub, $name);
    }

    /**
     * Apply the name of the pivot table to the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replacePivotTableName(&$stub)
    {
        $stub = str_replace('{{pivotTableName}}', $this->getPivotTableName(), $stub);

        return $this;
    }

    /**
     * Apply the correct schema to the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceSchema(&$stub)
    {
        $tables = $this->getSortedTableNames();
        $singularTableNames = array_map('str_singular', $tables);

        $fields = ($this->option('columnOne') && $this->option('columnTwo'))
            ? [$this->option('columnOne'), $this->option('columnTwo')]
            : [$singularTableNames[0].'_id', $singularTableNames[1].'_id'];

        $stub = str_replace(
            ['{{columnOne}}', '{{columnTwo}}', '{{tableOne}}', '{{tableTwo}}'],
            array_merge($fields, $tables),
            $stub
        );

        $foreignKeys = '';
        if ($this->option('useForeignKeys')) {
            $foreignKeys = '
            $table->foreign(\''.$fields[0].'\')->references(\'id\')->on(\''.$tables[0].'\')->onDelete(\'cascade\');
            $table->foreign(\''.$fields[1].'\')->references(\'id\')->on(\''.$tables[1].'\')->onDelete(\'cascade\');';
        }

        $stub = str_replace(
            '{{foreignKeys}}',
            $foreignKeys,
            $stub
        );

        return $this;
    }
    
    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = str_replace(
            '{{class}}',
            ($this->option('className') ? $this->option('className') : $name),
            $stub
        );

        return $stub;
    }

    /**
     * Get the name of the pivot table.
     *
     * @return string
     */
    protected function getPivotTableName()
    {
        return ($this->option('tableName'))
            ? $this->option('tableName')
            : implode('_', array_map('str_singular', $this->getSortedTableNames()));
    }

    /**
     * Sort the two tables in alphabetical order.
     *
     * @return array
     */
    protected function getSortedTableNames()
    {
        $tables = [
            strtolower($this->argument('tableOne')),
            strtolower($this->argument('tableTwo'))
        ];

        sort($tables);

        return $tables;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['tableOne', InputArgument::REQUIRED, 'The name of the first table.'],
            ['tableTwo', InputArgument::REQUIRED, 'The name of the second table.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['action', null, InputOption::VALUE_OPTIONAL, 'Optional action name.', false],
            ['columnOne', null, InputOption::VALUE_OPTIONAL, 'Optional name for the first field.', false],
            ['columnTwo', null, InputOption::VALUE_OPTIONAL, 'Optional name for the second field.', false],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Optional path for a migration.', false],
            ['filename', null, InputOption::VALUE_OPTIONAL, 'Optional filename for a migration.', false],
            ['tableName', null, InputOption::VALUE_OPTIONAL, 'Optional tablename for a migration.', false],
            ['className', null, InputOption::VALUE_OPTIONAL, 'Optional classname for a migration.', false],
            ['useForeignKeys', null, InputOption::VALUE_OPTIONAL, 'Optional flag to create foreign keys automatically with the pivot table.', true],
        ];
}
}
