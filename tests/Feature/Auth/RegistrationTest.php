<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

describe('new user can register as', function () {
    test('student', function () {
        $response = $this->post(route('register.store'), [
            'name' => 'Student User',
            'email' => 'student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = auth()->user();
        expect($user->role)->toBe('student');
    });

    test('educator', function () {
        $response = $this->post(route('register.store'), [
            'name' => 'Educator User',
            'email' => 'educator@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'educator',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = auth()->user();
        expect($user->role)->toBe('educator');
    });
});

test('new user register as administrator are forbidden', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'administrator',
    ]);

    $response->assertSessionHasErrors('role');
    $this->assertGuest();
});
