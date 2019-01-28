<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MozWidgetByAmperage;

use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;

/**
 * Defines Settings for MozWidgetByAmperage.
 *
 * Usage like this:
 * // require Piwik\Plugin\SettingsProvider via Dependency Injection eg in constructor of your class
 * $settings = $settingsProvider->getMeasurableSettings('MozWidgetByAmperage', $idSite);
 * $settings->appId->getValue();
 * $settings->contactEmails->getValue();
 */
class MeasurableSettings extends \Piwik\Settings\Measurable\MeasurableSettings
{

    /** @var Setting */
    public $mozCustomReportURLSetting;

    protected function init()
    {
        $this->mozCustomReportURLSetting = $this->makeMozCustomReportURLSetting();
    }

    private function makeMozCustomReportURLSetting()
    {
        return $this->makeSetting('mozCustomReportURL', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Moz.com Custom Report Preview URL';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->uiControlAttributes = array('size' => 3);
            $field->description = 'The Moz.com URL for previewing your preferred custom report. For example, `https://analytics.moz.com/pro/analytics/reports/12345/12345/preview/12345` is obtained by going to Moz Pro Campaign => Custom Reports => Preview & Download (under Actions for the preferred custom report.)';
        });
    }

}
