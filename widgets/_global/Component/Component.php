<?php

/**
 * File for class Component.
 *
 * @author    Proximify Inc <support@proximify.com>
 * @copyright Copyright (c) 2020, Proximify Inc
 * @license   https://opensource.org/licenses/GPL-3.0 GNU GPL 3.0
 * @version   1.0.0 GLOT Run Time Library
 */

use Proximify\Glot as Glot;

use Proximify\Glot\Plugin\Core\AssetManager;
use Proximify\Glot\Plugin\Core\PageReader;
use Proximify\Glot\Plugin\Core\PagePreRenderer;

/**
 * A global class to not break the Widget class in the server
 * that inherits from a global Component.
 */
abstract class Component extends Glot\Widget
{
    /** @var array list parameters that should trigger the page refresh when the parameter change */
    private $refreshParams;

    /**
     * Get settings value of the property $key.
     *
     * @param string|null $key
     * @return void
     */
    final public function getSettings(?string $key = null): array
    {
        return $key ? $this->options[$key] ?? [] : $this->options;
    }

    /**
     * Return private property refreshParams
     *
     * @return array
     */
    final public function getRefreshParams(): ?array
    {
        return $this->refreshParams;
    }

    /**
     * Get the subset of the values in $params that correspond
     * to the given keys.
     * @param array $keys A numeric array of keys in $params.
     * @param array $params An associative array with key-value pairs.
     * @return array The key-value pairs in $params with keys in $keys.
     */
    public static function arraySubset($keys, $params)
    {
        $out = [];

        foreach ($keys as $k) {
            if (isset($params[$k])) {
                $out[$k] = $params[$k];
            }
        }

        return $out;
    }

    /**
     * Get head widgets.
     *
     * @return array|null
     */
    final public function getHeadWidgets(): ?array
    {
        return $this->renderer->getPageLevelWidgets();
    }

    /**
     * Get page settings
     *
     * @return array|null
     */
    final public function getPageSettings(): ?array
    {
        return $this->renderer->getPageMap();
    }

    final public function getPageNameFromURL($pageName): ?string
    {
        return $this->renderer->getPageInfo()->getPageNameFromURL($pageName);
    }

    /**
     * Get the home page name
     *
     * @return string
     */
    final public function getHomePage(): string
    {
        return $this->renderer->getPageInfo()->getHomePage();
    }

    /**
     * Check if it's in the exporting mode
     *
     * @return boolean
     */
    final public function isStaticRendering(): bool
    {
        return $this->renderer->isStaticRendering();
    }

    /**
     * Get the domain name
     */
    final public function getWebsiteDomainName(): ?string
    {
        return $this->renderer->domain;
    }

    /**
     * Get language list of the website
     *
     * @return array
     */
    final public function getActiveLanguages(): array
    {
        $langSettings = $this->getLangSettings();

        return $langSettings['languages'] ?? [];
    }

    /**
     * This function localize the $data
     *
     * @param string|array $data
     * @param string $lang
     * @return string
     */
    final public function localize($data, ?string $lang = null): string
    {
        return $this->renderer->localize($data, $lang);
    }

    /**
     * This function analyze the url and return the url type
     * asset, link to internal pages or link to external pages
     *
     * @param string $href
     * @return string
     */
    final public function getLinkType(string $href): string
    {
        return $this->renderer->getLinkType($href);
    }

    /**
     * Takes an HRef parameters and decomposes it into page name, language,
     * parameters and hash part. It then creates an href string with this parts.
     *
     * @param array|string $href
     * @return string
     */
    final public function parseHRefParam(string $href)
    {
        return $this->renderer->getLocalizer()->parseHRefParam($href);
    }

    /**
     * This the property value in the active language
     *
     * @param string $name the property name
     * @param string $default
     * @return string
     */
    final public function getText(string $name, $default = ''): string
    {
        $widget = get_called_class();

        $wp = $this->loadWidgetPackage($widget);

        $filename = $wp->getRootFolder() . $wp->getMarkupDict();

        $contents = $this->renderer->readJSONFile($filename);

        return $this->localizeParamValue($contents, $name, $default);
    }

    /**
     * Get an AssetManager object in order to get asset's information
     *
     * @return AssetManager
     */
    final public function getAssets(): AssetManager
    {
        return $this->renderer->getAssets();
    }

    /**
     * This function returns the url of the given asset.
     * Try to find the asset from the website first.
     * If the asset can't be found from the website, fetch the asset from the
     * widget package.
     *
     * @param string $targetResource the name of asset
     * @param string $widget the name of widget
     * @param boolean $widgetAdvance Fetch asset from widget package first
     * @return string
     */
    final public function makeAssetUrl(
        string $asset,
        ?string $widget = '',
        bool $widgetAdvance = false
    ): string {
        if ($widgetAdvance) {
            return $this->getAssets()->makeWidgetAssetUrl($asset, get_called_class());
        }

        return $this->getAssets()->makeAssetUrl(
            $asset,
            get_called_class()
        );
    }

    /**
     * @deprecated Widgets should get the assetInfo and then
     * call the functions that they need from it.
     */
    final public function getImageSize($asset)
    {
        return $this->getAssets()->getImageSize($asset);
    }

    /**
     * @deprecated Widgets should get the assetInfo and then
     * call the functions that they need from it.
     */
    final public function getAssetPath($asset, $folder = 'assets', $widgetAsset = false)
    {
        if ($widgetAsset) {
            return $this->getAssets()->getWidgetAssetPath(
                $asset,
                get_called_class()
            );
        }

        return $this->getAssets()->getAssetPath($asset, get_called_class());
    }

    /**
     * @deprecated Widgets should get the assetInfo and then
     * call the functions that they need from it.
     */
    final public function getFileContents(
        string $asset,
        string $folder = 'assets',
        bool $widgetAsset = false
    ) {
        if ($widgetAsset) {
            return $this->getAssets()->getWidgetAssetContents($asset, get_called_class());
        }

        return $this->getAssets()->getAssetContents($asset, get_called_class());
    }

    final public function loadAssetJSON($name)
    {
        return $this->getFileContents($name, true);
    }

    /**
     * @deprecated Widgets should get the assetInfo and then
     * call the functions that they need from it.
     */
    final public function loadAssetFile($name)
    {
        return $this->getFileContents($name, true);
    }

    /**
     * Get a PageReader in order to get website widgets or pages structure
     *
     * @return PageReader
     */
    final public function getPage(): PageReader
    {
        return $this->renderer->getPageInfo();
    }

    /**
     * @deprecated Widgets should get the pageInfo and then
     * call the functions that they need from it.
     */
    final public function getPageInfo($pageName = '', $isHome = false)
    {
        return $this->getPage()->getPageInfo($pageName, $isHome);
    }

    /**
     * @deprecated Widgets should get the pageInfo and then
     * call the functions that they need from it.
     */
    final public function getAllPageNames()
    {
        return $this->getPage()->getAllPageNames();
    }

    /**
     * @deprecated Widgets should get the pageInfo and then
     * call the functions that they need from it.
     */
    final public function getWebsitePages(?string $folder = '', string $exclude = ''): ?array
    {
        return $this->getPage()->getWebsitePages($folder, $exclude, $this);
    }

    /**
     * @deprecated Widgets should get the pageInfo and then
     * call the functions that they need from it.
     */
    final public function getWebsiteWidgets(?array $params): ?array
    {
        return $this->getPage()->getWebsiteWidgets($params);
    }

    /**
     * @deprecated Widgets should get the pageInfo and then
     * call the functions that they need from it.
     */
    final public function getNextFolder(?string $pageName = ''): array
    {
        return $this->getPage()->getNextFolder($pageName, $this);
    }

    /**
     * @deprecated Widgets should get the pageInfo and then
     * call the functions that they need from it.
     */
    final public function getNextPage(string $pageName = ''): array
    {
        return $this->getPage()->getNextPage($pageName);
    }

    /**
     * @deprecated Widgets should get the pageInfo and then
     * call the functions that they need from it.
     */
    final public function getPrevPage(string $pageName = ''): array
    {
        return $this->getPage()->getPrevPage($pageName);
    }

    /**
     * Get a PagePreRenderer in order to interact with prerenderings
     * of website pages.
     *
     * @return PagePreRenderer
     */
    final public function getPreRenderer(): PagePreRenderer
    {
        return $this->renderer->getPreRenderer();
    }

    /**
     * @deprecated Widgets should get the preRenderer and then
     * call the functions that they need from it.
     */
    final public function getElementFromPageById($id, $page = '')
    {
        return $this->getPreRenderer()->getElementFromPageById($id, $page);
    }

    /**
     * @deprecated Widgets should get the preRenderer and then
     * call the functions that they need from it.
     */
    final public function getElementsFromPageByTag($tag, $page = '')
    {
        return $this->getPreRenderer()->getElementsFromPageByTag($tag, $page);
    }

    /**
     * @deprecated Widgets should get the preRenderer and then
     * call the functions that they need from it.
     */
    final public function getElementsFromPageByClass($class, $page = '')
    {
        return $this->getPreRenderer()->getElementsFromPageByClass($class, $page);
    }

    final public function getClientBreakpoints()
    {
        return $this->renderer->getBreakpoints();
    }

    ////////////////////////////////////////////////////////////////////////////
    // Public Functions

    public function websiteName()
    {
        return $this->renderer->websiteName();
    }

    public function getWidgetSettings()
    {
        return $this->renderer->getWidgetSettings(get_called_class());
    }

    public function pageName()
    {
        return $this->renderer->pageName();
    }

    public function getLanguage()
    {
        return $this->renderer->getLanguage();
    }

    public function getDraftName()
    {
        return method_exists($this->renderer, 'getDraftName') ?
            $this->renderer->getDraftName() : '';
    }

    public function getLangSettings()
    {
        return $this->renderer->getLangSettings();
    }

    public function getAvailableLanguages()
    {
        return $this->renderer->getAvailableLanguages();
    }

    public function getMainLanguage()
    {
        return $this->renderer->getMainLanguage();
    }

    /**
     * Temp solution.
     * @deprecated
     *
     * @param [type] $className
     * @return void
     */
    public static function readWidgetPackage(?string $class = null)
    {
        if (!$class) {
            $class = get_called_class();
        }

        return Glot\Renderer::getMainRenderer()->loadWidgetPackage($class);
    }

    /**
     * Encode an array as a JavaScript Object Literal. Note that that this is not
     * the same as JSON. In particular, this function wraps keys and text
     * values with single quotes.
     */
    public static function jsolEncode($params)
    {
        // Might also need JSON_UNESCAPED_SLASHES
        $options = JSON_HEX_QUOT | JSON_HEX_APOS | JSON_NUMERIC_CHECK;

        return str_replace('"', "'", json_encode($params, $options));
    }

    /**
     * Make html markup
     *
     * @param array|string $elements
     * @return void
     */
    final public function makeMarkup($elements)
    {
        return $this->renderer->renderMarkup($elements);
    }

    /**
     * Return private property hasJSCode
     * Declare to public because Website Renderer run this function
     * @return bool
     */
    final public function getHasJSCode(): ?bool
    {
        return $this->site()->code->hasJSCode(get_called_class(), $this->holderId());
    }

    /**
     * This methods is called Renderer::renderObjectMarkup() once for
     * each widget object. Widget can override the method to submit their
     * own getOnReadyCode(). Foe example, the Widget class implements an
     * automatic detection of static code from JS classes.
     *
     * @return void
     */
    public function getOnReadyCode()
    {
    }

    /**
     * Enable search in the widget
     * @deprecated Widgets should get the search plugin and then
     * call the functions that they need from it.
     */
    final protected function enableWebsiteSearch(?array $options = []): string
    {
        return $this->site()->finder->enableWebsiteSearch($options);
    }

    /**
     * Enable the image optimization
     * Return the original asset in developing mode or for those
     * images we cannot optimize.
     *
     * @param string $asset
     * @return string|array
     */
    final protected function enableImageOptimization(string $asset)
    {
        return $this->isStaticRendering() ?
            $this->getAssets()->getImgSrcset($asset) : $asset;
    }

    /**
     * Sets the lists of parameters that require a page refresh when changed.
     * The list is considered only if the widget is using javaScript code.
     *
     * @param array $params List of parameter names.
     */
    final protected function setRefreshParams(array $params)
    {
        $this->refreshParams = $params;
    }

    /**
     * Add machine-readable information (metadata) about the document, like
     * its title, meta elements, custom icons, Open Graph data (http://ogp.me/).
     * 
     * @deprecated Use $this->site()->code->addHeadHtml($html); 
     *
     * @param string|array $html It can be a string or an array of strings.
     * @return void
     */
    final protected function addPageMetadata($html): void
    {
        $this->site()->code->addHeadHtml($html);
    }

    /**
     * @todo Extensions will be Glot plugins of type Tool.
     * The extension must be required by the widget.
     *
     * @param [type] $text
     * @return void
     */
    final protected function convertMarkdown($text)
    {
        return $this->require('Tool\Parsedown')->text($text);
    }

    private function localizeParamValue($params, $key, $default = null)
    {
        return (isset($params[$key])) ? $this->localize($params[$key]) : $default;
    }

    private function loadWidgetPackage(?string $class = null)
    {
        if (!$class) {
            $class = get_called_class();
        }

        return $this->renderer->loadWidgetPackage($class);
    }
}
