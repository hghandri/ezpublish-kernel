<?php
/**
 * File containing the BaseTest class.
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\MVC\Symfony\Matcher\Tests\ContentBased;

use PHPUnit_Framework_TestCase;

abstract class BaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repositoryMock;

    protected function setUp()
    {
        parent::setUp();
        $this->repositoryMock = $this->getRepositoryMock();
    }

    /**
     * @param array $matchingConfig
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPartiallyMockedViewProvider( array $matchingConfig = array() )
    {
        return $this
            ->getMockBuilder( 'eZ\\Publish\\Core\\MVC\\Symfony\\View\\Provider\\Location\\Configured' )
            ->setConstructorArgs(
                array(
                    $this->repositoryMock,
                    $matchingConfig
                )
            )
            ->setMethods( array( 'getMatcher' ) )
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRepositoryMock()
    {
        $repositoryClass = 'eZ\\Publish\\Core\\Repository\\DomainLogic\\Repository';

        return $this
            ->getMockBuilder( $repositoryClass )
            ->disableOriginalConstructor()
            ->setMethods(
                array_diff(
                    get_class_methods( $repositoryClass ),
                    array( 'sudo' )
                )
            )
            ->getMock();
    }

    /**
     * @param array $properties
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLocationMock( array $properties = array() )
    {
        return $this
            ->getMockBuilder( 'eZ\\Publish\\API\\Repository\\Values\\Content\\Location' )
            ->setConstructorArgs( array( $properties ) )
            ->getMockForAbstractClass();
    }

    /**
     * @param array $properties
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContentInfoMock( array $properties = array() )
    {
        return $this->
            getMockBuilder( 'eZ\\Publish\\API\\Repository\\Values\\Content\\ContentInfo' )
            ->setConstructorArgs( array( $properties ) )
            ->getMockForAbstractClass();
    }
}
