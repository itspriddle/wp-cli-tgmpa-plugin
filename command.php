<?php

if (!defined("WP_CLI")) {
  return;
}

/**
 * Manage TGMPA plugins.
 */
class WP_CLI_TGMPA_Plugin extends WP_CLI_Command {

  /**
   * Version of this package.
   */
  const VERSION = "0.2.0";

  /**
   * TGM_Plugin_Activation instance
   */
  private $tgmpa;

  /**
   * Fields used with on `wp tgmpa-plugin list` when called without additional
   * fields.
   */
  private $fields = array(
    "name",
    "title",
    // "version",
    "required",
    "installed",
    "status",
    // "source",
    // "external"
  );

  /**
   * Registered TGMPA plugins.
   *
   * Keys are plugin slugs, values are arrays with information on the plugin. Eg:
   *
   *   array(
   *     "my-example" => array(
   *       "name"      => "my-example",
   *       "title"     => "My Example",
   *       "version"   => "",
   *       "required"  => true,
   *       "installed" => true,
   *       "status"    => "active",
   *       "source"    => "/var/www/wp/wp-content/themes/blah/plugins/my-example.zip",
   *       "external"  => false,
   *     ),
   *     ...
   *   )
   */
  private $plugins = array();

  /**
   * Initializes a new WP_CLI_TGMPA_Plugin instance.
   *
   * Detect if TGM_Plugin_Activation is loaded, otherwise emit an error and
   * bail. Build `$this->plugins` by examining plugins registered via the
   * TGM_Plugin_Activation class.
   */
  public function __construct() {
    if (!class_exists("TGM_Plugin_Activation")) {
      WP_CLI::error("TGM_Plugin_Activation not loaded!");
    }

    if (!has_action("tgmpa_register")) {
      WP_CLI::error("tgmpa_register hook not found!");
    }

    do_action("tgmpa_register");

    if (method_exists("TGM_Plugin_Activation", "get_instance")) {
      // TGMPA >= 2.4.0
      $this->tgmpa = TGM_Plugin_Activation::get_instance();
    } else {
      // TGMPA < 2.4.0
      $this->tgmpa = TGM_Plugin_Activation::$instance;
    }

    $this->tgmpa->populate_file_path();

    $installed_plugins = get_plugins();

    foreach ($this->tgmpa->plugins as $p) {
      $status    = is_plugin_active($p["file_path"]) ? "active" : "inactive";
      $installed = array_key_exists($p["file_path"], $installed_plugins);

      // Replace slug with name and name with title for parity with WP-CLI's
      // plugin commands.
      $this->plugins[$p["slug"]] = array(
        "name"      => $p["slug"],
        "title"     => $p["name"],
        "version"   => $p["version"],
        "required"  => $p["required"],
        "installed" => $installed,
        "status"    => $status,
        "source"    => $this->find_download_url($p),
        "external"  => $this->is_external($p),
      );
    }

    $this->debug(
      "Found %d TGMPA plugins: \n%s",
      count($this->plugins),
      print_r($this->plugins, true)
    );
  }

  /**
   * Show information about the TGMPA installation.
   *
   * ## OPTIONS
   *
   * [<section>]
   * : Accepted values: version, tgmpa-version, tgmpa-path, plugin-count
   *
   * ## EXAMPLES
   *
   * Show all info:
   *
   *     wp tgmpa-plugin info
   *
   * Show TGMPA version:
   *
   *     wp tgmpa-plugin info tgmpa-version
   *
   * Show path to TGMPA class:
   *
   *     wp tgmpa-plugin info tgmpa-path
   *
   * Edit TGMPA class in Vim:
   *
   *     vim $(wp tgmpa-plugin info tgmpa-path)
   *
   * Check if TGMPA is installed:
   *
   *     if wp tgmpa-plugin info &> /dev/null; then
   *       # Do stuff, maybe `wp tgmpa-plugin install --all`
   *     fi
   */
  public function info($args) {
    $valid_args = array(
      "version",
      "tgmpa-version",
      "tgmpa-path",
      "plugin-count"
    );

    if (empty($args) || $args[0] == "all") {
      $section = false;
    } else {
      $section = $args[0];

      if (!in_array($section, $valid_args)) {
        WP_CLI::error("Invalid section, {$section}");
      }
    }

    list($tgmpa_path, $tgmpa_version) = $this->tgmpa_version();

    switch ($section) {
      case "version":
        WP_CLI::line(self::VERSION);
        break;
      case "tgmpa-version":
        WP_CLI::line($tgmpa_version);
        break;
      case "tgmpa-path":
        WP_CLI::line($tgmpa_path);
        break;
      case "plugin-count":
        WP_CLI::line(count($this->plugins));
        break;
      default:
        WP_CLI::line("wp-cli-tgmpa-plugin version:    " . self::VERSION);
        WP_CLI::line("TGM_Plugin_Activation version:  " . $tgmpa_version);
        WP_CLI::line("TGM_Plugin_Activation location: " . $tgmpa_path);
        WP_CLI::line("Plugins registered:             " . count($this->plugins));
        break;
    }
  }

  /**
   * Install a TGMPA plugin.
   *
   * ## OPTIONS
   *
   * [<slug>...]
   * : One or more TGMPA plugins to install.
   *
   * [--all]
   * : If set, all TGMPA plugins will be installed.
   *
   * [--all-required]
   * : If set, all required TGMPA plugins will be installed.
   *
   * [--all-recommended]
   * : If set, all recommended (not required) TGMPA plugins will be installed.
   *
   * [--force]
   * : If set, the command will overwrite any installed version of the plugin
   * without prompting for confirmation.
   *
   * [--activate]
   * : If set, the plugin will be activated immediately after install.
   *
   * ## EXAMPLES
   *
   * Install all TGMPA plugins:
   *
   *     wp tgmpa-plugin install --all
   *
   * Install all required TGMPA plugins (excluding recommended plugins):
   *
   *     wp tgmpa-plugin install --all-required
   *
   * Install all recommended TGMPA plugins (excluding required plugins):
   *
   *     wp tgmpa-plugin install --all-recommended
   *
   * Install specific TGMPA plugins:
   *
   *     wp tgmpa-plugin install some-plugin another-plugin
   *
   * Update external TGMPA plugins:
   *
   *     wp tgmpa-plugin install --force $(wp tgmpa-plugin list --field=name --external=true)
   */
  public function install($args, $assoc_args = array()) {
    if ($this->has_flag($assoc_args, "all")) {
      $slugs   = array_keys($this->plugins);
      $filters = array();
    } elseif ($this->has_flag($assoc_args, "all-required")) {
      $slugs   = array_keys($this->plugins);
      $filters = array("required" => true);
    } elseif ($this->has_flag($assoc_args, "all-recommended")) {
      $slugs   = array_keys($this->plugins);
      $filters = array("required" => false);
    } else {
      $slugs   = $this->verify_slugs($args);
      $filters = array();
    }

    $args = $this->find_installation_sources($slugs, $filters);

    if (empty($args)) {
      WP_CLI::error("Specify one or more plugins to install, or use --all");
    } else {
      $this->dispatch("install", $args, $assoc_args);
    }
  }

  /**
   * Uninstall a TGMPA plugin.
   *
   * ## OPTIONS
   *
   * [<slug>...]
   * : One or more TGMPA plugins to uninstall.
   *
   * [--all]
   * : If set, all TGMPA plugins will be uninstalled.
   *
   * [--deactivate]
   * : Deactivate the TGMPA plugin before uninstalling. Default behavior is to
   * warn and skip if the plugin is active.
   *
   * [--skip-delete]
   * : If set, the TGMPA plugin files will not be deleted. Only the uninstall
   * procedure will be run. Note that deletions affect only the files added to
   * `WP_PLUGIN_DIR` and not bundled files used by TGMPA itself.
   */
  public function uninstall($args, $assoc_args = array()) {
    if ($this->has_flag($assoc_args, "all")) {
      $args = $this->installed_plugins();
    } else {
      $args = $this->verify_slugs($args, "installed");
    }

    if (empty($args)) {
      WP_CLI::error("Specify one or more plugins to uninstall, or use --all");
    } else {
      $this->dispatch("uninstall", $args, $assoc_args);
    }
  }

  /**
   * Get a list of plugins managed by TGMPA.
   *
   * ## OPTIONS
   *
   * [--<field>=<value>]
   * : Filter results based on the value of a field.
   *
   * [--field=<field>]
   * : Prints the value of a single field for each plugin.
   *
   * [--fields=<fields>]
   * : Limit the output to specific object fields.
   *
   * [--format=<format>]
   * : Accepted values: table, csv, json, count, yaml. Default: table
   *
   * ## AVAILABLE FIELDS
   *
   * These fields will be displayed by default for each plugin:
   *
   * * name
   * * title
   * * required
   * * installed
   * * status
   *
   * These fields are optionally available:
   *
   * * source
   * * version
   * * external
   *
   * ## EXAMPLES
   *
   * This command works like `wp plugin list`, but is limited to TGMPA plugins.
   *
   * Show default fields for all TGMPA plugins:
   *
   *     wp tgmpa-plugin list
   *
   * Output in JSON instead of a table:
   *
   *     wp tgmpa-plugin list --format=json
   *
   * Filter by installed or uninstalled TGMPA plugins:
   *
   *     wp tgmpa-plugin list --installed
   *     wp tgmpa-plugin list --no-installed
   *
   * Filter by required or unrequired TGMPA plugins:
   *
   *     wp tgmpa-plugin list --required
   *     wp tgmpa-plugin list --no-required
   *
   * Show only TGMPA plugin slugs:
   *
   *     wp tgmpa-plugin list --field=name
   *
   * Show non-default fields:
   *
   *     wp tgmpa-plugin list --fields=source,version
   *
   * @subcommand list
   */
  public function _list($_, $assoc_args) {
    $all = $this->plugins;

    foreach ($all as $key => $item) {
      foreach ($item as $field => $_) {
        if (isset($assoc_args[$field]) && $assoc_args[$field] != $item[$field]) {
          unset($all[$key]);
        }
      }
    }

    $formatter = new \WP_CLI\Formatter($assoc_args, $this->fields, "plugin");
    $formatter->display_items($all);
  }

  /**
   * Check if a TGMPA plugin is installed.
   *
   * Returns exit code 0 when installed, 1 when uninstalled.
   *
   * ## OPTIONS
   *
   * <slug>
   * : The TGMPA plugin to check.
   *
   * ## EXAMPLES
   *
   *     wp tgmpa-plugin is-installed hello
   *     echo $? # displays 0 or 1
   *
   * @subcommand is-installed
   */
  public function is_installed($args, $assoc_args = array()) {
    $this->dispatch("is-installed", $this->verify_slugs($args), $assoc_args);
  }

  /**
   * Get the path to a TGMPA plugin file or directory.
   *
   * ## OPTIONS
   *
   * <slug>
   * : The TGMPA plugin to get the path to.
   *
   * [--dir]
   * : If set, get the path to the closest parent directory, instead of the
   * plugin file.
   *
   * ## EXAMPLES
   *
   *     cd $(wp tgmpa-plugin path someplug --dir)
   *     vim $(wp tgmpa-plugin path someplug)
   */
  public function path($args, $assoc_args = array()) {
    $this->dispatch("path", $this->verify_slugs($args, "installed"), $assoc_args);
  }

  /**
   * Toggle TGMPA plugins' activation states.
   *
   * ## OPTIONS
   *
   * <slug>...
   * : One or more TGMPA plugins to toggle.
   */
  public function toggle($args) {
    $this->dispatch("toggle", $this->verify_slugs($args, "installed"));
  }

  /**
   * Activate TGMPA plugins.
   *
   * ## OPTIONS
   *
   * [<slug>...]
   * : One or more TGMPA plugins to activate.
   *
   * [--all]
   * : If set, all TGMPA plugins will be activated.
   */
  public function activate($args, $assoc_args = array()) {
    if ($this->has_flag($assoc_args, "all")) {
      $args = $this->installed_plugins();
    } else {
      $args = $this->verify_slugs($args, "installed");
    }

    $this->dispatch("activate", $args);
  }

  /**
   * Deactivate TGMPA plugins.
   *
   * ## OPTIONS
   *
   * [<slug>...]
   * : One or more TGMPA plugins to deactivate.
   *
   * [--all]
   * : If set, all TGMPA plugins will be deactivated.
   */
  public function deactivate($args, $assoc_args = array()) {
    if ($this->has_flag($assoc_args, "all")) {
      $args = $this->installed_plugins();
    } else {
      $args = $this->verify_slugs($args, "installed");
    }

    $this->dispatch("deactivate", $args);
  }

  /**
   * Delete installed TGMPA plugin files without deactivating or uninstalling.
   *
   * ## OPTIONS
   *
   * <slug>...
   * : One or more TGMPA plugins to delete.
   *
   * ## EXAMPLES
   *
   *     wp tgmpa-plugin delete hello
   *
   *     # Delete inactive plugins
   *     wp tgmpa-plugin delete $(wp tgmpa-plugin list --status=inactive --field=name)
   */
  public function delete($args) {
    $this->dispatch("delete", $this->verify_slugs($args, "installed"));
  }

  /**
   * Get info on an installed TGMPA plugin.
   *
   * ## OPTIONS
   *
   * <slug>
   * : The TGMPA plugin to get.
   *
   * [--field=<field>]
   * : Instead of returning all info, returns the value of a single field.
   *
   * [--fields=<fields>]
   * : Limit the output to specific fields. Defaults to all fields.
   *
   * [--format=<format>]
   * : Output list as table, json, CSV, yaml. Defaults to table.
   *
   * ## EXAMPLES
   *
   *     wp tgmpa-plugin get bbpress --format=json
   */
  public function get($args, $assoc_args) {
    $this->dispatch("get", $this->verify_slugs($args, "installed"), $assoc_args);
  }

  /**
   * Verifies that the given plugin slugs are registered TGMPA plugins.
   * Optionally check if those plugins are installed.
   *
   * If the plugin is not a registered TGMPA plugin, or the install check
   * fails, output an error and exit 1.
   *
   * @param array $slugs           List of plugin slugs to verify.
   * @param mixed $check_installed If set, check that plugin is installed.
   *
   * @return array
   */
  private function verify_slugs(&$slugs, $check_installed = false) {
    foreach ($slugs as $slug) {
      if (!array_key_exists($slug, $this->plugins)) {
        WP_CLI::error("The '{$slug}' TGMPA plugin could not be found.");
      }

      if ($check_installed && !$this->plugins[$slug]["installed"]) {
        WP_CLI::error("The '{$slug}' TGMPA plugin was found but is not installed.");
      }
    }

    return $slugs;
  }

  /**
   * Checks if the given arguments have a flag present.
   *
   * @param array  $args Arguments to search
   * @param string $flag Flag to search for
   *
   * @return boolean
   */
  private function has_flag(&$args, $flag) {
    if (\WP_CLI\Utils\get_flag_value($args, $flag)) {
      unset($args[$flag]);
      return true;
    } else {
      return false;
    }
  }

  /**
   * Fetches slugs for TGMPA plugins that are currently installed.
   *
   * @return array
   */
  private function installed_plugins() {
    $plugins = array_keys(array_filter($this->plugins, function($plugin) {
      return $plugin["installed"];
    }));

    if (empty($plugins)) {
      WP_CLI::error("No TGMPA plugins installed.");
    }

    return $plugins;
  }

  /**
   * Find installation sources for the given plugin slugs.
   *
   * @param array $slugs      Array of slugs to check
   * @param array $conditions Array of conditions to check
   *
   * @return array
   */
  private function find_installation_sources($slugs, $conditions = array()) {
    $sources = array();

    foreach ($slugs as $slug) {
      $plugin = $this->plugins[$slug];

      foreach ($conditions as $field => $value) {
        if (isset($plugin[$field]) && $plugin[$field] != $value) {
          continue 2;
        }
      }

      $sources[] = $plugin["source"];
    }

    return $sources;
  }

  /**
   * Find the given plugin's download URL.
   *
   * @return string
   */
  private function find_download_url($plugin) {
    if (!isset($plugin["source"]) || $plugin["source"] == "repo") {
      return $plugin["slug"];
    } elseif (!preg_match("|^http(s)?://|", $plugin["source"])) {
      return $this->tgmpa->default_path . $plugin["source"];
    } else {
      return $plugin["source"];
    }
  }

  /**
   * Determines if the given plugin is installed from an external source (i.e.
   * not bundled in the theme or hosted on wordpress.org).
   *
   * @param array $plugin - TGMPA plugin data
   *
   * @return boolean
   */
  private function is_external($plugin) {
    return isset($plugin["source"]) &&
      preg_match("|^http(s)?://|", $plugin["source"]);
  }

  /**
   * Find the installed TGMPA version.
   *
   * Grab the TGMPA version by class constant or by parsing the file itself. If
   * the version can't be detected for some reason, return unknown.
   *
   * @return array
   */
  private function tgmpa_version() {
    $tgmpa   = new ReflectionClass("TGM_Plugin_Activation");
    $file    = $tgmpa->getFileName();
    $version = "Unknown";

    if (defined("TGM_Plugin_Activation::TGMPA_VERSION")) {
      $version = TGM_Plugin_Activation::TGMPA_VERSION;
      $this->debug("Detected TGM_Plugin_Activation version %s", $version);
    } else {
      $this->debug(
        "Couldn't detect TGM_Plugin_Activation version at runtime, parsing %s",
        $file
      );

      $line = array_shift(preg_grep("/\* @version\s+(.*)\s*$/", file($file)));

      if (!is_null($line) && strpos($line, "* @version") !== false) {
        $version = trim(str_replace("* @version", "", $line));
        $this->debug("Detected TGM_Plugin_Activation version %s", $version);
      } else {
        $this->debug("Couldn't detect TGM_Plugin_Activation in %s", $file);
      }
    }

    return array($file, $version);
  }

  /**
   * Pass the given subcommand and arguments to `wp plugin`.
   *
   * @param string $subcommand `wp plugin` subcommand.
   * @param array  $args       Word arguments (eg: plugin slugs)
   * @param array  $assoc_args Associative args (eg: --all)
   */
  private function dispatch($subcommand, $args, $assoc_args = array()) {
    $this->debug(
      "wp plugin %s %s %s",
      $subcommand,
      join(" ", $args),
      join(" ", $assoc_args)
    );

    $args = array_merge(array("plugin", $subcommand), $args);

    WP_CLI::run_command($args, $assoc_args);
  }

  /**
   * Delegaates to WP_CLI::debug() with vsprintf.
   *
   * Examples:
   *
   *   $this->debug("Hi there!");
   *   $this->debug("Hey, %s, have %d cookie!", "Josh", 1);
   */
  private function debug() {
    $args = func_get_args();

    if (count($args) > 0) {
      $message = array_shift($args);
      WP_CLI::debug(trim(vsprintf($message, $args)));
    }
  }
}

/**
 * Some themes or plugins that include TGMPA use `is_admin()` to check if the
 * file should be loaded. This causes issues with plugin detection.
 *
 * This allows you to run commands with`WP_ADMIN=true` and have `is_admin()`
 * return true, eg:
 *
 *     WP_ADMIN=true wp tgmpa-plugin list
 *
 * NOTE: There are also themes and plugins that check for admin privileges in
 * addition to `is_admin()`. If the above doesn't work, try using WP-CLI's
 * global `--user=` switch, eg:
 *
 *     WP_ADMIN=true wp tgmpa-plugin list --user=josh
 */
WP_CLI::add_hook("before_wp_config_load", function() {
  if (!defined("WP_ADMIN") && getenv("WP_ADMIN")) {
    define("WP_ADMIN", true);
    WP_CLI::debug("defined WP_ADMIN");
  }
});

/**
 * Register the command.
 */
WP_CLI::add_command("tgmpa-plugin", "WP_CLI_TGMPA_Plugin");
