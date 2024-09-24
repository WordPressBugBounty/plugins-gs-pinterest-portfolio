<?php
// if direct access than exit the file.
defined('ABSPATH') || exit;

return array(
    'Hooks'           => 'includes/hooks.php',
    'Database'        => 'includes/database.php',
    'Scripts'         => 'includes/scripts.php',
    'Admin'           => 'includes/admin.php',
    'Notices'         => 'includes/notices.php',
    'Migration'       => 'includes/migration.php',
    'Helpers'         => 'includes/helpers.php',
    'Shortcode'      => 'includes/shortcode.php',

    'Integrations'    => 'includes/integrations/integrations.php',

    'FollowPin'       => 'includes/widgets/followpin.php',
    'PinBoard'        => 'includes/widgets/pinboard.php',
    'PinProfile'      => 'includes/widgets/pinprofile.php',
    'SinglePin'       => 'includes/widgets/singlepin.php',
    'Widgets'         => 'includes/widgets/widgets.php',
    'Pinterest'       => 'includes/pinterest.php',
    'Template_Loader'  => 'includes/template-loader.php',

    // builders file.
    'Builder'        => 'includes/shortcode-builder/builder.php'
);
