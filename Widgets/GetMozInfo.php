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
			$debug_api = false; // Toggle whether the raw Moz API results should be shown

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

				// Set your expires times for several minutes into the future.
				// An expires time excessively far in the future will not be honored by the Mozscape API.
				$expires = time() + 300;
				// Put each parameter on a new line.
				$stringToSign = $api_key."\n".$expires;
				// Get the "raw" or binary output of the hmac hash.
				$binarySignature = hash_hmac('sha1', $stringToSign, $secret, true);
				// Base64-encode it and then url-encode that.
				$urlSafeSignature = urlencode(base64_encode($binarySignature));
				// Add up all the bit flags you want returned.
				// Learn more here: https://moz.com/help/guides/moz-api/mozscape/api-reference/url-metrics
				$cols = "141421025689597";
				// Put it all together and you get your request URL.
				// This example uses the Mozscape URL Metrics API.
				$requestUrl = "https://lsapi.seomoz.com/linkscape/url-metrics/".urlencode($siteurl)."?Cols=".$cols."&AccessID=".$api_key."&Expires=".$expires."&Signature=".$urlSafeSignature;
				$content = $this->amp_get_contents($requestUrl);
				$content = json_decode($content,true);
				if($debug_api){
					echo '<pre>';print_r($content);echo '</pre>';
				}

				$output.= '<dl class="moz-info">';
				if(isset($content['ut']) && $content['ut'] !== ''){
					$output.= '<dt>Homepage Title</dt><dd>'.$content['ut'].'</dd>';
				}
				if(isset($content['uu'])){
					$output.= '<div class="more-info"><dt>Canonical URL</dt><dd>'.$content['uu'].'</dd></div>';
				}
				if(isset($content['ufq'])){
					$output.= '<div class="more-info"><dt>Subdomain</dt><dd>'.$content['ufq'].'</dd></div>';
				}
				if(isset($content['upl'])){
					$output.= '<div class="more-info"><dt>Root Domain</dt><dd>'.$content['upl'].'</dd></div>';
				}
				if(isset($content['upa'])){
					$output.= '<dt>Homepage Authority</dt><dd>'.$content['upa'].'</dd>';
				}
				if(isset($content['pda'])){
					$output.= '<dt>Domain Authority</dt><dd>'.$content['pda'].'</dd>';
				}
				if(isset($content['ueid'])){
					$output.= '<dt>Homepage External Equity Links</dt><dd>'.$content['ueid'].'</dd>';
				}
				if(isset($content['feid'])){
					$output.= '<div class="more-info"><dt>Subdomain External Links</dt><dd>'.$content['feid'].'</dd></div>';
				}
				if(isset($content['peid'])){
					$output.= '<dt>Root Domain External Links</dt><dd>'.$content['peid'].'</dd>';
				}
				if(isset($content['ujid'])){
					$output.= '<div class="more-info"><dt>Equity Links</dt><dd>'.$content['ujid'].'</dd></div>';
				}
				if(isset($content['uifq'])){
					$output.= '<div class="more-info"><dt>Subdomains Linking</dt><dd>'.$content['uifq'].'</dd></div>';
				}
				if(isset($content['uipl'])){
					$output.= '<dt>Root Domains Linking</dt><dd>'.$content['uipl'].'</dd>';
				}
				if(isset($content['fid'])){
					$output.= '<div class="more-info"><dt>Subdomain, Subdomains Linking</dt><dd>'.$content['fid'].'</dd></div>';
				}
				if(isset($content['pid'])){
					$output.= '<div class="more-info"><dt>Root Domain, Root Domains Linking</dt><dd>'.$content['pid'].'</dd></div>';
				}
				if(isset($content['umrp'])){
					$output.= '<dt>MozRank: Homepage</dt><dd>'.round($content['umrp'],1).'<span> / 10</span></dd>';
				}
				if(isset($content['fmrp'])){
					$output.= '<div class="more-info"><dt>MozRank: Subdomain</dt><dd>'.round($content['fmrp'],1).'<span> / 10</span></dd></div>';
				}
				if(isset($content['pmrp'])){
					$output.= '<dt>MozRank: Root Domain</dt><dd>'.round($content['pmrp'],1).'<span> / 10</span></dd>';
				}
				if(isset($content['utrp'])){
					$output.= '<dt>MozTrust: Homepage</dt><dd>'.round($content['utrp'],1).'<span> / 10</span></dd>';
				}
				if(isset($content['ftrp'])){
					$output.= '<div class="more-info"><dt>MozTrust: Subdomain</dt><dd>'.round($content['ftrp'],1).'<span> / 10</span></dd></div>';
				}
				if(isset($content['ptrp'])){
					$output.= '<dt>MozTrust: Root Domain</dt><dd>'.round($content['ptrp'],1).'<span> / 10</span></dd>';
				}
				if(isset($content['uemrp'])){
					$output.= '<dt>MozRank: Homepage External Equity</dt><dd>'.round($content['uemrp'],1).'<span> / 10</span></dd>';
				}
				if(isset($content['fejp'])){
					$output.= '<div class="more-info"><dt>MozRank: Subdomain, External Equity</dt><dd>'.round($content['fejp'],1).'<span> / 10</span></dd></div>';
				}
				if(isset($content['pejp'])){
					$output.= '<dt>MozRank: Root Domain, External Equity</dt><dd>'.round($content['pejp'],1).'<span> / 10</span></dd>';
				}
				if(isset($content['pjp'])){
					$output.= '<div class="more-info"><dt>MozRank: Subdomain Combined</dt><dd>'.round($content['pjp'],1).'<span> / 10</span></dd></div>';
				}
				if(isset($content['fjp'])){
					$output.= '<div class="more-info"><dt>MozRank: Root Domain Combined</dt><dd>'.round($content['fjp'],1).'<span> / 10</span></dd></div>';
				}
				if(isset($content['fspsc'])){
					$output.= '<dt>Subdomain Spam Score</dt><dd>'.$content['fspsc'].'</dd>';
				}
				if(isset($content['us'])){
					$output.= '<div class="more-info"><dt>HTTP Status Code</dt><dd>'.$content['us'].'</dd></div>';
				}
				if(isset($content['uid'])){
					$output.= '<dt>Links to Homepage</dt><dd>'.$content['uid'].'</dd>';
				}
				if(isset($content['fuid'])){
					$output.= '<div class="more-info"><dt>Links to Subdomain</dt><dd>'.$content['fuid'].'</dd></div>';
				}
				if(isset($content['puid'])){
					$output.= '<dt>Links to Root Domain</dt><dd>'.$content['puid'].'</dd>';
				}
				if(isset($content['fipl'])){
					$output.= '<div class="more-info"><dt>Root Domains Linking to Subdomain</dt><dd>'.$content['fipl'].'</dd></div>';
				}
				if(isset($content['ued'])){
					$output.= '<dt>External links</dt><dd>'.$content['ued'].'</dd>';
				}
				if(isset($content['fed'])){
					$output.= '<div class="more-info"><dt>External links to subdomain</dt><dd>'.$content['fed'].'</dd></div>';
				}
				if(isset($content['ped'])){
					$output.= '<dt>External links to root domain</dt><dd>'.$content['ped'].'</dd>';
				}
				if(isset($content['pib'])){
					$output.= '<div class="more-info"><dt>Linking C Blocks</dt><dd>'.$content['pib'].'</dd></div>';
				}
				if(isset($content['ulc'])){
					$output.= '<div class="more-info"><dt>Time last crawled</dt><dd>'.$content['ulc'].'</dd></div>';
				}
				$output.= '</dl>';
				$output.= '<p><a href="#" id="moz-info-show-more-info" class="more">Show More Info</a></p>';

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