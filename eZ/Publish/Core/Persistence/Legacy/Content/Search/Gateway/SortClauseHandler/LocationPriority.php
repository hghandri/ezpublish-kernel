<?php
/**
 * File containing a DoctrineDatabase sort clause handler class
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\Persistence\Legacy\Content\Search\Gateway\SortClauseHandler;

use eZ\Publish\Core\Persistence\Legacy\Content\Search\Common\Gateway\SortClauseHandler;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\Core\Persistence\Database\SelectQuery;

/**
 * Content locator gateway implementation using the DoctrineDatabase.
 */
class LocationPriority extends SortClauseHandler
{
    /**
     * Check if this sort clause handler accepts to handle the given sort clause.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\SortClause $sortClause
     *
     * @return boolean
     */
    public function accept( SortClause $sortClause )
    {
        return $sortClause instanceof SortClause\LocationPriority;
    }

    /**
     * Apply selects to the query
     *
     * Returns the name of the (aliased) column, which information should be
     * used for sorting.
     *
     * @param \eZ\Publish\Core\Persistence\Database\SelectQuery $query
     * @param \eZ\Publish\API\Repository\Values\Content\Query\SortClause $sortClause
     * @param int $number
     *
     * @return string
     */
    public function applySelect( SelectQuery $query, SortClause $sortClause, $number )
    {
        $query
            ->select(
                $query->alias(
                    $this->dbHandler->quoteColumn(
                        'priority',
                        $this->getSortTableName( $number )
                    ),
                    $column = $this->getSortColumnName( $number )
                )
            );

        return $column;
    }

    /**
     * Applies joins to the query, required to fetch sort data
     *
     * @param \eZ\Publish\Core\Persistence\Database\SelectQuery $query
     * @param \eZ\Publish\API\Repository\Values\Content\Query\SortClause $sortClause
     * @param int $number
     *
     * @return void
     */
    public function applyJoin( SelectQuery $query, SortClause $sortClause, $number )
    {
        $table = $this->getSortTableName( $number );
        $query
            ->leftJoin(
                $query->alias(
                    $this->dbHandler->quoteTable( 'ezcontentobject_tree' ),
                    $this->dbHandler->quoteIdentifier( $table )
                ),
                $query->expr->eq(
                    $this->dbHandler->quoteColumn( 'contentobject_id', $table ),
                    $this->dbHandler->quoteColumn( 'id', 'ezcontentobject' )
                )
            );
    }
}

