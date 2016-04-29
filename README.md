# wp-cli-tgmpa-plugin

This WP-CLI package provides the `wp tgmpa-plugin` command for working with
plugins required using the [TGM Plugin Activation][] library.

Quick links: [Installing](#installing) | [Usage](#usage) | [Troubleshooting](#troubleshooting) | [Contributing](#contributing)

## Installing

### Installation as a WP-CLI package

Installing this package requires WP-CLI v0.23.0 or greater. Update to the
latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install
itspriddle/wp-cli-tgmpa-plugin`

### Manual installation

Save [`command.php`][command.php]. For a system-wide installation, add the
following to `~/.wp-cli/config.yml`; to install just for a single WordPress
blog add it to `wp-cli.yml` or `wp-cli.local.yml` in the root of your project:

```yaml
require:
  - /path/to/command.php
```

Note that you should add to the `require` array if you already have one
present, eg:

```yaml
require:
  - /some/file/i/already/have.php
  - /path/to/command.php
```

You can also pass the `--require` flag when calling `wp tgmpa-plugin`, eg:

```
wp --require=/path/to/command.php tgmpa-plugin
```

### Verify installation

In the root of your blog, run `wp tgmpa-plugin info` to verify that
the `TGM_Plugin_Activation` class is loaded. You should see output similar to
this:

```
wp-cli-tgmpa-plugin version:    0.1.0
TGM_Plugin_Activation version:  2.5.2
TGM_Plugin_Activation location: /var/www/wordpress/public/wp-content/themes/footheme/includes/plugins/class-tgm-plugin-activation.php
Plugins registered:             2
```

See [Troubleshooting](#troubleshooting) for things to check if you see `Error:
TGM_Plugin_Activation not loaded!`

## Usage

`wp tgmpa-plugin` provides most of the same commands as `wp plugin`. Most
commands are delegated directly to `wp plugin` after validating that any
specified plugins are registered with TGMPA.

### wp tgmpa-plugin activate

```
wp tgmpa-plugin activate [<slug>...] [--all]
```

Activate TGMPA plugins.

**OPTIONS**

```
[<slug>...]
One or more TGMPA plugins to activate.

[--all]
If set, all TGMPA plugins will be activated.
```

### wp tgmpa-plugin deactivate

```
wp tgmpa-plugin deactivate [<slug>...] [--all]
```

Deactivate TGMPA plugins.

**OPTIONS**

```
[<slug>...]
One or more TGMPA plugins to deactivate.

[--all]
If set, all TGMPA plugins will be deactivated.
```

### wp tgmpa-plugin delete

```
wp tgmpa-plugin delete <slug>...
```

Delete installed TGMPA plugin files without deactivating or uninstalling.

**OPTIONS**

```
<slug>...
One or more TGMPA plugins to delete.
```

**EXAMPLES**

```
wp tgmpa-plugin delete hello

# Delete inactive plugins
wp tgmpa-plugin delete $(wp tgmpa-plugin list --status=inactive --field=name)
```

### wp tgmpa-plugin get

```
wp tgmpa-plugin get <slug> [--field=<field>] [--fields=<fields>] [--format=<format>]
```

Get info on an installed TGMPA plugin.

**OPTIONS**

```
<slug>
The TGMPA plugin to get.

[--field=<field>]
Instead of returning all info, returns the value of a single field.

[--fields=<fields>]
Limit the output to specific fields. Defaults to all fields.

[--format=<format>]
Output list as table, json, CSV, yaml. Defaults to table.
```

**EXAMPLES**

```
wp tgmpa-plugin get bbpress --format=json
```

### wp tgmpa-plugin info

```
wp tgmpa-plugin info [<section>]
```

Show information about the TGMPA installation.

**OPTIONS**

```
[<section>]
Accepted values: version, tgmpa-version, tgmpa-path, plugin-count
```

**EXAMPLES**

```
Show all info:

wp tgmpa-plugin info

Show TGMPA version:

wp tgmpa-plugin info tgmpa-version

Show path to TGMPA class:

wp tgmpa-plugin info tgmpa-path

Edit TGMPA class in Vim:

vim $(wp tgmpa-plugin info tgmpa-path)

Check if TGMPA is installed:

if wp tgmpa-plugin info &> /dev/null; then
  # Do stuff, maybe `wp tgmpa-plugin install --all`
fi
```

### wp tgmpa-plugin install

```
wp tgmpa-plugin install [<slug>...] [--all] [--all-required] [--all-recommended] [--force] [--activate]
```

Install a TGMPA plugin.

**OPTIONS**

```
[<slug>...]
One or more TGMPA plugins to install.

[--all]
If set, all TGMPA plugins will be installed.

[--all-required]
If set, all required TGMPA plugins will be installed.

[--all-recommended]
If set, all recommended (not required) TGMPA plugins will be installed.

[--force]
If set, the command will overwrite any installed version of the plugin
without prompting for confirmation.

[--activate]
If set, the plugin will be activated immediately after install.
```

**EXAMPLES**

```
Install all TGMPA plugins:

wp tgmpa-plugin install --all

Install all required TGMPA plugins (excluding recommended plugins):

wp tgmpa-plugin install --all-required

Install all recommended TGMPA plugins (excluding required plugins):

wp tgmpa-plugin install --all-recommended

Install specific TGMPA plugins:

wp tgmpa-plugin install some-plugin another-plugin
```

### wp tgmpa-plugin is-installed

```
wp tgmpa-plugin is-installed <slug>
```

Check if a TGMPA plugin is installed.

**OPTIONS**

```
<slug>
The TGMPA plugin to check.
```

**EXAMPLES**

```
wp tgmpa-plugin is-installed hello
echo $? # displays 0 or 1
```

### wp tgmpa-plugin list

```
wp tgmpa-plugin list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
```

Get a list of plugins managed by TGMPA.

**OPTIONS**

```
[--<field>=<value>]
Filter results based on the value of a field.

[--field=<field>]
Prints the value of a single field for each plugin.

[--fields=<fields>]
Limit the output to specific object fields.

[--format=<format>]
Accepted values: table, csv, json, count, yaml. Default: table
```

**EXAMPLES**

```
This command works like `wp plugin list`, but is limited to TGMPA plugins.

Show default fields for all TGMPA plugins:

wp tgmpa-plugin list

Output in JSON instead of a table:

wp tgmpa-plugin list --format=json

Filter by installed or uninstalled TGMPA plugins:

wp tgmpa-plugin list --installed
wp tgmpa-plugin list --no-installed

Filter by required or unrequired TGMPA plugins:

wp tgmpa-plugin list --required
wp tgmpa-plugin list --no-required

Show only TGMPA plugin slugs:

wp tgmpa-plugin list --field=name

Show non-default fields:

wp tgmpa-plugin list --fields=source,version
```

### wp tgmpa-plugin path

```
wp tgmpa-plugin path <slug> [--dir]
```

Get the path to a TGMPA plugin file or directory.

**OPTIONS**

```
<slug>
The TGMPA plugin to get the path to.

[--dir]
If set, get the path to the closest parent directory, instead of the
plugin file.
```

**EXAMPLES**

```
cd $(wp tgmpa-plugin path someplug --dir)
vim $(wp tgmpa-plugin path someplug)
```

### wp tgmpa-plugin toggle

```
wp tgmpa-plugin toggle <slug>...
```

Toggle TGMPA plugins' activation states.

**OPTIONS**

```
<slug>...
One or more TGMPA plugins to toggle.
```

### wp tgmpa-plugin uninstall

```
wp tgmpa-plugin uninstall [<slug>...] [--all] [--deactivate] [--skip-delete]
```

Uninstall a TGMPA plugin.

**OPTIONS**

```
[<slug>...]
One or more TGMPA plugins to uninstall.

[--all]
If set, all TGMPA plugins will be uninstalled.

[--deactivate]
Deactivate the TGMPA plugin before uninstalling. Default behavior is to
warn and skip if the plugin is active.

[--skip-delete]
If set, the TGMPA plugin files will not be deleted. Only the uninstall
procedure will be run. Note that deletions affect only the files added to
`WP_PLUGIN_DIR` and not bundled files used by TGMPA itself.
```

## Troubleshooting

If the `TGM_Plugin_Activation` class is not loaded your theme or plugin may be
using `is_admin()` to determine whether or not to load the class. To work
around this, you can set `WP_ADMIN=true` environment variable when running
`tgmpa-plugin` commands. Try the following to see if the class loads:

```
WP_ADMIN=true wp tgmpa-plugin info
```

If that doesn't work, some themes _also_ check your user account for admin
privileges. You can try the above along with using the `--user=` flag with a
valid admin user:

```
WP_ADMIN=true wp tgmpa-plugin info --user=josh
```

If all else fails, you can [open an issue][]. Please include a link to the
theme or plugin as well as the commands you are trying or any pertinent error
messages you see.

## Contributing

Code and ideas are more than welcome.

Please [open an issue][] with questions, feedback, bug fixes or improvements.
Pull requests should use the same coding style and should include test
coverage.

[Open an issue]: https://github.com/itspriddle/wp-cli-tgmpa-plugin/issues
[TGM Plugin Activation]: http://tgmpluginactivation.com/
[command.php]: https://github.com/itspriddle/wp-cli-tgmpa-plugin/raw/master/command.php
