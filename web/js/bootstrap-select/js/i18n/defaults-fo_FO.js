/*
 * Translated default messages for bootstrap-select.
 * Locale: fo (Faroese)
 * Region: FO (Faroe Islands)
 * File:  bootstrap-select/js/i18n/defaults-fo_FO.js
 * Author: Torkil Zachariassen
 * Date: 2017-02-04
 * Source: bootstrap-select/js/i18n/defaults-en_US.js
 */
(function ($) {
  $.fn.selectpicker.defaults = {
    noneSelectedText: 'Einki valt',
    noneResultsText: 'Eingi úrslit samsvara {0}',
    countSelectedText: function (numSelected, numTotal) {
      return (numSelected == 1) ? "{0} eind vald" : "{0} eindir valdar";
    },
    maxOptionsText: function (numAll, numGroup) {
      return [
        (numAll == 1) ? 'Avmarking rokkin (max {n} val)' : 'Avmarking rokkin (max {n} val)',
        (numGroup == 1) ? 'Bólka-avmarking (max {n} val)' : 'Bólka-avmarking rokkint (max {n} val)'
      ];
    },
    selectAllText: 'Vel øll',
    deselectAllText: 'Frável øll',
    multipleSeparator: ', '
  };
})(jQuery);
