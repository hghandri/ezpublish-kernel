<?php
/**
 * File containing the ObjectStateCreate parser class
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\REST\Server\Input\Parser;

use eZ\Publish\Core\REST\Common\Input\ParsingDispatcher;
use eZ\Publish\Core\REST\Common\Input\ParserTools;
use eZ\Publish\Core\REST\Common\Exceptions;
use eZ\Publish\API\Repository\ObjectStateService;

/**
 * Parser for ObjectStateCreate
 */
class ObjectStateCreate extends Base
{
    /**
     * Object state service
     *
     * @var \eZ\Publish\API\Repository\ObjectStateService
     */
    protected $objectStateService;

    /**
     * @var \eZ\Publish\Core\REST\Common\Input\ParserTools
     */
    protected $parserTools;

    /**
     * Construct
     *
     * @param \eZ\Publish\API\Repository\ObjectStateService $objectStateService
     * @param \eZ\Publish\Core\REST\Common\Input\ParserTools $parserTools
     */
    public function __construct( ObjectStateService $objectStateService, ParserTools $parserTools )
    {
        $this->objectStateService = $objectStateService;
        $this->parserTools = $parserTools;
    }

    /**
     * Parse input structure
     *
     * @param array $data
     * @param \eZ\Publish\Core\REST\Common\Input\ParsingDispatcher $parsingDispatcher
     *
     * @return \eZ\Publish\API\Repository\Values\ObjectState\ObjectStateCreateStruct
     */
    public function parse( array $data, ParsingDispatcher $parsingDispatcher )
    {
        if ( !array_key_exists( 'identifier', $data ) )
        {
            throw new Exceptions\Parser( "Missing 'identifier' attribute for ObjectStateCreate." );
        }

        $objectStateCreateStruct = $this->objectStateService->newObjectStateCreateStruct( $data['identifier'] );

        if ( !array_key_exists( 'priority', $data ) )
        {
            throw new Exceptions\Parser( "Missing 'priority' attribute for ObjectStateCreate." );
        }

        $objectStateCreateStruct->priority = (int)$data['priority'];

        if ( !array_key_exists( 'defaultLanguageCode', $data ) )
        {
            throw new Exceptions\Parser( "Missing 'defaultLanguageCode' attribute for ObjectStateCreate." );
        }

        $objectStateCreateStruct->defaultLanguageCode = $data['defaultLanguageCode'];

        if ( !array_key_exists( 'names', $data ) || !is_array( $data['names'] ) )
        {
            throw new Exceptions\Parser( "Missing or invalid 'names' element for ObjectStateCreate." );
        }

        if ( !array_key_exists( 'value', $data['names'] ) || !is_array( $data['names']['value'] ) )
        {
            throw new Exceptions\Parser( "Missing or invalid 'names' element for ObjectStateCreate." );
        }

        $objectStateCreateStruct->names = $this->parserTools->parseTranslatableList( $data['names'] );

        // @todo XSD says that descriptions field is mandatory. Does that make sense?
        if ( array_key_exists( 'descriptions', $data ) && is_array( $data['descriptions'] ) )
        {
            $objectStateCreateStruct->descriptions = $this->parserTools->parseTranslatableList( $data['descriptions'] );
        }

        return $objectStateCreateStruct;
    }
}
