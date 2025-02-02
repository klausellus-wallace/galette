<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Saved search tests
 *
 * PHP version 5
 *
 * Copyright © 2019-2023 The Galette Team
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
 * @package   GaletteTests
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2019-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     2019-05-08
 */

namespace Galette\Entity\test\units;

use atoum;
use Laminas\Db\Adapter\Adapter;

/**
 * Saved search tests
 *
 * @category  Entity
 * @name      SavedSearch
 * @package   GaletteTests
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2019-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     2019-05-08
 */
class SavedSearch extends atoum
{
    private \Galette\Core\Db $zdb;
    private \Galette\Core\I18n $i18n;
    private \Galette\Core\Login $login;

    /**
     * Set up tests
     *
     * @param string $method Calling method
     *
     * @return void
     */
    public function beforeTestMethod($method)
    {
        $this->zdb = new \Galette\Core\Db();
        $this->i18n = new \Galette\Core\I18n();

        $this->login = new \mock\Galette\Core\Login($this->zdb, $this->i18n);
        $this->calling($this->login)->isLogged = true;
        $this->calling($this->login)->isSuperAdmin = true;
        $this->calling($this->login)->__get = 0; //to get login id
    }

    /**
     * Tear down tests
     *
     * @param string $method Calling method
     *
     * @return void
     */
    public function afterTestMethod($method)
    {
        if (TYPE_DB === 'mysql') {
            $this->array($this->zdb->getWarnings())->isIdenticalTo([]);
        }
        $this->deleteCreated();
    }

    /**
     * Delete status
     *
     * @return void
     */
    private function deleteCreated()
    {
        $this->zdb->db->query(
            'TRUNCATE TABLE ' . PREFIX_DB . \Galette\Entity\SavedSearch::TABLE,
            \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
        );
    }

    /**
     * Test saved search
     *
     * @return void
     */
    public function testSave()
    {
        global $i18n, $translator; // globals :(
        $i18n = $this->i18n;
        $i18n->changeLanguage('en_US');

        $saved = new \Galette\Entity\SavedSearch($this->zdb, $this->login);
        $searches = new \Galette\Repository\SavedSearches($this->zdb, $this->login);

        $post = [
            'parameters'    => [
                'filter_str'        => '',
                'field_filter'      => 0,
                'membership_filter' => 0,
                'filter_account'    => 0,
                'roup_filter'       => 0,
                'email_filter'      => 5,
                'nbshow'            => 10
            ],
            'form'          => 'Adherent',
            'name'          => 'Simple search'
        ];

        $errored = $post;
        unset($errored['form']);
        $this->boolean($saved->check($errored))->isFalse();
        $this->array($saved->getErrors())->isIdenticalTo(['form' => 'Form is mandatory!']);

        //store search
        $this->boolean($saved->check($post))->isTrue();
        $this->boolean($saved->store())->isTrue();
        $this->array($searches->getList(true))->hasSize(1);
        //store again, got a duplicate
        $this->boolean($saved->store())->isTrue();
        $this->array($searches->getList(true))->hasSize(2);
    }
}
