<?php
/**
 * Created by PhpStorm.
 * User: Adapik
 * Date: 13.07.2017
 * Time: 21:51
 */

namespace Adapik\Test\CMS;

use Adapik\CMS\Mapper;
use Adapik\CMS\Maps\Certificate;
use FG\ASN1\ExplicitlyTaggedObject;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\Boolean;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\OctetString;
use FG\ASN1\Universal\PrintableString;
use FG\ASN1\Universal\Sequence;
use FG\ASN1\Universal\Set;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    public function testMap()
    {
        $map = ['type' => Identifier::INTEGER];

        $object       = Integer::create(123);
        $mappedObject = Mapper::map($object, $map);

        $this->assertEquals($mappedObject, $object);

        $map          = ['type' => Identifier::BOOLEAN];
        $mappedObject = Mapper::map($object, $map);

        $this->assertNull($mappedObject, $object);
    }

    public function testMapAny()
    {
        $map = ['type' => Identifier::ANY];

        $object       = Integer::create(123);
        $mappedObject = Mapper::map($object, $map);

        $this->assertEquals($mappedObject, $object);
    }

    public function testMapSequence()
    {
        $map = [
            'type' => Identifier::SEQUENCE,
            'children' => [
                'oid1'  => ['type' => Identifier::OBJECT_IDENTIFIER],
                'oid2'  => ['type' => Identifier::OBJECT_IDENTIFIER],
            ]
        ];

        $object       = Sequence::create([
            ObjectIdentifier::create('1.2.3'),
            ObjectIdentifier::create('1.2.4'),
        ]);
        $mappedObject = Mapper::map($object, $map);

        $this->assertCount(2, $mappedObject);
    }

    public function testMapSequenceOrder()
    {
        $map = [
            'type' => Identifier::SEQUENCE,
            'children' => [
                'oid1'  => ['type' => Identifier::INTEGER],
                'oid2'  => ['type' => Identifier::OBJECT_IDENTIFIER],
            ]
        ];

        $object       = Sequence::create([
            ObjectIdentifier::create('1.2.3'),
            Integer::create(1),
        ]);
        $mappedObject = Mapper::map($object, $map);

        $this->assertNull($mappedObject);
    }

    public function testMapSequenceWithOptionalElements()
    {
        $map = [
            'type' => Identifier::SEQUENCE,
            'children' => [
                'oid1'  => ['type' => Identifier::OBJECT_IDENTIFIER],
                'octetStringOptional' => [
                    'type'     => Identifier::OCTETSTRING,
                    'optional' => true
                ],
                'integerOptional1' => [
                    'type'     => Identifier::INTEGER,
                    'optional' => true
                ],
                'oid2'  => ['type' => Identifier::OBJECT_IDENTIFIER],
                'integerOptional2' => [
                    'type'     => Identifier::INTEGER,
                    'optional' => true
                ],
            ]
        ];

        $object       = Sequence::create([
            ObjectIdentifier::create('1.2.3'),
            OctetString::createFromString('testString'),
            Integer::create(3),
            ObjectIdentifier::create('1.2.3'),
        ]);
        $mappedObject = Mapper::map($object, $map);

        $this->assertCount(4, $mappedObject);

        $object       = Sequence::create([
            ObjectIdentifier::create('1.2.3'),
            OctetString::createFromString('testString'),
            ObjectIdentifier::create('1.2.3'),
        ]);
        $mappedObject = Mapper::map($object, $map);

        $this->assertCount(3, $mappedObject);

        $object       = Sequence::create([
            ObjectIdentifier::create('1.2.3'),
            ObjectIdentifier::create('1.2.4'),
        ]);
        $mappedObject = Mapper::map($object, $map);

        $this->assertCount(2, $mappedObject);

        $object       = Integer::create(123);
        $mappedObject = Mapper::map($object, $map);

        $this->assertNull($mappedObject, $object);

        $object       = Sequence::create([
            ObjectIdentifier::create('1.2.3'),
        ]);
        $mappedObject = Mapper::map($object, $map);

        $this->assertNull($mappedObject);
    }

    public function testMapSequenceWithAny()
    {
        $map = [
            'type' => Identifier::SEQUENCE,
            'children' => [
                'oid1'  => ['type' => Identifier::ANY],
                'oid2'  => ['type' => Identifier::OBJECT_IDENTIFIER],
            ]
        ];

        $object       = Sequence::create([
            ObjectIdentifier::create('1.2.3'),
            ObjectIdentifier::create('1.2.3.4'),
        ]);
        $mappedObject = Mapper::map($object, $map);
        $this->assertCount(2, $mappedObject);
    }

    public function testSequenceOf()
    {
        $map = [
            'type' => Identifier::SEQUENCE,
            'min'      => 1,
            'max'      => -1,
            'children' => [
                'type'     => Identifier::OBJECT_IDENTIFIER
            ]
        ];

        $object = Sequence::create([
            ObjectIdentifier::create('1.2.3.1'),
            ObjectIdentifier::create('1.2.3.2'),
            ObjectIdentifier::create('1.2.3.3'),
            ObjectIdentifier::create('1.2.3.4'),
        ]);

        $mappedObject = Mapper::map($object, $map);
        $this->assertCount(4, $mappedObject);
    }

    public function testChoice()
    {
        $map = [
            'type' => Identifier::CHOICE,
            'children' => [
                'oid1'  => ['type' => Identifier::INTEGER],
                'oid2'  => ['type' => Identifier::OBJECT_IDENTIFIER],
            ]
        ];

        $object       = Integer::create(1);
        $mappedObject = Mapper::map($object, $map);
        $this->assertNotNull($mappedObject);

        $object       = ObjectIdentifier::create('1.2.3');
        $mappedObject = Mapper::map($object, $map);
        $this->assertNotNull($mappedObject);

        $object       = PrintableString::createFromString('Test string');
        $mappedObject = Mapper::map($object, $map);
        $this->assertNull($mappedObject);
    }

    public function testMapSet()
    {
        $map = [
            'type' => Identifier::SET,
            'children' => [
                'oid1'  => ['type' => Identifier::INTEGER],
                'oid2'  => ['type' => Identifier::OBJECT_IDENTIFIER],
            ]
        ];

        $object       = Set::create([
            Integer::create(1),
            ObjectIdentifier::create('1.2.4'),
        ]);
        $mappedObject = Mapper::map($object, $map);

        $this->assertCount(2, $mappedObject);

        $object       = Set::create([
            ObjectIdentifier::create('1.2.4'),
            Integer::create(1),
        ]);
        $mappedObject = Mapper::map($object, $map);

        $this->assertCount(2, $mappedObject);
    }

    public function testMapSetWithOptional()
    {
        $map = [
            'type' => Identifier::SET,
            'children' => [
                'oid'  => ['type' => Identifier::OBJECT_IDENTIFIER],
                'octetStringOptional' => [
                    'type'     => Identifier::OCTETSTRING,
                    'optional' => true
                ],
                'bool'  => ['type' => Identifier::BOOLEAN],
                'integerOptional' => [
                    'type'     => Identifier::BITSTRING,
                    'optional' => true
                ],
            ]
        ];

        $set = Set::create([
            Boolean::create(true),
            ObjectIdentifier::create('123.2.1'),
        ]);

        $mappedObject = Mapper::map($set, $map);

        $this->assertCount(2, $mappedObject);
    }

    public function testMapExplicitlyTaggedObject()
    {
        $map = [
            'implicit' => true,
            'constant' => 0,
        ] + [
                'type'     => Identifier::SET,
                'children' => [
                    'oid'  => ['type' => Identifier::OBJECT_IDENTIFIER],
                    'bool'  => ['type' => Identifier::BOOLEAN],
                ]
            ];

        $set = Set::create([
            Boolean::create(true),
            ObjectIdentifier::create('123.2.1'),
        ]);

        $taggedObject = ExplicitlyTaggedObject::create(0, $set);
        $mappedObject = Mapper::map($taggedObject, $map);
        $this->assertCount(2, $mappedObject);
    }

    public function testMapCert()
    {
        //$this->markTestIncomplete();
        $map = Certificate::MAP;
        $userCert = base64_decode(file_get_contents(__DIR__ . '/../fixtures/phpnet.crt'));
        $sequence = \FG\ASN1\ASN1Object::fromFile($userCert);
        $mappedObject = Mapper::map($sequence, $map);
        $this->assertNotNull($mappedObject);
    }
}