<?php

namespace Sunnysideup\CleanupTables;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

class ArchiveOldRecords extends BuildTask
{
    protected $_schema;

    private static $segment = 'archive-old-records';

    protected $title = 'Archive old records from selected tables';

    protected $description = '
        You can set a list of tables and an expiration date.
        This will create a new table with the same name and the post-fix "_ARCHIVE" and
        move all records older than the expiration date to that table.';

    private static array $tables = [
        'LoginAttempt',
    ];

    private static string $post_fix = '_ARCHIVE';

    private static int $days_ago = 365;

    private static bool $delete_only = false;

    protected string $table = '';

    private array $cacheTableExists = [];

    public function run($request)
    {
        foreach (Config::inst()->get(self::class, 'tables') as $table) {
            $this->setTable($table);
            $this->copyTable();
            $this->moveRecords();
        }
    }

    protected function setTable(string $table): ArchiveOldRecords
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
        $deleteOnly = Config::inst()->get(static::class, 'delete_only');
        if (! $this->tableExists($newTable) && $deleteOnly !== true) {
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
        $deleteOnly = Config::inst()->get(static::class, 'delete_only');
        DB::alteration_message('Archiving ' . $count . ' records from ' . $oldTable . ' to ' . $newTable, 'created');
        if (! (bool) $deleteOnly) {
            DB::query('INSERT INTO "' . $newTable . '" SELECT * FROM "' . $oldTable . '" ' . $where);
        }
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
