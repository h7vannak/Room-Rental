<?php
function money($amount) {
    global $system;
    return $system['currency_symbol'] . number_format($amount, 2);
}
