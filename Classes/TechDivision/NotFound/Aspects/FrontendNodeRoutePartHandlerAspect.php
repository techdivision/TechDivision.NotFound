<?php
namespace TechDivision\NotFound\Aspects;

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
use TYPO3\Neos\Routing\Exception\InvalidRequestPathException;
use TYPO3\Neos\Routing\Exception\NoSuchDimensionValueException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Neos\Routing\Exception\NoSuchNodeException;
use TYPO3\Neos\Routing\Exception;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use TechDivision\NotFound\Domain\Service\NodeNotFoundService;

/**
 * provides 404 handling if no node was found for request uri
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class FrontendNodeRoutePartHandlerAspect
{
    /**
     * @Flow\Inject
     * @var NodeNotFoundService
     */
    protected $nodeNotFoundService;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @Flow\Around("method(TYPO3\Neos\Routing\FrontendNodeRoutePartHandler->convertRequestPathToNode())")
     *
     * @param \TYPO3\FLOW\AOP\JoinPointInterface $joinPoint the join point
     *
     * @return mixed
     */
    public function aroundConvertRequestPathToNodeAspect($joinPoint)
    {
        if ($this->nodeNotFoundService->isEnabled()) {
            /** @var NodeInterface $node */
            $requestPath = $joinPoint->getMethodArgument('requestPath');

            try {
                return $joinPoint->getAdviceChain()->proceed($joinPoint);
            } catch (InvalidRequestPathException $e) {
                $defaultUriSegment = $this->nodeNotFoundService->getDefaultUriSegment();
                $requestPath = $defaultUriSegment . $this->nodeNotFoundService->get404NodeUriForDimensionUriSegment($defaultUriSegment);

                $joinPoint->setMethodArgument("requestPath", $requestPath);
                return $joinPoint->getAdviceChain()->proceed($joinPoint);
            } catch (NoSuchDimensionValueException $e) {
                $defaultUriSegment = $this->nodeNotFoundService->getDefaultUriSegment();
                $requestPath = $defaultUriSegment . $this->nodeNotFoundService->get404NodeUriForDimensionUriSegment($defaultUriSegment);

                $joinPoint->setMethodArgument("requestPath", $requestPath);
                return $joinPoint->getAdviceChain()->proceed($joinPoint);

            } catch (NoSuchNodeException $e) {
                $dimensionUriSegment = strstr($requestPath, "/", true);

                if (count($this->contentDimensionPresetSource->getAllPresets()) > 0) {
                    $requestPath = $dimensionUriSegment . "/" . $this->nodeNotFoundService->get404NodeUriForDimensionUriSegment($dimensionUriSegment);
                } else {
                    $requestPath = $this->nodeNotFoundService->get404NodeUriForDimensionUriSegment('');
                }

                $joinPoint->setMethodArgument("requestPath", $requestPath);
                return $joinPoint->getAdviceChain()->proceed($joinPoint);
            }
        } else {
            // execute the original code
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }
    }
}
