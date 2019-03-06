<?php

use Brain\Monkey;

/**
 * Class UnitCest
 *
 * Based on the tutorial: https://swas.io/blog/wordpress-plugin-unit-test-with-brainmonkey/
 */
class UnitCest
{
    public function _before(UnitTester $I)
    {
        Monkey\setUp();

        // A few common passthrough
        // 1. WordPress i18n functions
        Monkey\Functions\when( '__' )
            ->returnArg( 1 );
        Monkey\Functions\when( '_e' )
            ->returnArg( 1 );
        Monkey\Functions\when( '_n' )
            ->returnArg( 1 );
    }

    public function _after(UnitTester $I = null)
    {
        Monkey\tearDown();
    }
}
