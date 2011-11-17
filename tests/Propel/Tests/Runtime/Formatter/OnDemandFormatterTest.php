<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\Formatter;

use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookPeer;

use Propel\Runtime\Propel;
use Propel\Runtime\Configuration;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\OnDemandCollection;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Formatter\OnDemandFormatter;
use Propel\Runtime\Query\ModelCriteria;

/**
 * Test class for OnDemandFormatter.
 *
 * @author     Francois Zaninotto
 * @version    $Id: OnDemandFormatterTest.php 1374 2009-12-26 23:21:37Z francois $
 * @package    runtime.formatter
 */
class OnDemandFormatterTest extends BookstoreEmptyTestBase
{

    public function testFormatNoCriteria()
    {
        $con = Configuration::getInstance()->getConnection(BookPeer::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new OnDemandFormatter();
        try {
            $books = $formatter->format($stmt);
            $this->fail('OnDemandFormatter::format() trows an exception when called with no valid criteria');
        } catch (PropelException $e) {
            $this->assertTrue(true,'OnDemandFormatter::format() trows an exception when called with no valid criteria');
        }
    }

    public function testFormatManyResults()
    {
        $con = Configuration::getInstance()->getConnection(BookPeer::DATABASE_NAME);
        BookstoreDataPopulator::populate($con);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new OnDemandFormatter();
        $formatter->init(new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertTrue($books instanceof OnDemandCollection, 'OnDemandFormatter::format() returns a PropelOnDemandCollection');
        $this->assertEquals(4, count($books), 'OnDemandFormatter::format() returns a collection that counts as many rows as the results in the query');
        foreach ($books as $book) {
            $this->assertTrue($book instanceof Book, 'OnDemandFormatter::format() returns an traversable collection of Model objects');
        }
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testFormatManyResultsIteratedTwice()
    {
        $con = Configuration::getInstance()->getConnection(BookPeer::DATABASE_NAME);
        BookstoreDataPopulator::populate($con);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new OnDemandFormatter();
        $formatter->init(new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        foreach ($books as $book) {
            // do nothing
        }
        foreach ($books as $book) {
            // this should throw a \Propel\Runtime\Exception\PropelException since we're iterating a second time over a stream
        }
    }

    public function testFormatALotOfResults()
    {
        $nbBooks = 50;
        $con = Configuration::getInstance()->getConnection(BookPeer::DATABASE_NAME);
        Propel::disableInstancePooling();
        $book = new Book();
        for ($i=0; $i < $nbBooks; $i++) {
            $book->clear();
            $book->setTitle('BookTest' . $i);
            $book->save($con);
        }

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new OnDemandFormatter();
        $formatter->init(new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertTrue($books instanceof OnDemandCollection, 'OnDemandFormatter::format() returns a PropelOnDemandCollection');
        $this->assertEquals($nbBooks, count($books), 'OnDemandFormatter::format() returns a collection that counts as many rows as the results in the query');
        $i = 0;
        foreach ($books as $book) {
            $this->assertTrue($book instanceof Book, 'OnDemandFormatter::format() returns a collection of Model objects');
            $this->assertEquals('BookTest' . $i, $book->getTitle(), 'OnDemandFormatter::format() returns the model objects matching the query');
            $i++;
        }
        Propel::enableInstancePooling();
    }


    public function testFormatOneResult()
    {
        $con = Configuration::getInstance()->getConnection(BookPeer::DATABASE_NAME);
        BookstoreDataPopulator::populate($con);

        $stmt = $con->query('SELECT * FROM book WHERE book.TITLE = "Quicksilver"');
        $formatter = new OnDemandFormatter();
        $formatter->init(new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertTrue($books instanceof OnDemandCollection, 'OnDemandFormatter::format() returns a PropelOnDemandCollection');
        $this->assertEquals(1, count($books), 'OnDemandFormatter::format() returns a collection that counts as many rows as the results in the query');
        foreach ($books as $book) {
            $this->assertTrue($book instanceof Book, 'OnDemandFormatter::format() returns a collection of Model objects');
            $this->assertEquals('Quicksilver', $book->getTitle(), 'OnDemandFormatter::format() returns the model objects matching the query');
        }
    }

    public function testFormatNoResult()
    {
        $con = Configuration::getInstance()->getConnection(BookPeer::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book WHERE book.TITLE = "foo"');
        $formatter = new OnDemandFormatter();
        $formatter->init(new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertTrue($books instanceof OnDemandCollection, 'OnDemandFormatter::format() returns a Collection');
        $this->assertEquals(0, count($books), 'OnDemandFormatter::format() returns an empty collection when no record match the query');
        foreach ($books as $book) {
            $this->fail('OnDemandFormatter returns an empty iterator when no record match the query');
        }
    }

    public function testFormatOneManyResults()
    {
        $con = Configuration::getInstance()->getConnection(BookPeer::DATABASE_NAME);
        BookstoreDataPopulator::populate($con);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new OnDemandFormatter();
        $formatter->init(new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book'));
        $book = $formatter->formatOne($stmt);

        $this->assertTrue($book instanceof Book, 'OnDemandFormatter::formatOne() returns a model object');
    }

}
