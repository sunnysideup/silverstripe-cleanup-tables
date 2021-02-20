Backup stale parts of your table.


use:
```yml
Sunnysideup\CleanupTables\CleanTable:
  tables:
    - MyTable1
    - MyTable2
  days_ago: 30
  post_fix: '_Archive'
```

Then run BuildTask.
