<?php
/**
 * File containing the SecurityListenerTest class.
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\MVC\Symfony\Security\Tests\EventListener;

use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute;
use eZ\Publish\Core\MVC\Symfony\Security\EventListener\SecurityListener;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent as BaseInteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SecurityListenerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $configResolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $securityContext;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\Security\EventListener\SecurityListener
     */
    private $listener;

    protected function setUp()
    {
        parent::setUp();
        $this->repository = $this->getMock( 'eZ\Publish\API\Repository\Repository' );
        $this->configResolver = $this->getMock( 'eZ\Publish\Core\MVC\ConfigResolverInterface' );
        $this->eventDispatcher = $this->getMock( 'Symfony\Component\EventDispatcher\EventDispatcherInterface' );
        $this->securityContext = $this->getMock( 'Symfony\Component\Security\Core\SecurityContextInterface' );
        $this->listener = new SecurityListener( $this->repository, $this->configResolver, $this->eventDispatcher, $this->securityContext );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            array(
                SecurityEvents::INTERACTIVE_LOGIN => array(
                    array( 'onInteractiveLogin', 10 ),
                    array( 'checkSiteAccessPermission', 9 ),
                ),
                KernelEvents::REQUEST => array( 'onKernelRequest', 7 )
            ),
            SecurityListener::getSubscribedEvents()
        );
    }

    public function testOnInteractiveLoginAlreadyEzUser()
    {
        $user = $this->getMock( 'eZ\Publish\Core\MVC\Symfony\Security\UserInterface' );
        $token = $this->getMock( 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface' );
        $token
            ->expects( $this->once() )
            ->method( 'getUser' )
            ->will( $this->returnValue( $user ) );
        $event = new BaseInteractiveLoginEvent( new Request(), $token );

        $this->eventDispatcher
            ->expects( $this->never() )
            ->method( 'dispatch' );

        $this->listener->onInteractiveLogin( $event );
    }

    public function testOnInteractiveLoginNotUserObject()
    {
        $user = 'foobar';
        $token = $this->getMock( 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface' );
        $token
            ->expects( $this->once() )
            ->method( 'getUser' )
            ->will( $this->returnValue( $user ) );
        $event = new BaseInteractiveLoginEvent( new Request(), $token );

        $this->eventDispatcher
            ->expects( $this->never() )
            ->method( 'dispatch' );

        $this->listener->onInteractiveLogin( $event );
    }

    public function testOnInteractiveLogin()
    {
        $user = $this->getMock( 'Symfony\Component\Security\Core\User\UserInterface' );
        $token = $this->getMock( 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface' );
        $token
            ->expects( $this->once() )
            ->method( 'getUser' )
            ->will( $this->returnValue( $user ) );
        $token
            ->expects( $this->once() )
            ->method( 'getRoles' )
            ->will( $this->returnValue( array( 'ROLE_USER' ) ) );
        $token
            ->expects( $this->once() )
            ->method( 'getAttributes' )
            ->will( $this->returnValue( array( 'foo' => 'bar' ) ) );

        $event = new BaseInteractiveLoginEvent( new Request(), $token );

        $anonymousUserId = 10;
        $this->configResolver
            ->expects( $this->once() )
            ->method( 'getParameter' )
            ->with( 'anonymous_user_id' )
            ->will( $this->returnValue( $anonymousUserId ) );

        $apiUser = $this->getMock( 'eZ\Publish\API\Repository\Values\User\User' );
        $userService = $this->getMock( 'eZ\Publish\API\Repository\UserService' );
        $userService
            ->expects( $this->once() )
            ->method( 'loadUser' )
            ->with( $anonymousUserId )
            ->will( $this->returnValue( $apiUser ) );

        $this->repository
            ->expects( $this->once() )
            ->method( 'getUserService' )
            ->will( $this->returnValue( $userService ) );
        $this->repository
            ->expects( $this->once() )
            ->method( 'setCurrentUser' )
            ->with( $apiUser );

        $this->securityContext
            ->expects( $this->once() )
            ->method( 'setToken' )
            ->with( $this->isInstanceOf( 'eZ\Publish\Core\MVC\Symfony\Security\InteractiveLoginToken' ) );

        $this->listener->onInteractiveLogin( $event );
    }

    /**
     * @expectedException \eZ\Publish\Core\MVC\Symfony\Security\Exception\UnauthorizedSiteAccessException
     */
    public function testCheckSiteAccessPermissionDenied()
    {
        $user = $this->getMock( 'eZ\Publish\Core\MVC\Symfony\Security\UserInterface' );
        $token = $this->getMock( 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface' );
        $token
            ->expects( $this->once() )
            ->method( 'getUser' )
            ->will( $this->returnValue( $user ) );

        $request = new Request();
        $siteAccess = new SiteAccess();
        $request->attributes->set( 'siteaccess', $siteAccess );

        $this->securityContext
            ->expects( $this->once() )
            ->method( 'isGranted' )
            ->with( $this->equalTo( new Attribute( 'user', 'login', array( 'valueObject' => $siteAccess ) ) ) )
            ->will( $this->returnValue( false ) );

        $this->listener->checkSiteAccessPermission( new BaseInteractiveLoginEvent( $request, $token ) );
    }

    public function testCheckSiteAccessPermissionGranted()
    {
        $user = $this->getMock( 'eZ\Publish\Core\MVC\Symfony\Security\UserInterface' );
        $token = $this->getMock( 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface' );
        $token
            ->expects( $this->once() )
            ->method( 'getUser' )
            ->will( $this->returnValue( $user ) );

        $request = new Request();
        $siteAccess = new SiteAccess();
        $request->attributes->set( 'siteaccess', $siteAccess );

        $this->securityContext
            ->expects( $this->once() )
            ->method( 'isGranted' )
            ->with( $this->equalTo( new Attribute( 'user', 'login', array( 'valueObject' => $siteAccess ) ) ) )
            ->will( $this->returnValue( true ) );

        // Nothing should happen or should be returned.
        $this->listener->checkSiteAccessPermission( new BaseInteractiveLoginEvent( $request, $token ) );
    }

    public function testCheckSiteAccessNotEzUser()
    {
        $user = $this->getMock( 'Symfony\Component\Security\Core\User\UserInterface' );
        $token = $this->getMock( 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface' );
        $token
            ->expects( $this->once() )
            ->method( 'getUser' )
            ->will( $this->returnValue( $user ) );

        $request = new Request();
        $siteAccess = new SiteAccess();
        $request->attributes->set( 'siteaccess', $siteAccess );

        $this->securityContext
            ->expects( $this->never() )
            ->method( 'isGranted' );

        $this->listener->checkSiteAccessPermission( new BaseInteractiveLoginEvent( $request, $token ) );
    }

    public function testCheckSiteAccessNoSiteAccess()
    {
        $user = $this->getMock( 'eZ\Publish\Core\MVC\Symfony\Security\UserInterface' );
        $token = $this->getMock( 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface' );
        $token
            ->expects( $this->once() )
            ->method( 'getUser' )
            ->will( $this->returnValue( $user ) );

        $this->securityContext
            ->expects( $this->never() )
            ->method( 'isGranted' );

        $this->listener->checkSiteAccessPermission( new BaseInteractiveLoginEvent( new Request(), $token ) );
    }

    public function testOnKernelRequestSubRequest()
    {
        $event = new GetResponseEvent(
            $this->getMock( 'Symfony\Component\HttpKernel\HttpKernelInterface' ),
            new Request(),
            HttpKernelInterface::SUB_REQUEST
        );

        $this->securityContext
            ->expects( $this->never() )
            ->method( 'getToken' );
        $this->securityContext
            ->expects( $this->never() )
            ->method( 'isGranted' );

        $this->listener->onKernelRequest( $event );
    }

    public function testOnKernelRequestSubRequestFragment()
    {
        $event = new GetResponseEvent(
            $this->getMock( 'Symfony\Component\HttpKernel\HttpKernelInterface' ),
            Request::create( '/_fragment' ),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->configResolver
            ->expects( $this->never() )
            ->method( 'getParameter' );

        $this->securityContext
            ->expects( $this->never() )
            ->method( 'getToken' );
        $this->securityContext
            ->expects( $this->never() )
            ->method( 'isGranted' );

        $this->listener->onKernelRequest( $event );
    }

    public function testOnKernelRequestLegacyMode()
    {
        $event = new GetResponseEvent(
            $this->getMock( 'Symfony\Component\HttpKernel\HttpKernelInterface' ),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->configResolver
            ->expects( $this->once() )
            ->method( 'getParameter' )
            ->with( 'legacy_mode' )
            ->will( $this->returnValue( true ) );

        $this->securityContext
            ->expects( $this->never() )
            ->method( 'getToken' );
        $this->securityContext
            ->expects( $this->never() )
            ->method( 'isGranted' );

        $this->listener->onKernelRequest( $event );
    }

    public function testOnKernelRequestNoSiteAccess()
    {
        $event = new GetResponseEvent(
            $this->getMock( 'Symfony\Component\HttpKernel\HttpKernelInterface' ),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->configResolver
            ->expects( $this->once() )
            ->method( 'getParameter' )
            ->with( 'legacy_mode' )
            ->will( $this->returnValue( false ) );

        $this->securityContext
            ->expects( $this->never() )
            ->method( 'getToken' );
        $this->securityContext
            ->expects( $this->never() )
            ->method( 'isGranted' );

        $this->listener->onKernelRequest( $event );
    }

    public function testOnKernelRequestNullToken()
    {
        $request = new Request();
        $request->attributes->set( 'siteaccess', new SiteAccess() );
        $event = new GetResponseEvent(
            $this->getMock( 'Symfony\Component\HttpKernel\HttpKernelInterface' ),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->configResolver
            ->expects( $this->once() )
            ->method( 'getParameter' )
            ->with( 'legacy_mode' )
            ->will( $this->returnValue( false ) );

        $this->securityContext
            ->expects( $this->once() )
            ->method( 'getToken' )
            ->will( $this->returnValue( null ) );
        $this->securityContext
            ->expects( $this->never() )
            ->method( 'isGranted' );

        $this->listener->onKernelRequest( $event );
    }

    public function testOnKernelRequestLoginRoute()
    {
        $request = new Request();
        $request->attributes->set( 'siteaccess', new SiteAccess() );
        $request->attributes->set( '_route', 'login' );
        $event = new GetResponseEvent(
            $this->getMock( 'Symfony\Component\HttpKernel\HttpKernelInterface' ),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->configResolver
            ->expects( $this->once() )
            ->method( 'getParameter' )
            ->with( 'legacy_mode' )
            ->will( $this->returnValue( false ) );

        $this->securityContext
            ->expects( $this->once() )
            ->method( 'getToken' )
            ->will( $this->returnValue( null ) );
        $this->securityContext
            ->expects( $this->never() )
            ->method( 'isGranted' );

        $this->listener->onKernelRequest( $event );
    }

    /**
     * @expectedException \eZ\Publish\Core\MVC\Symfony\Security\Exception\UnauthorizedSiteAccessException
     */
    public function testOnKernelRequestAccessDenied()
    {
        $request = new Request();
        $request->attributes->set( 'siteaccess', new SiteAccess() );
        $event = new GetResponseEvent(
            $this->getMock( 'Symfony\Component\HttpKernel\HttpKernelInterface' ),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->configResolver
            ->expects( $this->once() )
            ->method( 'getParameter' )
            ->with( 'legacy_mode' )
            ->will( $this->returnValue( false ) );

        $token = $this->getMock( 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface' );
        $token
            ->expects( $this->any() )
            ->method( 'getUsername' )
            ->will( $this->returnValue( 'foo' ) );

        $this->securityContext
            ->expects( $this->once() )
            ->method( 'getToken' )
            ->will( $this->returnValue( $token ) );
        $this->securityContext
            ->expects( $this->once() )
            ->method( 'isGranted' )
            ->will( $this->returnValue( false ) );

        $this->listener->onKernelRequest( $event );
    }

    public function testOnKernelRequestAccessGranted()
    {
        $request = new Request();
        $request->attributes->set( 'siteaccess', new SiteAccess() );
        $event = new GetResponseEvent(
            $this->getMock( 'Symfony\Component\HttpKernel\HttpKernelInterface' ),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->configResolver
            ->expects( $this->once() )
            ->method( 'getParameter' )
            ->with( 'legacy_mode' )
            ->will( $this->returnValue( false ) );

        $token = $this->getMock( 'Symfony\Component\Security\Core\Authentication\Token\TokenInterface' );
        $token
            ->expects( $this->any() )
            ->method( 'getUsername' )
            ->will( $this->returnValue( 'foo' ) );

        $this->securityContext
            ->expects( $this->once() )
            ->method( 'getToken' )
            ->will( $this->returnValue( $token ) );
        $this->securityContext
            ->expects( $this->once() )
            ->method( 'isGranted' )
            ->will( $this->returnValue( true ) );

        // Nothing should happen or should be returned.
        $this->listener->onKernelRequest( $event );
    }
}
