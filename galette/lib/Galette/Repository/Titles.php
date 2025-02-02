<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Titles repository management
 *
 * PHP version 5
 *
 * Copyright © 2009-2014 The Galette Team
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
 * @category  Entity
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-03-04
 */

namespace Galette\Repository;

use Galette\Core\Db;
use Throwable;
use Galette\Entity\Title;
use Analog\Analog;

/**
 * Titles repository management
 *
 * @category  Entity
 * @name      Titles
 * @package   Galette
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-03-04
 */

class Titles
{
    public const TABLE = 'titles';
    public const PK = 'id_title';

    public const MR = 1;
    public const MRS = 2;
    public const MISS = 3;

    private static $defaults = array(
        array(
            'id_title'      => 1,
            'short_label'   => 'Mr.',
            'long_label'    => null
        ),
        array(
            'id_title'      => 2,
            'short_label'   => 'Mrs.',
            'long_label'    => null
        )
    );

    /**
     * Get the list of all titles as an array
     *
     * @param Db $zdb Database instance
     *
     * @return array
     */
    public static function getArrayList(Db $zdb)
    {
        $otitles = self::getList($zdb);
        $titles = array();
        foreach ($otitles as $t) {
            $titles[$t->id] = $t->short;
        }
        return $titles;
    }

    /**
     * Get the list of all titles
     *
     * @param Db $zdb Database instance
     *
     * @return Title[]
     */
    public static function getList(Db $zdb)
    {
        $select = $zdb->select(self::TABLE);
        $select->order(self::PK);

        $results = $zdb->execute($select);

        $pols = array();
        foreach ($results as $r) {
            $pk = self::PK;
            $pols[$r->$pk] = new Title($r);
        }
        return $pols;
    }


    /**
     * Set default titles at install time
     *
     * @param Db $zdb Database instance
     *
     * @return boolean
     * @throws Throwable
     */
    public function installInit(Db $zdb)
    {
        try {
            //first, we drop all values
            $delete = $zdb->delete(self::TABLE);
            $zdb->execute($delete);

            $insert = $zdb->insert(self::TABLE);
            $insert->values(
                array(
                    'id_title'      => ':id',
                    'short_label'   => ':short',
                    'long_label'    => ':long'
                )
            );
            $stmt = $zdb->sql->prepareStatementForSqlObject($insert);

            $zdb->handleSequence(
                self::TABLE,
                count(self::$defaults)
            );

            foreach (self::$defaults as $d) {
                $short = _T($d['short_label']);
                $long = null;
                if ($d['long_label'] !== null) {
                    $long = _T($d['long_label']);
                }
                $stmt->execute(
                    array(
                        'id'    => $d['id_title'],
                        'short' => $short,
                        'long'  => $long
                    )
                );
            }

            Analog::log(
                'Default titles were successfully stored into database.',
                Analog::INFO
            );
            return true;
        } catch (Throwable $e) {
            Analog::log(
                'Unable to initialize default titles. ' . $e->getMessage(),
                Analog::WARNING
            );
            throw $e;
        }
    }

    /**
     * Get translated title short version
     *
     * @param integer $title The title id to retrieve
     *
     * @return string
     */
    public static function getTitle($title)
    {
        global $zdb;

        $select = $zdb->select(self::TABLE);
        $select->limit(1)
            ->where(array(self::PK => $title));

        $results = $zdb->execute($select);
        $result = $results->current();
        $res = $result->short_label;
        return _T($res);
    }
}
