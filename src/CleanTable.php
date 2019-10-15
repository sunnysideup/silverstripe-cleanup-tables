<?php

namespace Sunnysideup\CleanupTables;

class CleanTable extends BuildTask
{

    private static $post_fix = '_ARCHIVE';

    private static $days_ago = 30;

    private static $tables = [
        'LoginAttempt',
    ];

    public function run($request)
    {
        foreach(Config::inst()->get(self::class, 'tables') as $table) {
            $this->setTable($table);
            $this->copyTable();
            $this->moveRecords();
        }

    }

    protected function setTable(string $table) : CleanTable
    {
        $this->table = $table;
        return $this;
    }

    protected function getTable() : string
    {
        return $this->table;
    }

    protected function getArchiveTable() : string
    {
        return $this->getTable(). Config::inst()->get(self::class, 'post_fix');
    }

    protected function copyTable()
    {
        $oldTable = $this->getTable();
        $newTable = $this->getArchiveTable();
        if(! $this->tableExists($newTable)) {
            DB::query('CREATE TABLE "'.$newTable.'" LIKE "'.$oldTable.'";');
        }
    }

    protected function getCutOffTimestamp()
    {
        return time() - (86400 * intval(Config::inst()->get(self::class, 'days_ago')));
    }

    protected function moveRecords()
    {
        $oldTable = $this->getTable();
        $newTable = $this->getArchiveTable();
        $where = ' WHERE UNIX_TIMESTAMP("'.$oldTable.'"."Created") < '. $this->getCutOffTimestamp();
        DB::query('INSERT INTO "'.$newTable.'" SELECT * FROM "'.$oldTable.' '.$where);
        DB::query('DELETE FROM "'.$oldTable.' '.$where);
    }

}
