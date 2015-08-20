<?php
namespace TechDivision\NotFound\Routing;

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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Routing\FrontendNodeRoutePartHandler;
use TechDivision\NotFound\Domain\Service\NodeNotFoundService;
use TYPO3\Neos\Routing\Exception;

/**
 * A route part handler for delivering the dimension's 404 site
 */
class NodeNotFoundRoutePartHandler extends FrontendNodeRoutePartHandler
{
    /**
     * @Flow\Inject
     * @var NodeNotFoundService
     */
    protected $nodeNotFoundService;

    /**
     * this overwrites the default matchValue method in the FrontendNodeRoutePartHandler class
     *
     * every coming request will be (internal) redirected to the dimensions 404 site, which can be configured in the
     * settings.yaml
     *
     * @param string $requestPath The request path (without leading "/", relative to the current Site Node)
     *
     * @return boolean true if the $requestPath could be matched, otherwise false
     * @throws \Exception
     * @throws Exception\NoHomepageException if no node could be found on the homepage (empty $requestPath)
     */
    protected function matchValue($requestPath)
    {
        try {
            // only if the module is enabled execute the code.
            // Otherwise return FALSE to prevent any further request processing
            if ($this->nodeNotFoundService->isEnabled()) {
                // strip the dimension segment from url .e.g /en/test.html -> "en"
                $dimensionUriSegment = strstr($requestPath, "/", true);

                // if no slash in in uri exists we use the complete requestPath as "dimension"
                if ($dimensionUriSegment === false) {
                    $dimensionUriSegment = $requestPath;
                }

                // build the new request path with the configured 404 site and the dimension uri segment (perpas invalid, but
                // our aspect takes care about correct handling
                $requestPath = $dimensionUriSegment . "/" . $this->nodeNotFoundService->get404NodeUriForDimensionUriSegment($dimensionUriSegment);

                // execute the original method with the altered request uri
                $node = $this->convertRequestPathToNode($requestPath);

            } else {
                // return false without any execution
                return false;
            }

        } catch (Exception $exception) {
            if ($requestPath === '') {
                throw new Exception\NoHomepageException('Homepage could not be loaded. Probably you haven\'t imported a site yet', 1346950755, $exception);
            }

            $this->systemLogger->log('FrontendNodeRoutePartHandler matchValue(): ' . $exception->getMessage(), LOG_DEBUG);

            return false;
        }

        if ($this->onlyMatchSiteNodes() && $node !== $node->getContext()->getCurrentSiteNode()) {
            return false;
        }

        $this->value = $node->getContextPath();

        return true;
    }
}
