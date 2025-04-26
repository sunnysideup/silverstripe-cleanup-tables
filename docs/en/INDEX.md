
# tl;dr

Lets imagine you have 12 years worth of data and you want to archive part of this.

Here is the module for you.  It will create (if not exists) a table called:

`MyTable1_Archive` 

And move all the records from `MyTable1` into `MyTable1_Archive` where they are older than
a set number of days:

You can define it like this:

```yml
Sunnysideup\CleanupTables\CleanTable:
  tables:
    - MyTable1
    - MyTable2
  days_ago: 30
  post_fix: '_Archive'
```

Then run BuildTask.

`vendor/bin/sake dev/tasks/archive-old-records`

## IMPORTANT LIMITATION

This currently only works on simple tables, not on classes with several tables!

If you change the table you are archiving from, then you will need to manually update the archived table as well.
