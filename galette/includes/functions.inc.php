<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Utilities functions
 *
 * PHP version 5
 *
 * Copyright © 2003-2014 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Functions
 * @package   Galette
 *
 * @author    Frédéric Jacquot <unknown@unknow.com>
 * @author    Georges Khaznadar (password encryption, images) <unknown@unknow.com>
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2003-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

if (!defined('GALETTE_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

use Analog\Analog;

/**
 * Check URL validity
 *
 * @param string $url The URL to check
 *
 * @return boolean
 */
function isValidWebUrl($url)
{
    return (preg_match(
        '#^http[s]?\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i',
        $url
    ));
}

/**
 * Custom HTML entitiy decode
 *
 * @param string $given_html  Original HTML
 * @param int    $quote_style Quoting style
 *
 * @return string
 */
function custom_html_entity_decode($given_html, $quote_style = ENT_QUOTES)
{
    $trans_table = array_flip(
        get_html_translation_table(
            HTML_ENTITIES,
            $quote_style
        )
    );
    $trans_table['&#39;'] = "'";
    return strtr($given_html, $trans_table);
}

/**
 * Get a value sent by a form, either in POST and GET arrays
 *
 * @param string $name   property name
 * @param string $defval default rollback value
 *
 * @return string value retrieved from :
 * - GET array if defined and numeric,
 * - POST array if defined and numéric
 * - $defval otherwise
 */
function get_form_value($name, $defval)
{
    $val = $defval;
    if (isset($_GET[$name])) {
        $val = $_GET[$name];
    } elseif (isset($_POST[$name])) {
        $val = $_POST[$name];
    }
    return $val;
}

/**
 * Get a numeric value sent by a form, either in POST and GET arrays
 *
 * @param string $name   property name
 * @param string $defval default rollback value
 *
 * @return numeric value retrieved from :
 * - GET array if defined and numeric,
 * - POST array if defined and numéric
 * - $defval otherwise
 */
function get_numeric_form_value($name, $defval)
{
    $val = get_form_value($name, $defval);
    if (!is_numeric($val)) {
        Analog::log(
            '[get_numeric_form_value] not a numeric value! (value was: `' .
            $val . '`)',
            Analog::INFO
        );
        $val = $defval;
    }
    return $val;
}

/**
 * Get a post numeric value
 *
 * @param string $name   property name
 * @param string $defval default rollback value
 *
 * @return string value retrieved from :
 * - POST array if defined and numéric
 * - $defval otherwise
 */
function get_numeric_posted_value($name, $defval)
{
    if (isset($_POST[$name])) {
        $val = $_POST[$name];
        if (is_numeric($val)) {
            return $val;
        }
    }
    return $defval;
}

if (!function_exists('str_contains')) {
    /**
     * PHP8 str_contains polyfill
     *
     * based on original work from the PHP Laravel framework
     * see https://www.php.net/manual/fr/function.str-contains.php#125977
     *
     * @param string $haystack The string to search in.
     * @param string $needle   The substring to search for in the haystack
     *
     * @return bool
     */
    function str_contains($haystack, $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
