<?php
/**
 * File containing the Type Update Handler base class
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\Persistence\Legacy\Content\Type\Update;

use eZ\Publish\SPI\Persistence\Content\Type;

/**
 * Base class for update handlers
 */
abstract class Handler
{
    /**
     * Updates existing content objects from $fromType to $toType
     *
     * @param \eZ\Publish\SPI\Persistence\Content\Type $fromType
     * @param \eZ\Publish\SPI\Persistence\Content\Type $toType
     *
     * @return void
     */
    abstract public function updateContentObjects( Type $fromType, Type $toType );

    /**
     * Deletes $fromType and all of its field definitions
     *
     * @param \eZ\Publish\SPI\Persistence\Content\Type $fromType
     *
     * @return void
     */
    abstract public function deleteOldType( Type $fromType );

    /**
     * Publishes $toType to $newStatus
     *
     * @param \eZ\Publish\SPI\Persistence\Content\Type $toType
     * @param int $newStatus
     *
     * @return void
     */
    abstract public function publishNewType( Type $toType, $newStatus );
}
