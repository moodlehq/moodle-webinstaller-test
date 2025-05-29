Feature: Web Installation testing

  Scenario: Verify installation of Moodle is successful
    Given I am on the moodle site to be installed

    # First page: Choose a language
    And I should see "Installation"
    And I should see "Choose a language"
    And I should see "Please choose a language for the installation. This language will also be used as the default language for the site, though it may be changed later."
    And I press "Next"

    # Second page: Confirm paths
    And I should see "Confirm paths"
    And I press "Next"

    # Choose database driver
    And I fill in the database type
    And I press "Next"

    # Database settings
    And I fill in the database settings
    And I press "Next"

    # Installation (if config.php was writable)
    And I should see "Installation"
    And I should see "Modular Object-Oriented Dynamic Learning Environment"
    And I should see "Have you read these conditions and understood them?"
    And I press "Continue"

    # Server checks
    And I should see "Server checks"
    And I press "Continue"

    # All modules installation
    And I press "Continue"

    # Admin user account
    And I should see "On this page you should configure your main administrator account"
    And I fill in "email" with "admin@moodle.com"
    And I fill in "newpassword" with "Web1nstall123!"
    And I press "Update profile"

    # Site home settings
    And I fill in "s__fullname" with "site test"
    And I fill in "s__shortname" with "sitetest"
    And I fill in "s__supportemail" with "admin@moodle.com"
    And I fill in "s__noreplyaddress" with "admin@moodle.com"
    When I press "Save changes"

    # Installation finished
    Then I should see "Welcome, admin!"
