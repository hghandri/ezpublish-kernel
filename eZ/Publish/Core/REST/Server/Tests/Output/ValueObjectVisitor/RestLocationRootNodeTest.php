<?php
/**
 * File containing a test class
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\REST\Server\Tests\Output\ValueObjectVisitor;

use eZ\Publish\Core\REST\Common\Tests\Output\ValueObjectVisitorBaseTest;
use eZ\Publish\Core\REST\Server\Output\ValueObjectVisitor;
use eZ\Publish\Core\REST\Server\Values\RestLocation;
use eZ\Publish\Core\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\Core\REST\Common;

class RestLocationRootNodeTest extends RestLocationTest
{
    /**
     * Test the Location visitor
     *
     * @return string
     */
    public function testVisit()
    {
        $visitor   = $this->getVisitor();
        $generator = $this->getGenerator();

        $generator->startDocument( null );

        $location = new RestLocation(
            new Location(
                array(
                    'id' => 1,
                    'priority' => 0,
                    'hidden' => false,
                    'invisible' => true,
                    'remoteId' => 'remote-id',
                    'parentLocationId' => null,
                    'pathString' => '/1',
                    'depth' => 3,
                    'sortField' => Location::SORT_FIELD_PATH,
                    'sortOrder' => Location::SORT_ORDER_ASC,
                    'contentInfo' => new ContentInfo(
                        array(
                            'id' => 42
                        )
                    )
                )
            ),
            // Dummy value for ChildCount
            0
        );

        $this->addRouteExpectation(
            'ezpublish_rest_loadLocation',
            array( 'locationPath' => '1' ),
            '/content/locations/1'
        );
        $this->addRouteExpectation(
            'ezpublish_rest_loadLocationChildren',
            array( 'locationPath' => '1' ),
            '/content/locations/1/children'
        );
        $this->addRouteExpectation(
            'ezpublish_rest_loadContent', array( 'contentId' => $location->location->contentId ),
            "/content/objects/{$location->location->contentId}"
        );
        $this->addRouteExpectation(
            'ezpublish_rest_listLocationURLAliases', array( 'locationPath' => '1' ),
            "/content/objects/1/urlaliases"
        );

        $visitor->visit(
            $this->getVisitorMock(),
            $generator,
            $location
        );

        $result = $generator->endDocument( null );

        $this->assertNotNull( $result );

        return $result;
    }

    /**
     * Test if result contains id value element
     *
     * @param string $result
     *
     * @depends testVisit
     */
    public function testResultContainsIdValueElement( $result )
    {
        $this->assertTag(
            array(
                'tag'      => 'id',
                'content'  => '1'
            ),
            $result,
            'Invalid or non-existing <Location> id value element.',
            false
        );
    }

    /**
     * Test if result contains ParentLocation element
     *
     * @param string $result
     *
     * @depends testVisit
     */
    public function testResultContainsParentLocationElement( $result )
    {
        $this->assertTag(
            array(
                'tag'      => 'ParentLocation'
            ),
            $result,
            'Invalid <ParentLocation> element.',
            false
        );
    }

    /**
     * Test if result contains ParentLocation element attributes
     *
     * @param string $result
     *
     * @depends testVisit
     */
    public function testResultContainsParentLocationAttributes( $result )
    {
        $this->assertTag(
            array(
                'tag'      => 'ParentLocation',
                'attributes' => array()
            ),
            $result,
            'Invalid <ParentLocation> attributes.',
            false
        );
    }

    /**
     * Test if result contains Location element attributes
     *
     * @param string $result
     *
     * @depends testVisit
     */
    public function testResultContainsLocationAttributes( $result )
    {
        $this->assertTag(
            array(
                'tag'      => 'Location',
                'attributes' => array(
                    'media-type' => 'application/vnd.ez.api.Location+xml',
                    'href'       => '/content/locations/1',
                )
            ),
            $result,
            'Invalid <Location> attributes.',
            false
        );
    }

    /**
     * Test if result contains Children element attributes
     *
     * @param string $result
     *
     * @depends testVisit
     */
    public function testResultContainsChildrenAttributes( $result )
    {
        $this->assertTag(
            array(
                'tag'      => 'Children',
                'attributes' => array(
                    'media-type' => 'application/vnd.ez.api.LocationList+xml',
                    'href'       => '/content/locations/1/children',
                )
            ),
            $result,
            'Invalid <Children> attributes.',
            false
        );
    }

    /**
     * Test if result contains pathString value element
     *
     * @param string $result
     *
     * @depends testVisit
     */
    public function testResultContainsPathStringValueElement( $result )
    {
        $this->assertTag(
            array(
                'tag'      => 'pathString',
                'content'  => '/1'
            ),
            $result,
            'Invalid or non-existing <Location> pathString value element.',
            false
        );
    }

    /**
     * Test if result contains Content element attributes
     *
     * @param string $result
     *
     * @depends testVisit
     */
    public function testResultContainsUrlAliasesTagAttributes( $result )
    {
        $this->assertTag(
            array(
                'tag' => 'UrlAliases',
                'attributes' => array(
                    'media-type' => 'application/vnd.ez.api.UrlAliasRefList+xml',
                    'href'       => '/content/objects/1/urlaliases',
                )
            ),
            $result,
            'Invalid <UrlAliases> attributes.',
            false
        );
    }

    /**
     * Get the Location visitor
     *
     * @return \eZ\Publish\Core\REST\Server\Output\ValueObjectVisitor\RestLocation
     */
    protected function internalGetVisitor()
    {
        return new ValueObjectVisitor\RestLocation;
    }
}
