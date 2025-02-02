<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Galette transactions controller
 *
 * PHP version 5
 *
 * Copyright © 2020-2022 The Galette Team
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
 * @category  Controllers
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2020-2022 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.9.4dev - 2020-05-08
 */

namespace Galette\Controllers\Crud;

use Galette\Controllers\CrudController;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Galette\Entity\Adherent;
use Galette\Entity\Contribution;
use Galette\Entity\Transaction;
use Galette\Repository\Contributions;
use Galette\Repository\Members;
use Galette\Repository\Transactions;
use Analog\Analog;

/**
 * Galette transactions controller
 *
 * @category  Controllers
 * @name      TransactionsController
 * @package   Galette
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2020-2022 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.9.4dev - 2020-05-02
 */

class TransactionsController extends ContributionsController
{
    // CRUD - Create

    /**
     * Add page
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     * @param string   $type     Contribution type
     *
     * @return Response
     */
    public function add(Request $request, Response $response, string $type = null): Response
    {
        return $this->edit($request, $response, null, 'add');
    }

    /**
     * Add action
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     * @param string   $type     Contribution type
     *
     * @return Response
     */
    public function doAdd(Request $request, Response $response, string $type = null): Response
    {
        return $this->doEdit($request, $response, $type);
    }

    // /CRUD - Create
    // CRUD - Read

    //ContributionsController manages both lists and filter

    // /CRUD - Read
    // CRUD - Update

    /**
     * Edit page
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     * @param integer  $id       Transaction id
     * @param string   $action   Action
     *
     * @return Response
     */
    public function edit(Request $request, Response $response, int $id = null, $action = 'edit'): Response
    {
        $trans = null;

        if ($this->session->transaction !== null) {
            $trans = $this->session->transaction;
            $this->session->transaction = null;
        } else {
            $trans = new Transaction($this->zdb, $this->login);
        }

        $trans_id = null;
        if ($id !== null) {
            $trans_id = $id;
        }

        $transaction['trans_id'] = $trans_id;
        $transaction['trans_amount'] = get_numeric_form_value("trans_amount", '');
        $transaction['trans_date'] = get_form_value("trans_date", '');
        $transaction['trans_desc'] = get_form_value("trans_desc", '');
        $transaction['id_adh'] = get_numeric_form_value("id_adh", '');

        // flagging required fields
        $required = array(
            'trans_amount'  =>  1,
            'trans_date'    =>  1,
            'trans_desc'    =>  1,
            'id_adh'        =>  1
        );

        if ($action === 'edit') {
            // initialize transactions structure with database values
            $trans->load($trans_id);
            if ($trans->id == '') {
                //not possible to load transaction, exit
                throw new \RuntimeException('Transaction does not exists!');
            }
        }

        // template variable declaration
        $title = _T("Transaction");
        if ($action === 'edit') {
            $title .= ' (' . _T("modification") . ')';
        } else {
            $title .= ' (' . _T("creation") . ')';
        }

        $params = [
            'page_title'        => $title,
            'required'          => $required,
            'data'              => $transaction, //TODO: remove
            'transaction'       => $trans
        ];

        if ($trans->id != '') {
            $contribs = new Contributions($this->zdb, $this->login);
            $params['contribs'] = $contribs->getListFromTransaction($trans->id);
        }

        // members
        $m = new Members();
        $members = $m->getDropdownMembers(
            $this->zdb,
            $this->login,
            $trans->member > 0 ? $trans->member : null
        );

        $params['members'] = [
            'filters'   => $m->getFilters(),
            'count'     => $m->getCount()
        ];
        $params['autocomplete'] = true;

        if (count($members)) {
            $params['members']['list'] = $members;
        }

        // display page
        $this->view->render(
            $response,
            'pages/transaction_form.html.twig',
            $params
        );
        return $response;
    }

    /**
     * Edit action
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     * @param integer  $id       Transaction id
     * @param string   $type     Transaction type
     *
     * @return Response
     */
    public function doEdit(Request $request, Response $response, int $id = null, $type = null): Response
    {
        $post = $request->getParsedBody();
        $trans = new Transaction($this->zdb, $this->login);

        $action = 'add';
        $trans_id = null;
        if ($id !== null) {
            $action = 'edit';
            $trans_id = $id;
        }

        $transaction['trans_id'] = $trans_id;
        $transaction['trans_amount'] = $post['trans_amount'];
        $transaction['trans_date'] = $post['trans_date'];
        $transaction['trans_desc'] = $post['trans_desc'];
        $transaction['id_adh'] = $post['id_adh'];

        // flagging required fields
        $required = array(
            'trans_amount'  =>  1,
            'trans_date'    =>  1,
            'trans_desc'    =>  1,
            'id_adh'        =>  1
        );
        $disabled = array();

        if ($action === 'edit') {
            // initialize transactions structure with database values
            $trans->load($trans_id);
            if ($trans->id == '') {
                //not possible to load transaction, exit
                throw new \RuntimeException('Transaction does not exists!');
            }
        }

        $error_detected = [];
        // regular fields
        $valid = $trans->check($_POST, $required, $disabled);
        if ($valid !== true) {
            $error_detected = array_merge($error_detected, $valid);
        }

        if (count($error_detected) == 0) {
            //all goes well, we can proceed
            $new = false;
            if ($trans->id == '') {
                $new = true;
            }

            $store = $trans->store($this->history);
            if ($store === true) {
                //transaction has been stored :)
                if ($new) {
                    $transaction['trans_id'] = $trans->id;
                }
            } else {
                //something went wrong :'(
                $error_detected[] = _T("An error occurred while storing the transaction.");
            }
        }

        if (count($error_detected) === 0) {
            $files_res = $trans->handleFiles($_FILES);
            if (is_array($files_res)) {
                $error_detected = array_merge($error_detected, $files_res);
            }
        }

        if (count($error_detected) == 0) {
            if (isset($post['contrib_type']) && $trans->getMissingAmount() > 0) {
                $rparams = [
                    'type' => $post['contrib_type']
                ];

                if (isset($trans->member)) {
                    $rparams['id_adh'] = $trans->member;
                }

                return $response
                    ->withStatus(301)
                    ->withHeader(
                        'Location',
                        $this->routeparser->urlFor(
                            'addContribution',
                            $rparams
                        ) . '?' . Transaction::PK . '=' . $trans->id .
                            '&' . Adherent::PK . '=' . $trans->member
                    );
            } else {
                //report success
                $this->flash->addMessage(
                    'success_detected',
                    _T("Transaction has been successfully stored")
                );

                //get back to transactions list
                return $response
                    ->withStatus(301)
                    ->withHeader(
                        'Location',
                        $this->routeparser->urlFor('contributions', ['type' => 'transactions'])
                    );
            }
        } else {
            //something went wrong.
            //store entity in session
            $this->session->transaction = $trans;

            //report errors
            foreach ($error_detected as $error) {
                $this->flash->addMessage(
                    'error_detected',
                    $error
                );
            }

            $args = [];
            if ($trans_id !== null) {
                $args['id'] = $id;
            }
            //redirect to calling action
            return $response
                ->withStatus(301)
                ->withHeader(
                    'Location',
                    $this->routeparser->urlFor(
                        ($action == 'add' ? 'addTransaction' : 'editTransaction'),
                        $args
                    )
                );
        }
    }

    /**
     * Attach action
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     * @param integer  $id       Transaction id
     * @param integer  $cid      Contribution id
     *
     * @return Response
     */
    public function attach(Request $request, Response $response, int $id = null, int $cid = null): Response
    {
        if (!Contribution::setTransactionPart($this->zdb, $id, $cid)) {
            $this->flash->addMessage(
                'error_detected',
                _T("Unable to attach contribution to transaction")
            );
        } else {
            $this->flash->addMessage(
                'success_detected',
                _T("Contribution has been successfully attached to current transaction")
            );
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->routeparser->urlFor(
                'editTransaction',
                ['id' => $id]
            ));
    }

    /**
     * Attach action
     *
     * @param Request  $request  PSR Request
     * @param Response $response PSR Response
     * @param integer  $id       Transaction id
     * @param integer  $cid      Contribution id
     *
     * @return Response
     */
    public function detach(Request $request, Response $response, int $id = null, int $cid = null): Response
    {
        if (!Contribution::unsetTransactionPart($this->zdb, $this->login, $id, $cid)) {
            $this->flash->addMessage(
                'error_detected',
                _T("Unable to detach contribution from transaction")
            );
        } else {
            $this->flash->addMessage(
                'success_detected',
                _T("Contribution has been successfully detached from current transaction")
            );
        }

        return $response
            ->withStatus(301)
            ->withHeader('Location', $this->routeparser->urlFor(
                'editTransaction',
                ['id' => $id]
            ));
    }

    // /CRUD - Update
    // CRUD - Delete

    //all inherited

    // /CRUD - Delete
    // /CRUD
}
