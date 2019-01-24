<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\MozWidgetByAmperage\Widgets;

use Piwik\Widget\Widget;
use Piwik\Widget\WidgetConfig;
use Piwik\View;
use Piwik\Common;
use Piwik\Site;
use Piwik\Url;
use Piwik\UrlHelper;

/**
 * This class allows you to add your own widget to the Piwik platform. In case you want to remove widgets from another
 * plugin please have a look at the "configureWidgetsList()" method.
 * To configure a widget simply call the corresponding methods as described in the API-Reference:
 * http://developer.piwik.org/api-reference/Piwik/Plugin\Widget
 */
class GetMozInfo extends Widget{
    public static function configure(WidgetConfig $config){
        /**
         * Set the category the widget belongs to. You can reuse any existing widget category or define
         * your own category.
         */
        $config->setCategoryId('SEO');

        /**
         * Set the subcategory the widget belongs to. If a subcategory is set, the widget will be shown in the UI.
         */
        // $config->setSubcategoryId('General_Overview');

        /**
         * Set the name of the widget belongs to.
         */
        $config->setName('MozWidgetByAmperage_MozCom');

        /**
         * Set the order of the widget. The lower the number, the earlier the widget will be listed within a category.
         */
        $config->setOrder(50);

        /**
         * Optionally set URL parameters that will be used when this widget is requested.
         * $config->setParameters(array('myparam' => 'myvalue'));
         */

        /**
         * Define whether a widget is enabled or not. For instance some widgets might not be available to every user or
         * might depend on a setting (such as Ecommerce) of a site. In such a case you can perform any checks and then
         * set `true` or `false`. If your widget is only available to users having super user access you can do the
         * following:
         *
         * $config->setIsEnabled(\Piwik\Piwik::hasUserSuperUserAccess());
         * or
         * if (!\Piwik\Piwik::hasUserSuperUserAccess())
         *     $config->disable();
         */
    }

    public function amp_get_contents($url){
		if(ini_get('allow_url_fopen')){
			return file_get_contents($url);
		}else{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, false);
			$output = curl_exec($curl);
			curl_close($curl);
			return $output;
		}
    }

    /**
     * This method renders the widget. It's on you how to generate the content of the widget.
     * As long as you return a string everything is fine. You can use for instance a "Piwik\View" to render a
     * twig template. In such a case don't forget to create a twig template (eg. myViewTemplate.twig) in the
     * "templates" directory of your plugin.
     *
     * @return string
     */
    public function render(){
        try {

			$debug_help = false; // Toggle whether the Moz API helper text should be shown
			$debug_siteurl = false; // Toggle the site URL being checked for at Moz
			$debug_api = false; // Toggle whether the basic Moz API Status output should be shown
			$debug_authentication = false; // Toggle whether the Moz API authentication testing output should be shown
			$debug_snapshots = false; // Toggle whether the Moz API snapshots results should be shown

			$output = '<div class="widget-body">';

			$api_key = '';
			$secret = '';

			// Get Piwik user's settings for the API Key & Secret they've set there (allowing different Piwik users to have different Moz authentication)
			$settings = new \Piwik\Plugins\MozWidgetByAmperage\UserSettings();
			$api_key = $settings->mozAPIKey->getValue();
			$secret = $settings->mozSecretKey->getValue();

			if($api_key == '' || $secret == ''){
				$output.= '<p>You first need to configure the Moz.com API Keys in your <a href="index.php?module=UsersManager&action=userSettings#MozWidgetByAmperage">user settings</a>.</p>';
			}else{ // API Keys have been provided

				// Get the site's URL from Piwik
				$idSite = Common::getRequestVar('idSite');
				$site = new Site($idSite);
				$siteurl = urldecode(Common::getRequestVar('url', '', 'string'));
				if (!empty($siteurl) && strpos($siteurl, 'http://') !== 0 && strpos($siteurl, 'https://') !== 0) {
					$siteurl = 'http://' . $url;
				}
				if (empty($siteurlurl) || !UrlHelper::isLookLikeUrl($siteurl)) {
					$siteurl = $site->getMainUrl();
				}
				$siteurl = str_replace('https://','',str_replace('http://','',$siteurl)); // Strip both http:// and https:// from the URL being checked

				if($debug_help){
					$output.= '<p>This is where the Moz API data should be shown (documented at <a href="https://moz.com/products/api" target="_blank">https://moz.com/products/api</a>)</p>';
				}

				if($debug_siteurl){
					$output.= '<p><strong>Site URL:</strong> <code>'.$siteurl.'</code></p>';
				}

				if($debug_api){
					$api_status = $this->amp_get_contents('https://lsapi.seomoz.com/linkscape/url-metrics/'); // Get the status of the Moz API (no authentication/signing needed)
					$output.= '<p><strong>Moz API Status:</strong> <code>'.$api_status.'</code></p>';
				}

				if($debug_authentication){
					$authentication_request = "test=value&api_key=".$api_key;
					$content = str_replace('=','',$authentication_request); // Concatenate the name and value into a string (required by Moz API)
					$content = explode('&',$content); // Make it so each request parameter is an item in the array so we can sort them alphabetically
					sort($content); // Sort the array alphabetically (required by Moz API)
					$content = implode('',$content); // Turn array into string
					$authentication_request_signed = hash_hmac("sha256", $content, $secret); // The result for the required Request Signing
					$authentication = $this->amp_get_contents('https://app.crazyegg.com/api/v2/authenticate.json?'.$authentication_request.'&signed='.$authentication_request_signed); // Test authentication with keys & singing
					$output.= '<p><strong>Moz API Authentication:</strong> <code>'.$authentication.'</code></p>';
				}

				$snapshots_request = "api_key=".$api_key;
				$content = str_replace('=','',$snapshots_request); // Concatenate the name and value into a string (required by Moz API)
				$content = explode('&',$content); // Make it so each request parameter is an item in the array so we can sort them alphabetically
				sort($content); // Sort the array alphabetically (required by Moz API)
				$content = implode('',$content); // Turn array into string
				$snapshots_request_signed = hash_hmac("sha256", $content, $secret); // The result for the required Request Signing
				$snapshots = $this->amp_get_contents('https://app.crazyegg.com/api/v2/snapshots.json?'.$snapshots_request.'&signed='.$snapshots_request_signed); // Get the list of snapshots in this account

				if($debug_snapshots){
					$output.= '<p><strong>Moz Snapshots:</strong> <code>'.$snapshots.'</code></p>';
				}

				$snapshots = json_decode($snapshots);
				$snapshotCount = 0;
				foreach($snapshots as $snapshot){
					if (strpos($snapshot->source_url, $siteurl) !== false) { // Only snapshot info if it's for the current site's URL
						$output.= '<a href="https://app.crazyegg.com/v2/snapshots/'.$snapshot->id.'" target="_blank" class="crazyegg-snapshot"><h4 class="name">'.$snapshot->name.'</h4><img src="'.$snapshot->thumbnail_url.'" class="thumbnail" alt="Thumbnail" width="154" height="102" /><span class="total_visits">'.number_format($snapshot->total_visits).'</span><span class="total_clicks">'.number_format($snapshot->total_clicks).'</span><span class="status">'.ucfirst($snapshot->status).'</span><span class="heatmap-preview"><img src="'.$snapshot->heatmap_url.'" class="heatmap" alt="Heatmap" width="100%" /><img src="'.$snapshot->screenshot_url.'" class="screenshot" alt="Screenshot" width="100%" /></span></a><!-- .crazyegg-snapshot -->';
						$snapshotCount++;
					}
				}

				if($snapshotCount<1){
					$output.= '<p>No <a href="https://www.moz.com" target="_blank">Moz.com</a> info is available for this website\'s URL ('.$siteurl.') under the account the Moz API keys are for, currently.</p>';
				}

			}

			$output.= '</div>';
			return $output;

        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    /**
     * @param \Exception $e
     * @return string
     */
    private function error($e)
    {
        return '<div class="pk-emptyDataTable">'
             . Piwik::translate('General_ErrorRequest', array('', ''))
             . ' - ' . $e->getMessage() . '</div>';
    }

}