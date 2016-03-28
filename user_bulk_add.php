<?php
# Alert the user that this is not a valid access point to MediaWiki if they
# try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
        echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/user_bulk_add/user_bulk_add.php" );
EOT;
        exit( 1 );
}
 
$wgExtensionCredits[ 'specialpage' ][] = array(
        'path' => __FILE__,
        'name' => 'User_Bulk_Add',
        'author' => 'Patrick West',
        'url' => 'http://cedarweb.hao.ucar.edu/cedaradmin/index.php/Extensions:user_bulk_add',
        'descriptionmsg' => 'user_bulk_add-desc',
        'version' => '1.0.1',
);
 
# Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgAutoloadClasses[ 'User_Bulk_Add' ] = __DIR__ .  '/User_Bulk_Add_body.php';

# Location of a messages file (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles[ 'User_Bulk_Add' ] = __DIR__ .  '/User_Bulk_Add.i18n.php';

# Tell MediaWiki about the new special page and its class name
$wgSpecialPages[ 'User_Bulk_Add' ] = 'User_Bulk_Add';

$wgGroupPermissions['sysop']['cedar_admin'] = true;
?>
