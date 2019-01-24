<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\MozWidgetByAmperage;

use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;

/**
 * Defines Settings for MozWidgetByAmperage.
 *
 * Usage like this:
 * $settings = new UserSettings();
 * $settings->autoRefresh->getValue();
 * $settings->color->getValue();
 */
class UserSettings extends \Piwik\Settings\Plugin\UserSettings
{
    /** @var Setting */
    public $mozAPIKey;

    /** @var Setting */
    public $mozSecretKey;

    protected function init()
    {
        $this->mozAPIKey = $this->createMozAPIKey();
        $this->mozSecretKey = $this->createMozSecretKey();
    }

    private function createMozAPIKey()
    {
        return $this->makeSetting('mozAPIKey', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Access ID (API Key)';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->uiControlAttributes = array('size' => 3);
            $field->description = 'The `Access ID` Moz provides on https://moz.com/products/mozscape/access';
        });
    }

    private function createMozSecretKey()
    {
        return $this->makeSetting('mozSecretKey', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Secret Key';
            $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
            $field->uiControlAttributes = array('size' => 3);
            $field->description = 'The `Secret Key` Moz provides on https://moz.com/products/mozscape/access';
        });
    }

}
