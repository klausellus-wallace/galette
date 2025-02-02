<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * L10n tests
 *
 * PHP version 5
 *
 * Copyright © 2020-2023 The Galette Team
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
 * @category  Core
 * @package   GaletteTests
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2020-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      https://galette.eu
 * @since     2020-07-05
 */

namespace Galette\Core\test\units;

use atoum;

/**
 * L10n tests class
 *
 * @category  Core
 * @name      L10n
 * @package   GaletteTests
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2020-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     2020-07-05
 */
class L10n extends atoum
{
    private \Galette\Core\Db $zdb;
    private \Galette\Core\I18n $i18n;
    private \Galette\Core\L10n $l10n;

    /**
     * Set up tests
     *
     * @param string $method Tested method name
     *
     * @return void
     */
    public function beforeTestMethod($method)
    {
        $this->zdb = new \Galette\Core\Db();
        $this->i18n = new \Galette\Core\I18n(
            \Galette\Core\I18n::DEFAULT_LANG
        );
        $this->l10n = new \Galette\Core\L10n(
            $this->zdb,
            $this->i18n
        );
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
        //cleanup dynamic translations
        $delete = $this->zdb->delete(\Galette\Core\L10n::TABLE);
        $delete
            ->where(['text_orig' => ['A text for test', 'Un texte de test']]);
        $this->zdb->execute($delete);
    }

    /**
     * Test add dynamic translation
     *
     * @return void
     */
    public function testAddDynamicTranslation()
    {
        $this->i18n->changeLanguage('en_US');

        $select = $this->zdb->select(\Galette\Core\L10n::TABLE);
        $select->columns([
            'text_locale',
            'text_nref',
            'text_trans'
        ]);
        $select->where(['text_orig' => 'A text for test']);
        $results = $this->zdb->execute($select);
        $this->integer($results->count())->isIdenticalTo(0);

        $this->boolean($this->l10n->addDynamicTranslation('A text for test'))->isTrue();

        $langs = array_keys($this->i18n->getArrayList());

        $results = $this->zdb->execute($select);
        $this->integer($results->count())->isIdenticalTo(count($langs));

        foreach ($results as $result) {
            $this->boolean(in_array(str_replace('.utf8', '', $result['text_locale']), $langs))->isTrue();
            $this->integer((int)$result['text_nref'])->isIdenticalTo(1);
            $this->string($result['text_trans'])->isIdenticalTo(
                ($result['text_locale'] == 'en_US' ? 'A text for test' : '')
            );
        }

        $this->i18n->changeLanguage('fr_FR');

        $select = $this->zdb->select(\Galette\Core\L10n::TABLE);
        $select->columns([
            'text_locale',
            'text_nref',
            'text_trans'
        ]);
        $select->where(['text_orig' => 'Un texte de test']);
        $results = $this->zdb->execute($select);
        $this->integer($results->count())->isIdenticalTo(0);

        $this->boolean($this->l10n->addDynamicTranslation('Un texte de test'))->isTrue();

        $langs = array_keys($this->i18n->getArrayList());

        $results = $this->zdb->execute($select);
        $this->integer($results->count())->isIdenticalTo(count($langs));

        foreach ($results as $result) {
            $this->boolean(in_array(str_replace('.utf8', '', $result['text_locale']), $langs))->isTrue();
            $this->integer((int)$result['text_nref'])->isIdenticalTo(1);
            $this->string($result['text_trans'])->isIdenticalTo(
                ($result['text_locale'] == 'fr_FR.utf8' ? 'Un texte de test' : '')
            );
        }
    }
}
