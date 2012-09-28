<?php
/**
 * File containing the CreatedRelation class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\REST\Server\Values;
use \eZ\Publish\API\Repository\Values\ValueObject;

/**
 * Struct representing a freshly created relation.
 */
class CreatedRelation extends ValueObject
{
    /**
     * The created relation
     *
     * @var \eZ\Publish\Core\REST\Server\Values\RestRelation
     */
    public $relation;
}
