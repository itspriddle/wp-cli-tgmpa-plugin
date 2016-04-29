Feature: Test that the tgmpa-plugin package works
  Scenario: TGM_Plugin_Activation class is not loaded
    Given a WP install

    When I try `wp tgmpa-plugin info`
    Then the return code should be 1
    Then STDERR should be:
      """
      Error: TGM_Plugin_Activation not loaded!
      """


  Scenario: TGM_Plugin_Activation class is loaded
    Given a WP install
    And I have TGMPA installed

    When I run `wp tgmpa-plugin list`
    Then the return code should be 0
    Then STDOUT should not be empty
    Then STDERR should be empty


  Scenario: tgmpa-plugin called with WP_ADMIN env var set
    Given a WP install
    And I have TGMPA installed

    When I run `WP_ADMIN=true wp eval 'var_dump(WP_ADMIN);'`
    Then the return code should be 0
    And STDOUT should be:
      """
      bool(true)
      """


  Scenario: tgmpa-plugin activate
    Given a WP install
    And I have TGMPA installed

    When I run `wp tgmpa-plugin install example-plugin`
    And I run `wp tgmpa-plugin activate example-plugin`
    Then the return code should be 0
    And STDOUT should be:
      """
      Success: Plugin 'example-plugin' activated.
      """

    When I run `wp tgmpa-plugin deactivate example-plugin`
    And I run `wp tgmpa-plugin install buddypress`
    And I run `wp tgmpa-plugin activate --all`
    Then the return code should be 0
    And STDOUT should be:
      """
      Success: Plugin 'example-plugin' activated.
      Success: Plugin 'buddypress' activated.
      """

    When I run `wp tgmpa-plugin uninstall example-plugin --deactivate`
    And I try `wp tgmpa-plugin activate example-plugin`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'example-plugin' TGMPA plugin was found but is not installed.
      """

    When I try `wp tgmpa-plugin activate bogus`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'bogus' TGMPA plugin could not be found.
      """


  Scenario: tgmpa-plugin deactivate
    Given a WP install
    And I have TGMPA installed

    When I run `wp tgmpa-plugin install example-plugin --activate`
    And I run `wp tgmpa-plugin deactivate example-plugin`
    Then the return code should be 0
    And STDOUT should be:
      """
      Success: Plugin 'example-plugin' deactivated.
      """

    When I run `wp tgmpa-plugin install buddypress --activate`
    And I run `wp tgmpa-plugin activate example-plugin`
    And I run `wp tgmpa-plugin deactivate --all`
    Then the return code should be 0
    And STDOUT should be:
      """
      Success: Plugin 'example-plugin' deactivated.
      Success: Plugin 'buddypress' deactivated.
      """

    When I run `wp tgmpa-plugin uninstall example-plugin`
    And I try `wp tgmpa-plugin deactivate example-plugin`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'example-plugin' TGMPA plugin was found but is not installed.
      """

    When I try `wp tgmpa-plugin deactivate bogus`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'bogus' TGMPA plugin could not be found.
      """


  Scenario: tgmpa-plugin delete
    Given a WP install
    And I have TGMPA installed
    And I run `wp tgmpa-plugin install example-plugin`

    When I run `wp tgmpa-plugin delete example-plugin`
    Then the return code should be 0
    And STDOUT should be:
      """
      Success: Deleted 'example-plugin' plugin.
      """

    When I try the previous command again
    Then STDERR should be:
      """
      Error: The 'example-plugin' TGMPA plugin was found but is not installed.
      """

    When I try `wp tgmpa-plugin delete buddypress`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'buddypress' TGMPA plugin was found but is not installed.
      """

    When I try `wp tgmpa-plugin delete bogus`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'bogus' TGMPA plugin could not be found.
      """


  Scenario: tgmpa-plugin get
    Given a WP install
    And I have TGMPA installed

    When I run `wp tgmpa-plugin install example-plugin --activate`
    And I run `wp tgmpa-plugin get example-plugin`
    Then the return code should be 0
    And STDOUT should be a table containing rows:
      | Field       | Value                                       |
      | name        | example-plugin                              |
      | title       | Example Plugin                              |
      | author      | Joshua Priddle                              |
      | version     | 1.0                                         |
      | description | Just an example plugin for tests and stuff. |

    When I run `wp tgmpa-plugin get example-plugin --field=name`
    Then the return code should be 0
    And STDOUT should be:
      """
      example-plugin
      """

    When I run `wp tgmpa-plugin get example-plugin --fields=name,version`
    Then the return code should be 0
    And STDOUT should be a table containing rows:
      | Field   | Value          |
      | name    | example-plugin |
      | version | 1.0            |

    When I run `wp tgmpa-plugin get example-plugin --format=json`
    Then the return code should be 0
    And STDOUT should be JSON containing:
      """
      {"name":"example-plugin","title":"Example Plugin","author":"Joshua Priddle","version":"1.0","description":"Just an example plugin for tests and stuff.","status":"active"}
      """

    When I try `wp tgmpa-plugin get buddypress`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'buddypress' TGMPA plugin was found but is not installed.
      """

    When I try `wp tgmpa-plugin get bogus`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'bogus' TGMPA plugin could not be found.
      """

  Scenario: tgmpa-plugin info
    Given a WP install
    And I have TGMPA installed

    When I run `wp eval 'echo WPMU_PLUGIN_DIR;'`
    And save STDOUT as {WPMU_PLUGIN_DIR}

    When I run `wp tgmpa-plugin info`
    Then the return code should be 0
    And STDOUT should be:
      """
      wp-cli-tgmpa-plugin version:    0.1.0
      TGM_Plugin_Activation version:  2.5.2
      TGM_Plugin_Activation location: {WPMU_PLUGIN_DIR}/tgmpa-example.php
      Plugins registered:             2
      """

    When I run `wp tgmpa-plugin info version`
    Then the return code should be 0
    And STDOUT should be:
      """
      0.1.0
      """

    When I run `wp tgmpa-plugin info tgmpa-version`
    Then the return code should be 0
    And STDOUT should be:
      """
      2.5.2
      """

    When I run `wp tgmpa-plugin info tgmpa-path`
    Then the return code should be 0
    And STDOUT should be:
      """
      {WPMU_PLUGIN_DIR}/tgmpa-example.php
      """

    When I run `wp tgmpa-plugin info plugin-count`
    Then the return code should be 0
    And STDOUT should be:
      """
      2
      """

    When I try `wp tgmpa-plugin info bogus`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Invalid section, bogus
      """


  Scenario: tgmpa-plugin install
    Given a WP install
    And I have TGMPA installed

    When I run `wp tgmpa-plugin install example-plugin`
    Then the return code should be 0
    And STDOUT should contain:
      """
      Plugin installed successfully.
      """

    When I run `wp tgmpa-plugin uninstall --all --deactivate`
    And I run `wp tgmpa-plugin install --all --activate`
    And I run `wp tgmpa-plugin list --field=name --installed --status=active`
    Then the return code should be 0
    And STDOUT should be:
      """
      example-plugin
      buddypress
      """

    When I run `wp tgmpa-plugin uninstall --all --deactivate`
    And I run `wp tgmpa-plugin install --all`
    And I run `wp tgmpa-plugin list --field=name --installed`
    Then the return code should be 0
    And STDOUT should be:
      """
      example-plugin
      buddypress
      """

    When I run `wp tgmpa-plugin uninstall --all --deactivate`
    And I run `wp tgmpa-plugin install --all-required`
    And I run `wp tgmpa-plugin list --field=name --installed`
    Then the return code should be 0
    And STDOUT should be:
      """
      example-plugin
      """

    When I run `wp tgmpa-plugin uninstall --all --deactivate`
    And I run `wp tgmpa-plugin install --all-recommended`
    And I run `wp tgmpa-plugin list --field=name --installed`
    Then the return code should be 0
    And STDOUT should be:
      """
      buddypress
      """

    When I try `wp tgmpa-plugin install bogus`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'bogus' TGMPA plugin could not be found.
      """


  Scenario: tgmpa-plugin is-installed
    Given a WP install
    And I have TGMPA installed
    And I run `wp tgmpa-plugin install example-plugin`

    When I run `wp tgmpa-plugin is-installed example-plugin`
    Then the return code should be 0
    And STDOUT should be empty

    When I try `wp tgmpa-plugin is-installed buddypress`
    Then the return code should be 1
    And STDOUT should be empty

    When I try `wp tgmpa-plugin is-installed bogus`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'bogus' TGMPA plugin could not be found.
      """


  Scenario: tgmpa-plugin list
    Given a WP install
    And I have TGMPA installed

    When I run `wp tgmpa-plugin list`
    Then STDOUT should be a table containing rows:
      | name           | title          | required | installed | status   |
      | example-plugin | Example Plugin | 1        |           | inactive |

    When I run `wp tgmpa-plugin list --required`
    Then the return code should be 0
    And STDOUT should be a table containing rows:
      | name           | title          | required | installed | status   |
      | example-plugin | Example Plugin | 1        |           | inactive |

    When I run `wp tgmpa-plugin list --no-required`
    Then the return code should be 0
    And STDOUT should be a table containing rows:
      | name       | title      | required | installed | status   |
      | buddypress | BuddyPress |          |           | inactive |

    When I run `wp tgmpa-plugin install example-plugin`
    And I run `wp tgmpa-plugin list --installed`
    Then the return code should be 0
    And STDOUT should be a table containing rows:
      | name           | title          | required | installed | status   |
      | example-plugin | Example Plugin | 1        | 1         | inactive |

    When I run `wp tgmpa-plugin list --no-installed`
    Then the return code should be 0
    And STDOUT should be a table containing rows:
      | name       | title      | required | installed | status   |
      | buddypress | BuddyPress |          |           | inactive |

    When I run `wp tgmpa-plugin list --field=name`
    Then the return code should be 0
    And STDOUT should be:
      """
      example-plugin
      buddypress
      """

    When I run `wp tgmpa-plugin list --fields=name,version`
    Then the return code should be 0
    And STDOUT should be a table containing rows:
      | name           | version |
      | example-plugin | 1       |
      | buddypress     | 2.5.2   |

    When I run `wp tgmpa-plugin list --format=json`
    Then the return code should be 0
    And STDOUT should be JSON containing:
      """
      [{"name":"example-plugin","title":"Example Plugin","required":true,"installed":true,"status":"inactive"},{"name":"buddypress","title":"BuddyPress","required":false,"installed":false,"status":"inactive"}]
      """

  Scenario: tgmpa-plugin path
    Given a WP install
    And I have TGMPA installed
    And I run `wp tgmpa-plugin install example-plugin`

    When I run `wp tgmpa-plugin path example-plugin`
    Then the return code should be 0
    And STDOUT should contain:
      """
      wp-content/plugins/example-plugin/example-plugin.php
      """

    When I run `wp tgmpa-plugin path example-plugin --dir`
    Then the return code should be 0
    And STDOUT should contain:
      """
      wp-content/plugins/example-plugin
      """
    And STDOUT should not contain:
      """
      example-plugin.php
      """

    When I try `wp tgmpa-plugin path buddypress`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'buddypress' TGMPA plugin was found but is not installed.
      """

    When I try `wp tgmpa-plugin path bogus`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'bogus' TGMPA plugin could not be found.
      """

  Scenario: tgmpa-plugin toggle
    Given a WP install
    And I have TGMPA installed
    And I run `wp tgmpa-plugin install example-plugin`

    When I run `wp tgmpa-plugin toggle example-plugin`
    Then STDOUT should be:
      """
      Success: Plugin 'example-plugin' activated.
      """

    When I run `wp tgmpa-plugin toggle example-plugin`
    Then STDOUT should be:
      """
      Success: Plugin 'example-plugin' deactivated.
      """

    When I try `wp tgmpa-plugin toggle buddypress`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'buddypress' TGMPA plugin was found but is not installed.
      """

    When I try `wp tgmpa-plugin toggle bogus`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'bogus' TGMPA plugin could not be found.
      """


  Scenario: tgmpa-plugin uninstall
    Given a WP install
    And I have TGMPA installed
    And I run `wp tgmpa-plugin install example-plugin --activate`

    When I run `wp tgmpa-plugin uninstall example-plugin`
    Then the return code should be 0
    And STDERR should be:
      """
      Warning: The 'example-plugin' plugin is active.
      """

    When I run `wp tgmpa-plugin uninstall example-plugin --deactivate`
    Then the return code should be 0
    And STDOUT should be:
      """
      Deactivating 'example-plugin'...
      Success: Plugin 'example-plugin' deactivated.
      Success: Uninstalled and deleted 'example-plugin' plugin.
      """

    When I run `wp tgmpa-plugin install --all --activate`
    And I run `wp tgmpa-plugin uninstall --all --deactivate`
    Then the return code should be 0
    And STDOUT should be:
      """
      Deactivating 'example-plugin'...
      Success: Plugin 'example-plugin' deactivated.
      Success: Uninstalled and deleted 'example-plugin' plugin.
      Deactivating 'buddypress'...
      Success: Plugin 'buddypress' deactivated.
      Success: Uninstalled and deleted 'buddypress' plugin.
      """

    When I run `wp tgmpa-plugin install --all`
    And I run `wp tgmpa-plugin uninstall --all`
    Then the return code should be 0
    And STDOUT should be:
      """
      Success: Uninstalled and deleted 'example-plugin' plugin.
      Success: Uninstalled and deleted 'buddypress' plugin.
      """

    When I try `wp tgmpa-plugin uninstall example-plugin`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'example-plugin' TGMPA plugin was found but is not installed.
      """

    When I try `wp tgmpa-plugin uninstall bogus`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'bogus' TGMPA plugin could not be found.
      """
