<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\MozWidgetByAmperage;

/**
 *
 */
class MozWidgetByAmperage extends \Piwik\Plugin
{
    /**
     * @see Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles'
        );
    }

    public function getStylesheetFiles(&$files)
    {
        $files[] = "plugins/MozWidgetByAmperage/stylesheets/moz.less";
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "plugins/MozWidgetByAmperage/javascripts/moz.js";
    }
}