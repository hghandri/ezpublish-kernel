<?php
/**
 * File containing the index.php for the REST Server
 *
 * ATTENTION: This is a test setup for the REST server. DO NOT USE IT IN
 * PRODUCTION!
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\REST\Server;
use eZ\Publish\Core\REST\Common;

use Qafoo\RMF;

require __DIR__ . '/../../../../../bootstrap.php';

/*
 * This is a very simple session handling for the repository, which allows the
 * integration tests to run multiple requests against a continuous repository
 * state. This is needed in many test methods, e.g. in
 * SectionServiceTest::testUpdateSection() where there is 1. the section loaded
 * and 2. updated.
 *
 * The test framework therefore issues an X-Test-Session header, with the same
 * session ID for a dedicated test method. The complete repository (including
 * fixture data) is stored serialized at the end of this file.
 */

$stateDir    = __DIR__ . '/_state/';
$sessionFile = null;
$repository  = null;
if ( isset( $_SERVER['HTTP_X_TEST_SESSION'] ) )
{
    // Check if we are in a test session and if, for this session, a repository
    // state file already exists.
    $sessionFile = $stateDir . $_SERVER['HTTP_X_TEST_SESSION'] . '.php';
    if ( is_file( $sessionFile ) )
    {
        $repository = unserialize( file_get_contents( $sessionFile ) );
    }
}

if ( !$repository )
{
    $setupFactory = new \eZ\Publish\API\Repository\Tests\SetupFactory\Memory();
    $repository   = $setupFactory->getRepository();
}

/*
 * Handlers are used to parse the input body (XML or JSON) into a common array
 * structure, as generated by json_decode( $body, true ).
 */

$handler = array(
    'json' => new Common\Input\Handler\Json(),
    'xml'  => new Common\Input\Handler\Xml(),
);

// The URL Handler is responsible for URL parsing and generation. It will be
// used in the output generators and in some parsing handlers.
$urlHandler = new Common\UrlHandler\eZPublish();

/*
 * The Input Dispatcher receives the array structure as decoded by a handler
 * fitting the input format. It selects a parser based on the media type of the
 * input, which is used to transform the input into a ValueObject.
 */

$inputDispatcher = new Common\Input\Dispatcher(
    new Common\Input\ParsingDispatcher( array(
        'application/vnd.ez.api.RoleInput'              => new Input\Parser\RoleInput( $urlHandler, $repository->getRoleService() ),
        'application/vnd.ez.api.SectionInput'           => new Input\Parser\SectionInput( $urlHandler, $repository->getSectionService() ),
        'application/vnd.ez.api.ContentUpdate'          => new Input\Parser\ContentUpdate( $urlHandler ),
        'application/vnd.ez.api.PolicyCreate'           => new Input\Parser\PolicyCreate( $urlHandler, $repository->getRoleService() ),
        'application/vnd.ez.api.PolicyUpdate'           => new Input\Parser\PolicyUpdate( $urlHandler, $repository->getRoleService() ),
        'application/vnd.ez.api.limitation'             => new Input\Parser\Limitation( $urlHandler ),
        'application/vnd.ez.api.RoleAssignInput'        => new Input\Parser\RoleAssignInput( $urlHandler ),
        'application/vnd.ez.api.LocationCreate'         => new Input\Parser\LocationCreate( $urlHandler, $repository->getLocationService() ),
        'application/vnd.ez.api.LocationUpdate'         => new Input\Parser\LocationUpdate( $urlHandler, $repository->getLocationService() ),
        'application/vnd.ez.api.ObjectStateGroupCreate' => new Input\Parser\ObjectStateGroupCreate( $urlHandler, $repository->getObjectStateService() ),
        'application/vnd.ez.api.ObjectStateCreate'      => new Input\Parser\ObjectStateCreate( $urlHandler, $repository->getObjectStateService() ),
    ) ),
    $handler
);

/*
 * Controllers are simple classes with public methods. They are the only ones
 * working directly with the Request object provided by RMF. Their
 * responsibility is to extract the request data and dispatch the corresponding
 * call to methods of the Public API.
 */

$sectionController = new Controller\Section(
    $inputDispatcher,
    $urlHandler,
    $repository->getSectionService()
);

$contentController = new Controller\Content(
    $inputDispatcher,
    $urlHandler,
    $repository->getContentService(),
    $repository->getSectionService()
);

$roleController = new Controller\Role(
    $inputDispatcher,
    $urlHandler,
    $repository->getRoleService(),
    $repository->getUserService()
);

$locationController = new Controller\Location(
    $inputDispatcher,
    $urlHandler,
    $repository->getLocationService(),
    $repository->getContentService()
);

$objectStateController = new Controller\ObjectState(
    $inputDispatcher,
    $urlHandler,
    $repository->getObjectStateService()
);

/*
 * Visitors are used to transform the Value Objects returned by the Public API
 * into the output format requested by the client. In some cases, it is
 * necessary to use Value Objects which are not part of the Public API itself,
 * in order to encapsulate data structures which don't exist there (e.g.
 * SectionList) or to trigger slightly different output (e.g. CreatedSection to
 * generate a "Created" response).
 *
 * A visitor uses a generator (XML or JSON) to generate the output structure
 * according to the API definition. It can also set headers for the output.
 */

$valueObjectVisitors = array(
    '\\eZ\Publish\API\Repository\Exceptions\InvalidArgumentException'       => new Output\ValueObjectVisitor\InvalidArgumentException( $urlHandler,  true ),
    '\\eZ\Publish\API\Repository\Exceptions\NotFoundException'              => new Output\ValueObjectVisitor\NotFoundException( $urlHandler,  true ),
    '\\eZ\Publish\API\Repository\Exceptions\BadStateException'              => new Output\ValueObjectVisitor\BadStateException( $urlHandler,  true ),
    '\\Exception'                                                           => new Output\ValueObjectVisitor\Exception( $urlHandler,  true ),

    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\SectionList'                => new Output\ValueObjectVisitor\SectionList( $urlHandler ),
    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\CreatedSection'             => new Output\ValueObjectVisitor\CreatedSection( $urlHandler ),
    '\\eZ\\Publish\\API\\Repository\\Values\\Content\\Section'              => new Output\ValueObjectVisitor\Section( $urlHandler ),

    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\ContentList'                => new Output\ValueObjectVisitor\ContentList( $urlHandler ),
    '\\eZ\\Publish\\API\\Repository\\Values\\Content\\ContentInfo'          => new Output\ValueObjectVisitor\ContentInfo( $urlHandler ),

    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\RoleList'                   => new Output\ValueObjectVisitor\RoleList( $urlHandler ),
    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\CreatedRole'                => new Output\ValueObjectVisitor\CreatedRole( $urlHandler ),
    '\\eZ\\Publish\\API\\Repository\\Values\\User\\Role'                    => new Output\ValueObjectVisitor\Role( $urlHandler ),
    '\\eZ\\Publish\\API\\Repository\\Values\\User\\Policy'                  => new Output\ValueObjectVisitor\Policy( $urlHandler ),
    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\PolicyList'                 => new Output\ValueObjectVisitor\PolicyList( $urlHandler ),
    '\\eZ\\Publish\\API\\Repository\\Values\\User\\Limitation'              => new Output\ValueObjectVisitor\Limitation( $urlHandler ),
    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\RoleAssignmentList'         => new Output\ValueObjectVisitor\RoleAssignmentList( $urlHandler ),
    '\\eZ\\Publish\\API\\Repository\\Values\\Content\\Location'             => new Output\ValueObjectVisitor\Location( $urlHandler ),
    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\LocationList'               => new Output\ValueObjectVisitor\LocationList( $urlHandler ),
    '\\eZ\\Publish\\API\\Repository\\Values\\ObjectState\\ObjectStateGroup' => new Output\ValueObjectVisitor\ObjectStateGroup( $urlHandler ),
    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\ObjectStateGroupList'       => new Output\ValueObjectVisitor\ObjectStateGroupList( $urlHandler ),
    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\ObjectState'                => new Output\ValueObjectVisitor\ObjectState( $urlHandler ),
    '\\eZ\\Publish\\Core\\REST\\Server\\Values\\ObjectStateList'            => new Output\ValueObjectVisitor\ObjectStateList( $urlHandler ),
);

/*
 * We use a simple derived implementation of the RMF dispatcher here, which
 * first authenticates the user and then triggers the parent dispatching
 * process.
 *
 * The RMF dispatcher is the core of the MVC. It selects a controller method on
 * basis of the request URI (regex match) and the HTTP verb, which is then executed.
 * After the controller has been executed, the view (second parameter) is
 * triggered to send the result to the client. The Accept Header View
 * Dispatcher selects from different view configurations the output format
 * based on the Accept HTTP header sent by the client.
 *
 * The used inner views are custom to the REST server and dispatch the received
 * Value Object to one of the visitors registered above.
 */

$dispatcher = new AuthenticatingDispatcher(
    new RMF\Router\Regexp( array(
        '(^/content/sections$)' => array(
            'GET'  => array( $sectionController, 'listSections' ),
            'POST' => array( $sectionController, 'createSection' ),
        ),
        '(^/content/sections\?identifier=.*$)' => array(
            'GET'  => array( $sectionController, 'loadSectionByIdentifier' ),
        ),
        '(^/content/sections/[0-9]+$)' => array(
            'GET'    => array( $sectionController, 'loadSection' ),
            'PATCH'  => array( $sectionController, 'updateSection' ),
            'DELETE' => array( $sectionController, 'deleteSection' ),
        ),
        '(^/content/objects\?remoteId=[0-9a-z]+$)' => array(
            'GET'   => array( $contentController, 'loadContentInfoByRemoteId' ),
        ),
        '(^/content/objects/[0-9]+$)' => array(
            'PATCH' => array( $contentController, 'updateContentMetadata' ),
        ),
        '(^/content/objects/[0-9]+/locations$)' => array(
            'GET' => array( $locationController, 'loadLocationsForContent' ),
            'POST' => array( $locationController, 'createLocation' ),
        ),
        '(^/content/objectstategroups$)' => array(
            'GET' => array( $objectStateController, 'loadObjectStateGroups' ),
            'POST' => array( $objectStateController, 'createObjectStateGroup' ),
        ),
        '(^/content/objectstategroups/[0-9]+$)' => array(
            'GET' => array( $objectStateController, 'loadObjectStateGroup' ),
            'DELETE' => array( $objectStateController, 'deleteObjectStateGroup' ),
        ),
        '(^/content/objectstategroups/[0-9]+/objectstates$)' => array(
            'GET' => array( $objectStateController, 'loadObjectStates' ),
            'POST' => array( $objectStateController, 'createObjectState' ),
        ),
        '(^/content/objectstategroups/[0-9]+/objectstates/[0-9]+$)' => array(
            'GET' => array( $objectStateController, 'loadObjectState' ),
            'DELETE' => array( $objectStateController, 'deleteObjectState' ),
        ),
        '(^/content/locations/[0-9/]+$)' => array(
            'GET'    => array( $locationController, 'loadLocation' ),
            'PATCH'  => array( $locationController, 'updateLocation' ),
        ),
        '(^/content/locations/[0-9/]+/children$)' => array(
            'GET'    => array( $locationController, 'loadLocationChildren' ),
        ),
        '(^/user/roles$)' => array(
            'GET' => array( $roleController, 'listRoles' ),
            'POST' => array( $roleController, 'createRole' ),
        ),
        '(^/user/roles\?identifier=.*$)' => array(
            'GET'  => array( $roleController, 'loadRoleByIdentifier' ),
        ),
        '(^/user/roles/[0-9]+$)' => array(
            'GET'    => array( $roleController, 'loadRole' ),
            'PATCH'  => array( $roleController, 'updateRole' ),
            'DELETE' => array( $roleController, 'deleteRole' ),
        ),
        '(^/user/roles/[0-9]+/policies$)' => array(
            'GET'    => array( $roleController, 'loadPolicies' ),
            'POST'   => array( $roleController, 'addPolicy' ),
            'DELETE' => array( $roleController, 'deletePolicies' ),
        ),
        '(^/user/roles/[0-9]+/policies/[0-9]+$)' => array(
            'PATCH'  => array( $roleController, 'updatePolicy' ),
            'DELETE' => array( $roleController, 'deletePolicy' ),
        ),
        '(^/user/users/[0-9]+/roles$)' => array(
            'GET'  => array( $roleController, 'loadRoleAssignmentsForUser' ),
            'POST'  => array( $roleController, 'assignRoleToUser' ),
        ),
        '(^/user/users/[0-9]+/roles/[0-9]+$)' => array(
            'DELETE'  => array( $roleController, 'unassignRoleFromUser' ),
        ),
        '(^/user/groups/[0-9/]+/roles$)' => array(
            'GET'  => array( $roleController, 'loadRoleAssignmentsForUserGroup' ),
            'POST'  => array( $roleController, 'assignRoleToUserGroup' ),
        '(^/user/groups/[0-9/]+/roles/[0-9]+$)' => array(
            'DELETE'  => array( $roleController, 'unassignRoleFromUserGroup' ),
        ),
        ),
    ) ),
    new RMF\View\AcceptHeaderViewDispatcher( array(
        '(^application/vnd\\.ez\\.api\\.[A-Za-z]+\\+json$)' => new View\Visitor(
            new Common\Output\Visitor(
                new Common\Output\Generator\Json(),
                $valueObjectVisitors
            )
        ),
        '(^application/vnd\\.ez\\.api\\.[A-Za-z]+\\+xml$)'  => new View\Visitor(
            new Common\Output\Visitor(
                new Common\Output\Generator\Xml(),
                $valueObjectVisitors
            )
        ),
        '(^.*/.*$)'  => new View\InvalidApiUse(),
    ) ),
    // This is just used for integration tests, DO NOT USE IN PRODUCTION
    new Authenticator\IntegrationTest( $repository )
    // For productive use, e.g. use
    // new Authenticator\BasicAuth( $repository )
);

/*
 * The simple request abstraction class provided by RMF allows handlers to be
 * registered, which extract request data and provide it via property access in
 * a manor of lazy loading.
 */

$request = new RMF\Request\HTTP();
$request->addHandler( 'body', new RMF\Request\PropertyHandler\RawBody() );
$request->addHandler( 'contentType', new RMF\Request\PropertyHandler\Server( 'CONTENT_TYPE' ) );
$request->addHandler( 'method', new RMF\Request\PropertyHandler\Override( array(
    new RMF\Request\PropertyHandler\Server( 'HTTP_X_HTTP_METHOD_OVERRIDE' ),
    new RMF\Request\PropertyHandler\Server( 'REQUEST_METHOD' ),
) ) );

// ATTENTION: Only used for test setup
$request->addHandler( 'testUser', new RMF\Request\PropertyHandler\Server( 'HTTP_X_TEST_USER' ) );

// For the use of Authenticator\BasicAuth:
// $request->addHandler( 'username', new RMF\Request\PropertyHandler\Server( 'PHP_AUTH_USER' ) );
// $request->addHandler( 'password', new RMF\Request\PropertyHandler\Server( 'PHP_AUTH_PW' ) );

/*
 * This triggers working of the MVC.
 */
$dispatcher->dispatch( $request );

/*
 * The session state is stored, if a session file was specified at the
 * beginning of the script. This is only necessary for the test setup.
 */
if ( $sessionFile )
{
    file_put_contents( $sessionFile, serialize( $repository ) );
}
