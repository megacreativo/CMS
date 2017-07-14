<?php

/**
 * OtherPrimeInfo
 *
 * PHP version 5
 *
 * @category  File
 * @package   ASN1
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace Adapik\CMS\Maps;

use FG\ASN1\Identifier;

/**
 * OtherPrimeInfo
 *
 * @package ASN1
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
abstract class OtherPrimeInfo
{
    // version must be multi if otherPrimeInfos present
    const MAP = [
        'type' => Identifier::SEQUENCE,
        'children' => [
            'prime' =>       ['type' => Identifier::INTEGER], // ri
            'exponent' =>    ['type' => Identifier::INTEGER], // di
            'coefficient' => ['type' => Identifier::INTEGER]  // ti
        ]
    ];
}