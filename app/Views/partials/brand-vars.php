<?php
// Brand colours are editable in Settings; only validated hex values are ever stored.
$hex = fn (string $key, string $default) => preg_match('/^#[0-9A-Fa-f]{6}$/', setting($key)) ? setting($key) : $default;
?>
<style>:root{--navy:<?= $hex('color_navy', '#14284B') ?>;--red:<?= $hex('color_red', '#C8161D') ?>;--gold:<?= $hex('color_gold', '#C9A13B') ?>}</style>
