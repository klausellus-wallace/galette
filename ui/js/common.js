/*!
 * Copyright © 2007-2014 The Galette Team
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
 * @category  Javascript
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2007-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2007-10-06
 */

/* Fomantic UI components */
var _bindFomanticComponents = function() {
    if (!("ontouchstart" in document.documentElement) || !("no-touch" in document.documentElement.classList)) {
        document.documentElement.classList.add("no-touch");
    }

    var
        $sidebar         = $('.ui.sidebar'),
        $dropdown        = $('.ui.dropdown, select:not(.nochosen)'),
        $accordion       = $('.ui.accordion'),
        $checkbox        = $('.ui.checkbox, .ui.radio.checkbox'),
        $tabulation      = $('.ui.tabbed .item'),
        $popup           = $('.no-touch a[title], .no-touch .tooltip'),
        $menuPopupRight  = $('.no-touch .ui.vertical.accordion.menu a[title]'),
        $menuPopupBottom = $('.no-touch .ui.top.fixed.menu a.item[title]'),
        $menuPopupLeft   = $('.no-touch .ui.dropdown.right-aligned a[title]')
    ;

    $sidebar.sidebar('attach events', '.toc.item');

    /* Make all dropdowns clickable when js is enabled for UX consistency.
     * Keep them hoverable only when js is disabled.
     */
    $('.simple.dropdown').removeClass('simple');
    $dropdown.dropdown();

    $accordion.accordion();

    $checkbox.checkbox();

    $tabulation.tab();

    /* Fomantic UI Tooltips */
    /* Hide all popups when a dropdown is shown. */
    $.fn.dropdown.settings.onShow = function() {
        $('body').popup('hide all');
    };
    /* Hide all popups when an accordion is opened. */
    $.fn.accordion.settings.onOpening = function() {
        $('body').popup('hide all');
    };
    /* Default behaviour for tooltips on links with a title attribute,
     * or other tags with the "tooltip" class.
     * The title (or data-html) attribute is appended to body and removed
     * from DOM after being hidden (inline: false).
     */
    $popup
        .popup({
            variation: 'inverted',
            inline: false
        })
    ;
    /* Position right on the main accordion menu.
     */
    $menuPopupRight
        .popup({
            position: 'right center',
            variation: 'inverted',
            delay: {
                show: 300
            }
        })
    ;
    /* Position bottom on the top fixed menu.
     */
    $menuPopupBottom
        .popup({
            position: 'center bottom',
            variation: 'inverted'
        })
    ;
    /* Position left on the top right language dropdown menu.
     */
    $menuPopupLeft
        .popup({
            position: 'left center',
            variation: 'inverted',
            delay: {
                show: 300
            }
        })
    ;
}

var _bind_check = function(boxelt){
    if (typeof(boxelt) == 'undefined') {
        boxelt = 'entries_sel'
    }
    var _is_checked = true;
    $('.checkall').click(function(){
        $('table.listing :checkbox[name="' + boxelt + '[]"]').each(function(){
            this.checked = _is_checked;
        });
        _is_checked = !_is_checked;
        return false;
    });
    $('.checkinvert').click(function(){
        var _haschecked = false;
        $('table.listing :checkbox[name="' + boxelt + '[]"]').each(function(){
            if ($(this).is(':checked')) {
                this.checked = false;
            } else {
                this.checked = true;
                _haschecked = true;
            }
        });
        if (!_haschecked) {
            _is_checked = true;
        } else {
            _is_checked = false;
        }
        return false;
    });
};

var _bindNbshow = function(selector) {
    if (typeof(selector) == 'undefined') {
        selector = '';
    } else {
        selector = selector + ' ';
    }

    $(selector + '#nbshow').change(function() {
        $(this.form).trigger('submit');
    });
}

/* Display tables legends in Fomantic UI modal */
var _bind_legend = function() {
    $('.show_legend').click(function(e){
        $('#legende').modal('show');
    });
}

$(function() {
    $('.nojs').removeClass('nojs').addClass('jsenabled');
    /* Display/enable elements required only when javascript is active */
    $('.jsenabled .jsonly.displaynone').removeClass('displaynone');
    $('.jsenabled .jsonly.disabled').removeClass('disabled');
    $('.jsenabled .jsonly.read-only').removeClass('read-only');
    $('.jsenabled .jsonly.search-dropdown').removeClass('search-dropdown').addClass('search selection dropdown');

    $('#login').focus();

    _bindNbshow();

    _bindFomanticComponents();

    if ( $('#back2top').length > 0 ) {
        if (!$('#wrapper').scrollTop() && !$('html').scrollTop() ) {
            $('#back2top').fadeOut();
        }
        $(window).scroll(function() {
            if ($(this).scrollTop()) {
                $('#back2top').fadeIn();
            } else {
                $('#back2top').fadeOut();
            }
        });
    }
});
