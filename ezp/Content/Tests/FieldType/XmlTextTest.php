<?php
/**
 * File containing the XmlText\FieldTypeTest class
 *
 * @copyright Copyright (C) 1999-2011 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace ezp\Content\Tests\FieldType;
use ezp\Content\FieldType\Factory,
    ezp\Content\FieldType\XmlText\Type as XmlTextType,
    ezp\Content\FieldType\Value as BaseValue,
    ezp\Content\FieldType\XmlText\Value as XmlTextValue,
    ezp\Base\Exception\BadFieldTypeInput,
    PHPUnit_Framework_TestCase,
    ReflectionObject, ReflectionProperty;

/**
 * @group fieldType
 */
class XmlTextTypeTest extends PHPUnit_Framework_TestCase
{
    /**
     * This test will make sure a correct mapping for the field type string has
     * been made.
     *
     * @covers \ezp\Content\FieldType\Factory::build
     */
    public function testFactory()
    {
        self::assertInstanceOf(
            "ezp\\Content\\FieldType\\XmlText\\Type",
            Factory::build( "ezxmltext" ),
            "XmlText object not returned for 'ezxmltext'. Incorrect mapping?"
        );
    }

    /**
     * @covers \ezp\Content\FieldType\XmlText\Type::canParseValue
     * @expectedException \ezp\Base\Exception\InvalidArgumentType
     */
    public function testCanParseValueInvalidType()
    {
        $ft = new XmlTextType;
        $ft->setValue( $this->getMock( 'ezp\\Content\\FieldType\\Value' ) );
    }

    /**
     * @covers \ezp\Content\FieldType\XmlText\Type::canParseValue
     * @expectedException \ezp\Base\Exception\BadFieldTypeInput
     * @dataProvider providerForTestCanParseValueInvalidFormat
     */
    public function testCanParseValueInvalidFormat( $text, $format )
    {
        $ft = new XmlTextType;
        $value = new XmlTextValue( $text, $format );
        $ft->setValue( $value );
    }

    /**
     * @covers \ezp\Content\FieldType\Author\Type::canParseValue
     * @dataProvider providerForTestCanParseValueValidFormat
     */
    public function testCanParseValueValidFormat( $text, $format )
    {
        $ft = new XmlTextType;
        $value = new XmlTextValue( $text, $format );
        $ft->setValue( $value );
        self::assertSame( $value, $ft->getValue() );
    }

    /**
     * @covers \ezp\Content\FieldType\XmlText\Type::toFieldValue
     */
    public function testToFieldValue()
    {
        // @todo Do one per value class
        $value = new XmlTextValue( '', XmlTextValue::INPUT_FORMAT_PLAIN );

        $ft = new XmlTextType();
        $ft->setValue( $value );

        $fieldValue = $ft->toFieldValue();

        self::assertSame( $value, $fieldValue->data );
    }

    public function providerForTestCanParseValueInvalidFormat()
    {
        return array(

            // RawValue requires root XML + section tags
            array( '', XmlTextValue::INPUT_FORMAT_RAW ),

            // wrong closing tag
            array( '<a href="http://www.google.com/">bar</foo>', XmlTextValue::INPUT_FORMAT_PLAIN ),
        );
    }

    public static function providerForTestCanParseValueValidFormat()
    {
        return array(

            array(
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ez.no/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ez.no/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ez.no/namespaces/ezpublish3/custom/"><header level="1">This is a piece of text</header></section>',
                XmlTextValue::INPUT_FORMAT_RAW ),

            array(
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns:image="http://ez.no/namespaces/ezpublish3/image/"
         xmlns:xhtml="http://ez.no/namespaces/ezpublish3/xhtml/"
         xmlns:custom="http://ez.no/namespaces/ezpublish3/custom/" />',
                XmlTextValue::INPUT_FORMAT_RAW ),

            array( '<section>test</section>', XmlTextValue::INPUT_FORMAT_PLAIN ),

            array( '<paragraph><a href="eznode://1">test</a><a href="ezobject://1">test</a></paragraph>', XmlTextValue::INPUT_FORMAT_PLAIN ),
        );
    }
}
