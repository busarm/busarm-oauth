<?php
namespace Database;

use Phinx\Migration\AbstractMigration;

class BaseMigration extends AbstractMigration
{
    /**
     * Run in try catch sandox to prevent one failing from affecting another
     *
     * @param callable $caller
     * @return void
     */
    protected function sandbox(callable $caller)
    {
        try {
            $caller();
        } catch (\Throwable $th) {
            echo $th->getMessage() . PHP_EOL;
            return;
        }
    }
}