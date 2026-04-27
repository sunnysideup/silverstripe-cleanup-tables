# Upgrade to Silverstripe 6

## Requirements

- Update `composer.json` to require `silverstripe/cms: ^6.0`

## Task Architecture Changes

⚠️ **Breaking:** `ArchiveOldRecords` now uses the Symfony Console command architecture instead of the legacy `BuildTask` pattern.

### Method Signature Changes

- Replace `run($request)` method with `execute(InputInterface $input, PolyOutput $output): int`
- Return `Command::SUCCESS` from the execute method
- Pass `$output` parameter to methods that write output: `copyTable($output)` and `moveRecords($output)`

### Property Changes

- Replace `private static $segment` with `protected static string $commandName`
- Change `protected $title` to `protected string $title` (add type)
- Change `protected $description` to `protected static string $description` (add type and make static)

### Output Changes

- Replace `DB::alteration_message()` calls with `$output->writeln()` in `moveRecords()` method (src/ArchiveOldRecords.php:89)
- Add `$output->writeln()` for table creation in `copyTable()` method (src/ArchiveOldRecords.php:72)

### New Imports Required

```php
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
```

### Command Execution

🔍 The command name remains `archive-old-records` - verify your task invocations are updated to use `sake` or `vendor/bin/sake` with the new console architecture.
