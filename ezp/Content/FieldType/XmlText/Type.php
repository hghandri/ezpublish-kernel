<?php
/**
 * File containing the ezp\Content\FieldType\XmlBlock class.
 *
 * @copyright Copyright (C) 1999-2011 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace ezp\Content\FieldType\XmlText;
use ezp\Base\Repository,
    ezp\Content\Field,
    ezp\Content\Version,
    ezp\Content\FieldType,
    ezp\Content\FieldType\OnPublish,
    ezp\Content\FieldType\OnCreate,
    ezp\Content\FieldType\Value as BaseValue,
    ezp\Content\FieldType\XmlText\Value as Value,
    ezp\Content\Type\FieldDefinition,
    ezp\Base\Exception\BadFieldTypeInput,
    ezp\Base\Exception\InvalidArgumentType;

/**
 * XmlBlock field type.
 *
 * This field
 * @package
 */
class Type extends FieldType implements OnPublish, OnCreate
{
    const FIELD_TYPE_IDENTIFIER = "ezxmltext";
    const IS_SEARCHABLE = true;

    /**
     * List of settings available for this FieldType
     *
     * The key is the setting name, and the value is the default value for this setting
     *
     * @var array
     */
    protected $allowedSettings = array(
        'numRows' => 10,
        'tagPreset' => null,
    );

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \ezp\Content\FieldType\TextLine\Value
     */
    protected function getDefaultValue()
    {
        $value = <<< EOF
<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ez.no/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ez.no/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ez.no/namespaces/ezpublish3/custom/" />
EOF;
        return new Value( $value, Value::INPUT_FORMAT_RAW );
    }

    /**
     * Checks if $inputValue can be parsed.
     * If the $inputValue actually can be parsed, the value is returned.
     * Otherwise, an \ezp\Base\Exception\BadFieldTypeInput exception is thrown
     *
     * @throws \ezp\Base\Exception\BadFieldTypeInput Thrown when $inputValue is not understood.
     * @param \ezp\Content\FieldType\XmlText\Value $inputValue
     * @return \ezp\Content\FieldType\TextLine\Value
     */
    protected function canParseValue( BaseValue $inputValue )
    {
        if ( $inputValue instanceof Value )
        {

            if ( !is_string( $inputValue->text ) )
            {
                throw new BadFieldTypeInput( $inputValue, get_class( $this ) );
            }

            $handler = $inputValue->getInputHandler();
            if ( !$handler->isXmlValid( $inputValue->text, false ) )
            {
                // @todo Pass on the parser error messages (if any: $handler->getParsingMessages())
                throw new BadFieldTypeInput( $inputValue, get_class() );
            }

            return $inputValue;
        }

        throw new InvalidArgumentType( 'value', 'ezp\\Content\\FieldType\\XmlText\\Value' );
    }

    /**
     * Returns sortKey information
     *
     * @see ezp\Content\FieldType
     *
     * @return array|bool
     */
    protected function getSortInfo()
    {
        return false;
    }

    /**
     * Converts complex values to a Value\Raw object
     * @param \ezp\Content\FieldType\XmlText\Value $value
     * @param \ezp\Base\Repository $repository
     * @param \ezp\Content\Field $field
     * @return \ezp\Content\FieldType\XmlText\Value
     */
    protected function convertValueToRawValue( Value $value, Repository $repository, Field $field )
    {
        // we don't convert Raw to Raw, right ?
        // if ( get_class( $value ) === 'ezp\\Content\\FieldType\\XmlText\\Value' )
        //    return $value;

        $handler = $value->getInputHandler( $value );
        $handler->process( $value->text, $repository, $field->version );

        $value->setRawText( $handler->getDocumentAsXml() );
    }

    /**
     * Event handler for pre_publish
     * @param \ezp\Base\Repository $repository
     * @param \ezp\Content\Version $version
     * @param \ezp\Content\Field $field
     */
    public function onPrePublish( Repository $repository, Field $field )
    {
    }

    /**
     * Event handler for post_publish
     * @param \ezp\Base\Repository $repository
     * @param \ezp\Content\Version $version
     * @param \ezp\Content\Field $field
     */
    public function onPostPublish( Repository $repository, Field $field )
    {
    }

    /**
     * Event handler for pre_create
     * @param \ezp\Base\Repository $repository
     * @param \ezp\Content\Field $field
     */
    public function onPreCreate( Repository $repository, Field $field )
    {
        $this->convertValueToRawValue( $field->value, $repository, $field );
    }

    /**
     * Event handler for pre_create
     * @param \ezp\Base\Repository $repository
     * @param \ezp\Content\Field $field
     */
    public function onPostCreate( Repository $repository, Field $field )
    {
    }
}
