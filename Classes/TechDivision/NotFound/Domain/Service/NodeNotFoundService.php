<?php
namespace TechDivision\NotFound\Domain\Service;

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to version 3 of the GPL license,
 * that is bundled with this package in the file LICENSE, and is
 * available online at http://www.gnu.org/licenses/gpl.txt

 * @author    Philipp Dittert <pd@techdivision.com>
 * @copyright 2015 TechDivision GmbH <info@techdivision.com>
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License, version 3 (GPL-3.0)
 * @link      https://github.com/techdivision/TechDivision.NotFound
 */

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

/**
* Provides methods to serve the correct 404 site for every dimension
*
* @Flow\Scope("singleton")
*/
class NodeNotFoundService
{
    /**
     * the fallback '404' uri if use has nothing configured
     *
     * @var string
     */
    const FALLBACK_NOTFOUND_URI = '404';

    /**
     * @var string
     */
    protected $defaultUriSegment = '';

    /**
     * @var array
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * inject the package settings
     *
     * @param array $settings the package settings
     *
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Returns true if module is enabled by "enabled" flag in settings, else false
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (isset($this->settings['enable']) && $this->settings['enable'] === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the default uri segment
     *
     * @return string
     * @throws \Exception
     */
    public function getDefaultUriSegment()
    {
        if ($this->defaultUriSegment === '') {
            $this->defaultUriSegment = $this->getUriSegmentForDimensions(array(), false);
        }
        return $this->defaultUriSegment;
    }

    /**
     * Reads the individual 404 site path for given dimension from config file or returns the default uri segment
     *
     * @param string $dimensionUriSegment the dimensionUriSegment
     *
     * @return string
     */
    public function get404NodeUriForDimensionUriSegment($dimensionUriSegment)
    {
        // strip existing / at the end of the dimension uri segment string (if exists)
        $dimensionUriSegment = trim(str_replace("/", "", $dimensionUriSegment));

        if (isset($this->settings['dimensions'][$dimensionUriSegment])) {
            return $this->settings['dimensions'][$dimensionUriSegment];
        }
        if (isset($this->settings['defaultUriSegment'])) {
            return $this->settings['defaultUriSegment'];
        }

        return self::FALLBACK_NOTFOUND_URI;
    }

    /**
     * this method was copied from 'FrontendNodeRoutePartHandler' class
     *
     * Find a URI segment in the content dimension presets for the given "language" dimension values
     *
     * This will do a reverse lookup from actual dimension values to a preset and fall back to the default preset if none
     * can be found.
     *
     * @param array   $dimensionsValues      An array of dimensions and their values, indexed by dimension name
     * @param boolean $currentNodeIsSiteNode If the current node is actually the site node
     *
     * @return string
     * @throws \Exception
     */
    protected function getUriSegmentForDimensions(array $dimensionsValues, $currentNodeIsSiteNode)
    {
        $uriSegment = '';
        $allDimensionPresetsAreDefault = true;

        foreach ($this->contentDimensionPresetSource->getAllPresets() as $dimensionName => $dimensionPresets) {
            $preset = null;
            if (isset($dimensionsValues[$dimensionName])) {
                $preset = $this->contentDimensionPresetSource->findPresetByDimensionValues($dimensionName, $dimensionsValues[$dimensionName]);
            }
            $defaultPreset = $this->contentDimensionPresetSource->getDefaultPreset($dimensionName);
            if ($preset === null) {
                $preset = $defaultPreset;
            }
            if ($preset !== $defaultPreset) {
                $allDimensionPresetsAreDefault = false;
            }
            if (!isset($preset['uriSegment'])) {
                throw new \Exception(sprintf('No "uriSegment" configured for content dimension preset "%s" for dimension "%s". Please check the content dimension configuration in Settings.yaml', $preset['identifier'], $dimensionName), 1395824520);
            }
            $uriSegment .= $preset['uriSegment'] . '_';
        }

        if ($allDimensionPresetsAreDefault && $currentNodeIsSiteNode) {
            return '/';
        } else {
            return ltrim(trim($uriSegment, '_') . '/', '/');
        }
    }
}
