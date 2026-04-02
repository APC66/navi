<?php

function formatPhoneTel(string $phone): string
{
    $cleaned = preg_replace('/[^\d+]/', '', $phone);

    $cleaned = preg_replace('/^(\+33)0/', '$1', $cleaned);

    return $cleaned; // ex: +33600000000
}
