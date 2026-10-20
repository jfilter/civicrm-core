<?php

namespace Civi\Test;

use Playwright\Browser\BrowserContextInterface;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;
use Playwright\PlaywrightClient;
use Playwright\PlaywrightFactory;
use Playwright\Testing\Expect;
use Playwright\Testing\ExpectDecorator;
use Playwright\Testing\ExpectInterface;

/**
 * Class PlaywrightBase. Browser tests driven by Playwright, on the same E2E harness as MinkBase.
 *
 * Needs the Node half of playwright-php: `vendor/bin/playwright-install` (no browsers; it attaches to the CDP endpoint MinkBase uses).
 */
abstract class PlaywrightBase extends \CiviEndToEndTestCase {

  use BrowserLoginTrait;

  protected ?PlaywrightClient $playwright = NULL;
  protected ?BrowserContextInterface $context = NULL;
  protected ?PageInterface $page = NULL;

  protected function setUp(): void {
    parent::setUp();

    if (CIVICRM_UF === 'Drupal8' && version_compare(\CRM_Core_Config::singleton()->userSystem->getVersion(), '10', '<')) {
      $this->markTestSkipped('Browser testing is currently unsupported on Civi-Drupal 9');
    }

    $chromeUrl = sprintf('http://%s:%s', getenv('CHROME_HOST') ?: 'localhost', getenv('CHROME_PORT') ?: '9222');
    $this->playwright = PlaywrightFactory::create(new PlaywrightConfig());
    $this->context = $this->playwright->chromium()->connectOverCDP($chromeUrl)->newContext();
    $this->page = $this->context->newPage();
    if (getenv('PLAYWRIGHT_OUTPUT_DIR')) {
      $this->context->startTracing($this->page, ['screenshots' => TRUE, 'snapshots' => TRUE]);
    }
    $GLOBALS['civicrm_url_defaults'][] = ['format' => 'absolute', 'scheme' => 'backend'];
  }

  protected function tearDown(): void {
    try {
      // The trace opens in `npx playwright show-trace` or trace.playwright.dev.
      $outputDir = getenv('PLAYWRIGHT_OUTPUT_DIR');
      if ($outputDir && $this->hasFailedSoFar()) {
        $this->context->stopTracing($this->page, $outputDir . '/' . preg_replace('/\W+/', '-', static::class . '-' . $this->getTestName()) . '.zip');
      }
    }
    finally {
      $this->context?->close();
      $this->playwright?->close();
      array_pop($GLOBALS['civicrm_url_defaults']);
      parent::tearDown();
    }
  }

  protected function login(string $user): void {
    $this->page->goto($this->getLoginUrl($user));
  }

  protected function visit(string $url): void {
    $this->page->goto($url);
  }

  /**
   * Auto-retrying assertion on a locator or page; counts towards PHPUnit's assertions.
   */
  protected function expect(LocatorInterface|PageInterface $subject): ExpectInterface {
    return new ExpectDecorator(new Expect($subject, $this->context->tracing()), $this);
  }

  private function hasFailedSoFar(): bool {
    // PHPUnit 9 / PHPUnit 10+
    return method_exists($this, 'hasFailed') ? $this->hasFailed() : ($this->status()->isFailure() || $this->status()->isError());
  }

  private function getTestName(): string {
    return method_exists($this, 'nameWithDataSet') ? $this->nameWithDataSet() : $this->getName();
  }

}
