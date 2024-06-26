<?php

namespace Sunnysideup\CleanupTables;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

class CleanTable extends BuildTask
{
    protected $_schema;

    private static $post_fix = '_ARCHIVE';

    private static $days_ago = 30;

    private static $table = '';

    private static $cacheTableExists = [];

    private static $tables = [
        'LoginAttempt',
    ];

    public function run($request)
    {
        foreach (Config::inst()->get(self::class, 'tables') as $table) {
            $this->setTable($table);
            $this->copyTable();
            $this->moveRecords();
        }
    }

    protected function setTable(string $table): CleanTable
    {
        $this->table = $table;

        return $this;
    }

    protected function getTable(): string
    {
        return $this->table;
    }

    protected function getArchiveTable(): string
    {
        return $this->getTable() . Config::inst()->get(self::class, 'post_fix');
    }

    protected function copyTable()
    {
        $oldTable = $this->getTable();
        $newTable = $this->getArchiveTable();
        if (! $this->tableExists($newTable)) {
            DB::query('CREATE TABLE "' . $newTable . '" LIKE "' . $oldTable . '";');
        }
    }

    protected function getCutOffTimestamp(): int
    {
        return time() - intval(86400 * intval(Config::inst()->get(self::class, 'days_ago')));
    }

    protected function moveRecords()
    {
        $oldTable = $this->getTable();
        $newTable = $this->getArchiveTable();
        $where = ' WHERE UNIX_TIMESTAMP("' . $oldTable . '"."LastEdited") < ' . $this->getCutOffTimestamp();
        $count = DB::query('SELECT COUNT(*) FROM "' . $oldTable . '" ' . $where)->value();
        DB::alteration_message('Archiving ' . $count . ' records from ' . $oldTable . ' to ' . $newTable, 'created');
        DB::query('INSERT INTO "' . $newTable . '" SELECT * FROM "' . $oldTable . '" ' . $where);
        DB::query('DELETE FROM "' . $oldTable . '" ' . $where);
    }

    protected function tableExists($tableName): bool
    {
        $schema = $this->getSchema();
        return (bool) ($this->cacheTableExists[$tableName] = $schema->hasTable($tableName));
    }

    protected function getSchema()
    {
        if (null === $this->_schema) {
            $this->_schema = DB::get_schema();
            $this->_schema->schemaUpdate(function () {
                return true;
            });
        }

        return $this->_schema;
    }
}
