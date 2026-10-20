<?php

namespace Civi\Test;

use Civi;

/**
 * Login helper shared by the browser test bases (MinkBase, PlaywrightBase).
 */
trait BrowserLoginTrait {

  /**
   * URL that logs the browser in as $user via an authx JWT.
   */
  protected function getLoginUrl(string $user): string {
    $tok = \Civi::service('crypto.jwt')->encode([
      'exp' => time() + 60 + 999999,
      'sub' => 'cid:' . $this->getUserId($user),
      'scope' => 'authx',
    ]);
    // The use of frontend:// or backend:// is abstract on some env's. For WP, only 'frontend' supports auth
    return (string) Civi::url('frontend://civicrm/authx/login')->addQuery([
      '_authx' => 'Bearer ' . $tok,
      '_authxSes' => 1,
    ]);
  }

  private function getUserId(string $user): int {
    $r = civicrm_api3("Contact", "get", ["id" => "@user:" . $user]);
    foreach ($r['values'] as $id => $value) {
      $cid = $id;
      break;
    }
    if (empty($cid)) {
      throw new \RuntimeException("Failed to identify user ({$user})");
    }
    return $cid;
  }

}
