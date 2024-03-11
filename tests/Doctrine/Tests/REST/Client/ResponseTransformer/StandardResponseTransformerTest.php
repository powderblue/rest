<?php

namespace Doctrine\Tests\REST\Client\ResponseTransformer\StandardResponseTransformer;

use Doctrine\REST\Client\Entity;
use Doctrine\REST\Client\EntityConfiguration;
use Doctrine\REST\Client\ResponseTransformer\StandardResponseTransformer;
use PHPUnit\Framework\TestCase;

class StandardResponseTransformerTest extends TestCase
{
    public function testTransformsXmlIntoAnArray()
    {
        $entityConfiguration = new EntityConfiguration(__NAMESPACE__ . '\User');
        $transformer = new StandardResponseTransformer($entityConfiguration);

        $oneRecordXml = <<<END
<?xml version="1.0" encoding="utf-8"?>
<users>
    <id>1</id>
    <username>jwage</username>
</users>
END;

        $this->assertEquals(array(
            'id' => '1',
            'username' => 'jwage',
        ), $transformer->transform($oneRecordXml));

        $this->assertEquals($transformer->transform($oneRecordXml), $transformer->xmlToArray($oneRecordXml));

        $oneInCollectionXml = <<<END
<?xml version="1.0" encoding="utf-8"?>
<users>
    <users>
        <id>1</id>
        <username>jwage</username>
    </users>
</users>
END;

        $this->assertEquals(array(
            'users' => array(
                array(
                    'id' => '1',
                    'username' => 'jwage',
                ),
            ),
        ), $transformer->transform($oneInCollectionXml));

        $this->assertEquals($transformer->transform($oneInCollectionXml), $transformer->xmlToArray($oneInCollectionXml));

        $manyInCollectionXml = <<<END
<?xml version="1.0" encoding="utf-8"?>
<users>
    <users>
        <id>1</id>
        <username>jwage</username>
    </users>
    <users>
        <id>2</id>
        <username>danbettles</username>
    </users>
</users>
END;

        $this->assertEquals(array(
            'users' => array(
                array(
                    'id' => '1',
                    'username' => 'jwage',
                ),
                array(
                    'id' => '2',
                    'username' => 'danbettles',
                ),
            ),
        ), $transformer->transform($manyInCollectionXml));

        $this->assertEquals($transformer->transform($manyInCollectionXml), $transformer->xmlToArray($manyInCollectionXml));
    }
}

// phpcs:disable
class User extends Entity
{
    protected $id;
    protected $username;

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }
}
// phpcs:enable
