<?php

use Brain\Monkey\Functions;
use Codeception\Stub;

class MilestoneCest extends UnitCest
{
    const MILESTONE_CLASS = '\\UpStream\\Milestone';

    public function _before(\UnitTester $I)
    {
        parent::_before($I);
    }

    /**
     * @covers \UpStream\Milestone::__construct
     */
    public function tryToBuildWithValidInteger(\UnitTester $I)
    {
        $expectedId   = 3;
        $expectedPost = new stdClass();

        Functions\expect('get_post')
            ->once()
            ->andReturn($expectedPost);

        $milestone = Stub::construct(self::MILESTONE_CLASS, [$expectedId]);

        $actualId = $this->getProtectedProperty($milestone, 'postId');
        $I->assertEquals($expectedId, $actualId);

        $actuaPost = $this->getProtectedProperty($milestone, 'post');
        $I->assertEquals($expectedPost, $actuaPost);
    }

    /**
     * @covers \UpStream\Milestone::__construct
     */
    public function tryToBuildWithObjectWithValidPostType(\UnitTester $I)
    {
        $expectedId              = 3;
        $expectedPost            = new stdClass();
        $expectedPost->post_type = \UpStream\Milestone::POST_TYPE;
        $expectedPost->ID        = $expectedId;

        Functions\expect('get_post')
            ->never();

        $milestone = Stub::construct(self::MILESTONE_CLASS, [$expectedPost]);

        $actualId = $this->getProtectedProperty($milestone, 'postId');
        $I->assertEquals($expectedId, $actualId);

        $actuaPost = $this->getProtectedProperty($milestone, 'post');
        $I->assertEquals($expectedPost, $actuaPost);
    }

    /**
     * @covers \UpStream\Milestone::__construct
     */
    public function tryToBuildWithInvalidParam(\UnitTester $I)
    {
        $I->expectThrowable(\UpStream\Exception::class, function () {
            Stub::construct(self::MILESTONE_CLASS, [false]);
        });
    }

    /**
     * @covers \UpStream\Milestone::__construct
     */
    public function tryToBuildWithObjectOfWrongClass(\UnitTester $I)
    {
        $I->expectThrowable(\UpStream\Exception::class, function () {
            $mock            = new stdClass();
            $mock->post_type = 'post';
            $mock->ID        = 4;

            Stub::construct(self::MILESTONE_CLASS, [$mock]);
        });
    }

    /**
     * @covers \UpStream\Milestone::getPost
     */
    public function tryToGetPostFromCache(\UnitTester $I)
    {
        $I->amGoingTo('simulate a second call to the getPost method');
        $I->expectTo('get the post without see the get_post method being called');

        $stubPost1     = new stdClass();
        $stubPost1->ID = 1;

        Functions\expect('get_post')
            ->never();

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'postId' => 1,
                'post'   => $stubPost1,
            ]
        );

        $I->assertEquals($stubPost1, $milestone->getPost());
    }

    /**
     * @covers \UpStream\Milestone::getPost
     */
    public function tryToGetPostWithoutCache(\UnitTester $I)
    {
        $I->amGoingTo('call the getPost method for the first time, so we don\'t have a cache.');
        $I->expectTo('see the global function get_post being called and returning the post.');

        $stubPost1     = new stdClass();
        $stubPost1->ID = 1;

        Functions\when('get_post')
            ->justReturn($stubPost1);

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'postId' => 1,
            ]
        );

        $I->assertEquals($stubPost1, $milestone->getPost());
    }

    /**
     * @covers \UpStream\Milestone::getProjectId
     */
    public function tryToGetProjectIdFromAttribute(\UnitTester $I)
    {
        $I->amGoingTo('call the getProjectId method after the projectId attribute was set.');
        $I->expectTo('receive the cached ID, not a null value, and the get_post_meta function should not be called.');

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'projectId' => 2,
            ]
        );

        Functions\expect('get_post_meta')
            ->never();

        $I->assertEquals(2, $milestone->getProjectId());
    }

    /**
     * @covers \UpStream\Milestone::getProjectId
     */
    public function tryToGetProjectIdFromMetadata(\UnitTester $I)
    {
        $I->amGoingTo('call the getProjecId method before the projectId attribute is set.');
        $I->expectTo('receive the project ID found in the meta data.');

        $milestone = Stub::make(
            self::MILESTONE_CLASS
        );

        Functions\expect('get_post_meta')
            ->once()
            ->andReturn(4);

        $I->assertEquals(4, $milestone->getProjectId());
    }

    /**
     * @covers \UpStream\Milestone::getProjectId
     */
    public function tryToSetProjectIdWithValidInteger(\UnitTester $I)
    {
        $I->expect('to see the update_post_meta function called and the projectId attribute correctly set');

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'projectId' => 2,
            ]
        );

        Functions\expect('update_post_meta')
            ->once();
        Functions\expect('get_post_meta')
            ->never();

        $milestone->setProjectId(4);

        $I->assertEquals(4, $milestone->getProjectId());
    }

    /**
     * @covers \UpStream\Milestone::getProjectId
     */
    public function tryToSetProjectIdWithInvalidInteger(\UnitTester $I)
    {
        $I->expect('to see the projectId argument sanitized to integer and properly stored in the metadata');

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'projectId' => 2,
            ]
        );

        Functions\expect('update_post_meta')
            ->once();
        Functions\expect('get_post_meta')
            ->never();

        $milestone->setProjectId('not-valid-integer');

        $I->assertEquals(0, $milestone->getProjectId());
    }

    /**
     * @covers \UpStream\Milestone::getAssignedTo
     */
    public function tryToGetTheAssignedToPropertyFromMetadata(\UnitTester $I)
    {
        $I->expect('to get the data stored in the metadata');

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'projectId'  => 2,
                'assignedTo' => null,
            ]
        );

        $expected = [4, 6];

        Functions\expect('get_post_meta')
            ->once()
            ->andReturn($expected);

        $actual = $milestone->getAssignedTo();

        $I->assertInternalType('array', $actual);
        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getAssignedTo
     */
    public function tryToGetTheAssignedToPropertyFromCache(\UnitTester $I)
    {
        $I->expect('to get the data stored in the class\'s attribute. The get_post_meta should not be called.');

        $expected = [3, 6];

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'projectId'  => 2,
                'assignedTo' => $expected,
            ]
        );

        Functions\expect('get_post_meta')
            ->never();

        $actual = $milestone->getAssignedTo();

        $I->assertInternalType('array', $actual);
        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getAssignedTo
     */
    public function tryToSetTheAssignedToPropertyWithValidArrayOfIntegers(\UnitTester $I)
    {
        $I->expect('to see the delete_post_meta and add_post_meta functions being called and the class attribute being set');

        $expected = [3, 6];

        // Mock the instance with a no expected value in the assignedTo attribute.
        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'projectId'  => 2,
                'assignedTo' => [5],
            ]
        );

        Functions\expect('delete_post_meta')
            ->once();
        Functions\expect('add_post_meta')
            ->atLeast();
        Functions\expect('get_post_meta')
            ->never();

        $milestone->setAssignedTo($expected);

        $actual = $this->getProtectedProperty($milestone, 'assignedTo');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getAssignedTo
     */
    public function tryToSetTheAssignedToPropertyWithInvalidParameter(\UnitTester $I)
    {
        $I->expect('to see the parameter being sanitized to an empty array and the data being updated.');

        $expected = [];

        // Mock the instance with a no expected value in the assignedTo attribute.
        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'projectId'  => 2,
                'assignedTo' => [5],
            ]
        );

        Functions\expect('delete_post_meta')
            ->once();
        Functions\expect('add_post_meta')
            ->atLeast();
        Functions\expect('get_post_meta')
            ->never();

        $milestone->setAssignedTo(0);

        $actual = $this->getProtectedProperty($milestone, 'assignedTo');

        $I->assertInternalType('array', $actual);
        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getAssignedTo
     */
    public function tryToSetTheAssignedToPropertyWithInvalidItemInTheArray(\UnitTester $I)
    {
        $I->expect('to see the parameter being sanitized to a list of valid integers with no zeros and the data being updated.');

        $expected = [4];

        // Mock the instance with a no expected value in the assignedTo attribute.
        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'projectId'  => 2,
                'assignedTo' => [5, 7, 3],
            ]
        );

        Functions\expect('delete_post_meta')
            ->once();
        Functions\expect('add_post_meta')
            ->atLeast();
        Functions\expect('get_post_meta')
            ->never();

        $milestone->setAssignedTo(['not-valid-integer', 4]);

        $actual = $this->getProtectedProperty($milestone, 'assignedTo');

        $I->assertInternalType('array', $actual);
        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getStartDate
     */
    public function tryToGetStartDateFromCache(\UnitTester $I)
    {
        $I->expect('to get the cached value from the class\'s attribute, without calling the get_post_meta function.');

        $expected = '2019-01-01';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'startDate' => $expected,
            ]
        );

        Functions\expect('get_post_meta')
            ->never();

        $actual = $milestone->getStartDate();

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getStartDate
     */
    public function tryToGetStartDateFromAsMySQLFormat(\UnitTester $I)
    {
        $I->expect('to get the date in the MySQL date format');

        $expected = '2019-01-01';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'startDate' => $expected,
            ]
        );

        Functions\expect('get_post_meta')
            ->never();

        // Test as the default format.
        $actual = $milestone->getStartDate();
        $I->assertEquals($expected, $actual);

        $actual = $milestone->getStartDate('mysql');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getStartDate
     */
    public function tryToGetStartDateFromAsUnixTimeFormat(\UnitTester $I)
    {
        $I->expect('to get the date in the Unix Time date format');

        $expected = 1546300800;

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'startDate' => '2019-01-01',
            ]
        );

        $actual = $milestone->getStartDate('unix');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getStartDate
     */
    public function tryToGetStartDateFromUpStreamFormat(\UnitTester $I)
    {
        $I->expect('to get the date in the UpStream date format.');

        $expected = 'Jan 01 st, 2019';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'startDate' => '2019-01-01',
            ]
        );

        Functions\expect('upstream_format_date')
            ->once()
            ->andReturn($expected);

        $actual = $milestone->getStartDate('upstream');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getEndDate
     */
    public function tryToGetEndDateFromCache(\UnitTester $I)
    {
        $I->expect('to get the cached value from the class\'s attribute, without calling the get_post_meta function.');

        $expected = '2019-01-01';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'endDate' => $expected,
            ]
        );

        Functions\expect('get_post_meta')
            ->never();

        $actual = $milestone->getEndDate();

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getEndDate
     */
    public function tryToGetEndDateFromAsMySQLFormat(\UnitTester $I)
    {
        $I->expect('to get the date in the MySQL date format');

        $expected = '2019-01-01';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'endDate' => $expected,
            ]
        );

        Functions\expect('get_post_meta')
            ->never();

        // Test as the default format.
        $actual = $milestone->getEndDate();
        $I->assertEquals($expected, $actual);

        $actual = $milestone->getEndDate('mysql');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getEndDate
     */
    public function tryToGetEndDateFromAsUnixTimeFormat(\UnitTester $I)
    {
        $I->expect('to get the date in the Unix Time date format');

        $expected = 1546300800;

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'endDate' => '2019-01-01',
            ]
        );

        $actual = $milestone->getEndDate('unix');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getEndDate
     */
    public function tryToGetEndDateFromUpStreamFormat(\UnitTester $I)
    {
        $I->expect('to get the date in the UpStream date format.');

        $expected = 'Jan 01 st, 2019';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'endDate' => '2019-01-01',
            ]
        );

        Functions\expect('upstream_format_date')
            ->once()
            ->andReturn($expected);

        $actual = $milestone->getEndDate('upstream');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::setStartDate
     */
    public function tryToSetStartDateFromMySQLFormat(\UnitTester $I)
    {
        $I->expect('to see the start date saved in the MySQL format');

        $expected = '2019-01-01';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'startDate' => null,
            ]
        );

        Functions\expect('update_post_meta')
            ->once();
        Functions\expect('get_post_meta')
            ->never();
        Functions\expect('upstream_date_unixtime')
            ->never();

        $milestone->setStartDate($expected);

        $actual = $this->getProtectedProperty($milestone, 'startDate');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::setStartDate
     */
    public function tryToSetStartDateFromUnixTimeFormat(\UnitTester $I)
    {
        $I->expect('to see the start date saved in the MySQL format');

        $expected = '2019-01-01';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'startDate' => null,
            ]
        );

        Functions\expect('update_post_meta')
            ->once();
        Functions\expect('get_post_meta')
            ->never();
        Functions\expect('upstream_date_unixtime')
            ->never();

        $milestone->setStartDate('1546300800');

        $actual = $this->getProtectedProperty($milestone, 'startDate');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::setStartDate
     */
    public function tryToSetStartDateFromUpStreamFormat(\UnitTester $I)
    {
        $I->expect('to see the start date saved in the MySQL format');

        $expected = '2019-01-01';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'startDate' => null,
            ]
        );

        Functions\expect('update_post_meta')
            ->once();
        Functions\expect('get_post_meta')
            ->never();
        Functions\expect('upstream_format_date')
            ->never();
        Functions\expect('upstream_date_unixtime')
            ->once()
            ->andReturn('1546300800');

        $milestone->setStartDate('Jan 01st, 2019');

        $actual = $this->getProtectedProperty($milestone, 'startDate');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::setEndDate
     */
    public function tryToSetEndDateFromMySQLFormat(\UnitTester $I)
    {
        $I->expect('to see the start date saved in the MySQL format');

        $expected = '2019-01-01';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'endDate' => null,
            ]
        );

        Functions\expect('update_post_meta')
            ->once();
        Functions\expect('get_post_meta')
            ->never();
        Functions\expect('upstream_date_unixtime')
            ->never();

        $milestone->setEndDate($expected);

        $actual = $this->getProtectedProperty($milestone, 'endDate');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::setEndDate
     */
    public function tryToSetEndDateFromUnixTimeFormat(\UnitTester $I)
    {
        $I->expect('to see the start date saved in the MySQL format');

        $expected = '2019-01-01';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'endDate' => null,
            ]
        );

        Functions\expect('update_post_meta')
            ->once();
        Functions\expect('get_post_meta')
            ->never();
        Functions\expect('upstream_date_unixtime')
            ->never();

        $milestone->setEndDate('1546300800');

        $actual = $this->getProtectedProperty($milestone, 'endDate');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::setEndDate
     */
    public function tryToSetEndDateFromUpStreamFormat(\UnitTester $I)
    {
        $I->expect('to see the start date saved in the MySQL format');

        $expected = '2019-01-01';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'endDate' => null,
            ]
        );

        Functions\expect('update_post_meta')
            ->once();
        Functions\expect('get_post_meta')
            ->never();
        Functions\expect('upstream_format_date')
            ->never();
        Functions\expect('upstream_date_unixtime')
            ->once()
            ->andReturn('1546300800');

        $milestone->setEndDate('Jan 01st, 2019');

        $actual = $this->getProtectedProperty($milestone, 'endDate');

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getNotes
     */
    public function tryToGetNotesFromCache(\UnitTester $I)
    {
        $expected = 'This is the note we want to see.';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'notes' => $expected,
            ]
        );

        Functions\expect('get_post_meta')
            ->never();

        $actual = $milestone->getNotes();

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::getNotes
     */
    public function tryToGetNotesFromPostContent(\UnitTester $I)
    {
        $expected = 'This is the note we want to see.';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'notes'   => null,
                'getPost' => function () use ($expected) {
                    $post               = new stdClass();
                    $post->post_content = $expected;

                    return $post;
                },
            ]
        );

        Functions\expect('get_post_meta')
            ->never();

        $actual = $milestone->getNotes();

        $I->assertEquals($expected, $actual);
    }

    /**
     * @covers \UpStream\Milestone::setNotes
     */
    public function tryToSetNotes(\UnitTester $I)
    {
        $expected = 'This is the note we want to see.';

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'notes'                 => 'Not what we want to see.',
                'getPost'               => function () use ($expected) {
                    $post               = new stdClass();
                    $post->post_content = $expected;

                    return $post;
                },
                'getMilestonesInstance' => function () {
                    return new stdClass();
                },
            ]
        );

        Functions\expect('remove_action')
            ->once();
        Functions\expect('add_action')
            ->once();
        Functions\expect('wp_update_post')
            ->once();
        Functions\expect('get_post_meta')
            ->never();

        $milestone->setNotes($expected);

        $actual = $this->getProtectedProperty($milestone, 'notes');
        $I->assertEquals($expected, $actual, 'The protected attribute was set.');
    }

    public function tryToGetOrderFromCache(\UnitTester $I)
    {
        $expected = 3;

        $milestone = Stub::make(
            self::MILESTONE_CLASS,
            [
                'notes'                 => 'Not what we want to see.',
                'getPost'               => function () use ($expected) {
                    $post               = new stdClass();
                    $post->post_content = $expected;

                    return $post;
                },
                'getMilestonesInstance' => function () {
                    return new stdClass();
                },
            ]
        );
    }

    public function tryToGetOrderFromMetadata(\UnitTester $I)
    {
        return $I->fail('Not implemented');
    }

    public function tryToSetSpecificPosition(\UnitTester $I)
    {
        return $I->fail('Not implemented');
    }

    public function tryToCreateMilestoneAndUpdateOrder(\UnitTester $I)
    {
        return $I->fail('Not implemented');
    }
}
