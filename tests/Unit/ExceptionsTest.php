<?php

use Ashraam\LaravelSimpleCart\Exceptions\InvalidPrice;
use Ashraam\LaravelSimpleCart\Exceptions\InvalidQuantity;

test('InvalidPrice extends RuntimeException', function () {
    $exception = new InvalidPrice('Test message');
    
    expect($exception)
        ->toBeInstanceOf(RuntimeException::class)
        ->and($exception->getMessage())
        ->toBe('Test message');
});

test('InvalidQuantity extends RuntimeException', function () {
    $exception = new InvalidQuantity('Test message');
    
    expect($exception)
        ->toBeInstanceOf(RuntimeException::class)
        ->and($exception->getMessage())
        ->toBe('Test message');
});

test('InvalidPrice can be thrown and caught', function () {
    try {
        throw new InvalidPrice('Invalid price error');
    } catch (InvalidPrice $e) {
        expect($e->getMessage())->toBe('Invalid price error');
    }
});

test('InvalidQuantity can be thrown and caught', function () {
    try {
        throw new InvalidQuantity('Invalid quantity error');
    } catch (InvalidQuantity $e) {
        expect($e->getMessage())->toBe('Invalid quantity error');
    }
});
