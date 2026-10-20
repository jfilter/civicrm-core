<?php

use Civi\Api4\Group;

/**
 * E2E Playwright tests for Manage Groups screen; same steps as the Mink ManageGroupsTest.
 *
 * @group e2e
 * @see cv
 */
class E2E_CivicrmAdminUi_ManageGroupsPlaywrightTest extends \Civi\Test\PlaywrightBase {

  use Civi\Test\Api4TestTrait;

  public static function setUpBeforeClass(): void {
    // Example: Install this extension. Don't care about anything else.
    \Civi\Test::e2e()->installMe(__DIR__)->apply();
  }

  public function testManageGroups(): void {
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $gidBasic = $this->createTestRecord('Group')['id'];
    $gidMailing = $this->createTestRecord('Group', ['group_type:name' => ['Mailing List']])['id'];
    $gidInactive = $this->createTestRecord('Group', ['is_active' => FALSE])['id'];
    $this->visit(Civi::url('backend://civicrm/group'));

    $afformTable = $this->page->locator('afsearch-manage-groups table');
    $basicGroupRow = $afformTable->locator("tr[data-entity-id='$gidBasic']");
    $this->expect($basicGroupRow)->toBeVisible();
    $this->expect($basicGroupRow)->not()->toContainText('Mailing List');
    $this->expect($afformTable->locator("tr[data-entity-id='$gidMailing']"))->toContainText('Mailing List');
    $this->expect($afformTable->locator("tr[data-entity-id='$gidInactive']"))->toHaveCount(0);

    // Test some in-line editing.
    $isActiveCell = $basicGroupRow->locator('[data-field-name="is_active"]');
    $isActiveCell->locator('span.crm-editable-enabled')->click();
    $isActiveCell->locator('input[type=radio][value="false"]')->click();
    $isActiveCell->locator('button.btn-success')->click();
    $this->expect($this->page->locator('div.crm-status-box-outer.status-success'))->toBeVisible();

    $basicGroupStatus = Group::get(FALSE)->addWhere('id', '=', $gidBasic)->execute()->single()['is_active'];
    $this->assertEquals(FALSE, $basicGroupStatus);
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

}
