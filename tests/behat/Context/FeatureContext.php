<?php
namespace Context;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\Context;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context {

    /** @var int Number of page dumps written so far, used to name the dump files. */
    protected static $dumpcount = 0;

    /**
     * @Given I am on the moodle site to be installed
     */
    public function iAmOnTheMoodleSiteToBeInstalled() {
        $baseurl = getenv('MOODLE_SITE_URL') ?: 'http://localhost/moodle';

        $this->visitPath("{$baseurl}");
    }

    /**
     * @When I fill in the database type
     */
    public function iFillInTheDatabaseType() {
        $dbtype = getenv('DB_TYPE') ?: 'pgsql';

        $this->fillField('dbtype', $dbtype);
    }

    /**
     * @When I fill in the database settings
     */
    public function iFillInTheDatabaseSettings() {
        $dbhost = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'moodle';
        $dbuser = getenv('DB_USER') ?: 'postgres';
        $dbpass = getenv('DB_PASS') ?: 'moodle';

        $this->fillField('dbhost', $dbhost);
        $this->fillField('dbname', $dbname);
        $this->fillField('dbuser', $dbuser);
        $this->fillField('dbpass', $dbpass);
    }

    /**
     * Report what the browser actually received whenever something goes wrong.
     *
     * A failing step dumps the response to the artifacts directory and summarises it on stdout, so
     * that the reason for the failure (a PHP error page, for instance) is visible in the CI log
     * instead of only the missing text. A step that passed but returned an error status is failed
     * here as well, so the error is reported against the step that caused it rather than against a
     * later assertion.
     *
     * @AfterStep
     */
    public function reportFailureDetails(AfterStepScope $scope) {
        $status = $this->getResponseStatus();

        if ($scope->getTestResult()->isPassed()) {
            if ($status === null || $status < 400) {
                return;
            }
            $this->dumpResponse($scope, $status);
            throw new \RuntimeException("The server responded with HTTP {$status} for {$this->getCurrentUrlOrNull()}");
        }

        $this->dumpResponse($scope, $status);
    }

    /**
     * Write the current response to the artifacts directory and summarise it on stdout.
     *
     * @param AfterStepScope $scope The scope of the step being reported on.
     * @param int|null $status The HTTP status of the response, if the driver could report one.
     */
    protected function dumpResponse(AfterStepScope $scope, ?int $status): void {
        $url = $this->getCurrentUrlOrNull();
        $content = $this->getResponseContent();
        $step = $scope->getStep();
        $feature = basename($scope->getFeature()->getFile());

        $dir = $this->getArtifactDir();
        $name = sprintf('failure-%02d-line%d', ++self::$dumpcount, $step->getLine());

        if ($content !== null) {
            file_put_contents("{$dir}/{$name}.html", $content);
        }
        if ($url !== null) {
            // Consumed by screenshot.js so that the failure screenshot shows the page we stopped on.
            file_put_contents("{$dir}/last-url.txt", $url);
        }

        echo "\n---- PAGE DUMP (step failed) ----\n";
        echo "Step: {$step->getKeyword()} {$step->getText()} ({$feature}:{$step->getLine()})\n";
        echo 'URL: ' . ($url ?? 'unknown') . "\n";
        echo 'HTTP status: ' . ($status ?? 'unknown') . "\n";
        echo $content === null ? "Response: unavailable\n" : "Response saved to: artifacts/{$name}.html\n";

        if ($content !== null) {
            echo "---- PAGE TEXT (first 40 lines) ----\n";
            echo implode("\n", $this->getVisibleText($content, 40)) . "\n";
        }
        echo "---- END PAGE DUMP ----\n\n";
    }

    /**
     * Reduce an HTML response to the lines of text a reader would see.
     *
     * @param string $content The raw response body.
     * @param int $maxlines The maximum number of non-empty lines to return.
     * @return string[] The extracted lines of text.
     */
    protected function getVisibleText(string $content, int $maxlines): array {
        // Scripts and styles carry no useful text but plenty of noise, so drop them before stripping tags.
        $stripped = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $content) ?? $content;
        $text = html_entity_decode(strip_tags($stripped), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = [];
        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line));
            if ($line !== '') {
                $lines[] = $line;
            }
            if (count($lines) >= $maxlines) {
                break;
            }
        }

        return $lines;
    }

    /**
     * The directory that failure artifacts are written to, created if needed.
     *
     * @return string An absolute path.
     */
    protected function getArtifactDir(): string {
        $dir = getenv('ARTIFACT_DIR') ?: getcwd() . '/artifacts';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }

    /**
     * The HTTP status of the current response, or null when the driver cannot report one.
     *
     * @return int|null
     */
    protected function getResponseStatus(): ?int {
        try {
            return $this->getSession()->getStatusCode();
        } catch (\Exception $e) {
            // No page has been requested yet, or the driver does not expose the status.
            return null;
        }
    }

    /**
     * The body of the current response, or null when there is nothing to report.
     *
     * @return string|null
     */
    protected function getResponseContent(): ?string {
        try {
            return $this->getSession()->getPage()->getContent();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * The URL of the current response, or null when there is nothing to report.
     *
     * @return string|null
     */
    protected function getCurrentUrlOrNull(): ?string {
        try {
            return $this->getSession()->getCurrentUrl();
        } catch (\Exception $e) {
            return null;
        }
    }
}
