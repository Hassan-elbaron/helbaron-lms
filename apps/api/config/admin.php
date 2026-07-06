<?php

return [
    /*
     | When true, the admin panel (/admin) requires the signed-in operator to have confirmed
     | multi-factor authentication (in addition to the active + admin/super_admin role gate in
     | User::canAccessPanel()). Disabled by default so first-time/local setup is not locked out;
     | production should set ADMIN_REQUIRE_MFA=true.
     */
    'require_mfa' => (bool) env('ADMIN_REQUIRE_MFA', false),
];
