<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\DMS\Admin\DMSDocumentAddController;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\DMS\DMS;
use SilverStripe\DMS\Tools\DMSShortcodeHandler;
use SilverStripe\DMS\Model\DMSDocument_versions;
use SilverStripe\DMS\Model\DMSDocument;
use SilverStripe\Assets\Filesystem;

$config = Config::modify();

define('DMS_DIR', basename(__DIR__));

if (!file_exists(BASE_PATH . DIRECTORY_SEPARATOR . DMS_DIR)) {
    user_error('DMS directory named incorrectly. Please install the DMS module into a folder named: ' . DMS_DIR);
}

CMSMenu::remove_menu_item(DMSDocumentAddController::class);

ShortcodeParser::get('default')->register(
    $config->get(DMS::class, 'shortcode_handler_key'),
    array(DMSShortcodeHandler::class, 'handle')
);

if ($config->get(DMSDocument_versions::class, 'enable_versions')) {
    //using the same db relations for the versioned documents, as for the actual documents
    $config->set(DMSDocument_versions::class, 'db', $config->get(DMSDocument::class, 'db'));
}

// add dmsassets folder to file system sync exclusion
if (strpos($config->get(DMS::class, 'folder_name'), 'assets/') === 0) {
    $folderName = substr($config->get(DMS::class, 'folder_name'), 7);
    $config->set(Filesystem::class, 'sync_blacklisted_patterns', array("/^" . $folderName . "$/i",));
}
