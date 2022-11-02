# Shell Verbosity is Evil

The `SHELL_VERBOSITY` environment variable was introduced in `symfony/console` 3.4 and still appears in `symfony/console` 6.2.  It defines a gooey,
sticky form of verbosity.

The theory seems to be: if you call a command in verbose mode (`-v`), and if it calls any subcommands, then all those subcommands should also run in
verbose mode.  I suppose this is OK if the various commands follow simple, sequential procedures with outputs shown to a human.

But this convention is actively harmful when programs are _composed_ in Unix-style.  For simplicity, consider a Symfony Console command (`snafu`)
which invokes a subcommand:

```php
class SnafuCommand extends Command {
  public function execute(...) {
    shell_exec('find_data | filter_data > /tmp/my_data.json');
    $data = json_decode(file_get_contents('/tmp/my_data.json'));
  }
}
```

If you call `snafu -v`, then Symfony surrepetitiously sets `SHELL_VERBOSITY=1` and propagates it to each subprocess.  So `filter_data` receives
`SHELL_VERBOSITY=1` and begins outputting a processing log -- in addition to its regular JSON output.  Now, the file `/tmp/my_data.json` is no longer
JSON -- it is JSON plus random noise.  So anything reading `my_data.json` will break.

The overall effect is to make the system flaky.  Any Symfony-based command can cause this problem (by setting `SHELL_VERBOSITY`) or become broken by
it (by accepting `SHELL_VERBOSITY`).  If something breaks, you have to trawl the process-graph to find the two parties to the breakage.  The breakage
only happens when running `snafu -v`.  If you run commands individually, or if you run `snafu` normally, then it works -- which will confound
debugging efforts.

Of course, the reason to use `-v` is to debug something. If `-v` itself causes another bug, then you're investigating the combined behavior of two bugs.

## How to remove SHELL_VERBOSITY

Friends don't let friends use `SHELL_VERBOSITY`. This repo defines an adapter to kill `SHELL_VERBOSITY`.

```
composer require lesser-evil/shell-verbosity-is-evil
```

In Symfony 3.4 - 6.2, the `SHELL_VERBOSITY` behavior is defined by `Application::configureIO()`. Override this:

```php
use LesserEvil\ShellVerbosityIsEvil;

class MyApplication extends Application {
  protected function configureIO(InputInterface $input, OutputInterface $output) {
    ShellVerbosityIsEvil::doWithoutEvil(function() use ($input, $output) {
      parent::configureIO($input, $output);
    });
  }
}
```
