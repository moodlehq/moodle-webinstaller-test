# Moodle Web Installation Testing

This repository contains the testing suite for the Moodle web installation process.

## Pre-requisites
- A web server with PHP and a database server installed, as if you were going to install Moodle manually.
- Composer installed.
- The Moodle codebase, you can clone the code from the Moodle repository.

## Getting Started
```bash
# Get Moodle code, you could select another version branch (skip this if you already got the code)
git clone -b main git://git.moodle.org/moodle.git moodle

# Clone the web install repository.
git git@github.com:moodlehq/moodle-webinstaller-test.git
cd moodle-webinstaller-test

# Install the dependencies.
composer install

# URL to the moodle site to be installed.
export MOODLE_SITE_URL="http://localhost/moodle"

# Database connection details, ensure the database is created before running the tests.
export DB_TYPE=pgsql
export DB_HOST=localhost
export DB_NAME=moodle
export DB_USER=postgres
export DB_PASS=moodle

# Ensure the site to be installed has write permissions so moodle can write the config.php file.
sudo chown -R www-data:www-data /path/to/moodle

# Run the tests
/vendor/bin/behat
```

## Diagnosing a failure
Whenever a step fails, the response the browser received is written to the `artifacts` directory and
summarised on stdout, so the reason for the failure is visible without reproducing it:

```
---- PAGE DUMP (step failed) ----
Step: And I press "Next" (install.feature:22)
URL: http://localhost:8080/admin/index.php?lang=en
HTTP status: 500
Response saved to: artifacts/failure-01-line22.html
---- PAGE TEXT (first 40 lines) ----
Error
Exception - Interface "League\OAuth2\Server\Repositories\ScopeRepositoryInterface" not found
---- END PAGE DUMP ----
```

A step that returned an HTTP error status fails at that point rather than at a later assertion, so the
failure is reported against the request that caused it.

In GitHub Actions the same information is summarised on the run page, and the `diagnostics-<php>`
artifact holds the full Behat output, the saved responses, the screenshots, the web server log and the
Moodle revision under test (HEAD, version and the last 20 commits, which is usually enough to identify
what broke it).

## Troubleshooting
The test execution usually takes around 54 seconds to complete, the common issues that might prevent the tests from passing are:
- The database connection details are incorrect.
- The moodle site URL is incorrect.
- The moodle site directory does not have write permissions and Moodle is unable to write the config.php file.
- There's a config.php file in the moodle site directory.
