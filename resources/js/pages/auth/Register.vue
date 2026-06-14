<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Buat Akun Baru',
        description: 'Lengkapi data Anda di bawah ini untuk mendaftar gratis',
    },
});
</script>

<template>
    <Head title="Daftar Akun" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Nama Lengkap</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Nama lengkap Anda"
                    class="h-11 rounded-xl border-gray-200 dark:border-slate-800 focus:border-blue-500 focus:ring-blue-500/20"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Alamat Email</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="nama@email.com"
                    class="h-11 rounded-xl border-gray-200 dark:border-slate-800 focus:border-blue-500 focus:ring-blue-500/20"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Kata Sandi</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Minimal 8 karakter"
                    class="h-11 rounded-xl border-gray-200 dark:border-slate-800 focus:border-blue-500 focus:ring-blue-500/20"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Konfirmasi Kata Sandi</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Ulangi kata sandi"
                    class="h-11 rounded-xl border-gray-200 dark:border-slate-800 focus:border-blue-500 focus:ring-blue-500/20"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 h-11 w-full rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/20 hover:shadow-xl hover:-translate-y-0.5 transition-all dark:bg-blue-600 dark:hover:bg-blue-500"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Daftar Sekarang
            </Button>
        </div>

        <div class="text-center text-sm text-gray-500 dark:text-gray-400">
            Sudah memiliki akun?
            <TextLink
                :href="login()"
                class="text-blue-600 hover:text-blue-500 font-semibold dark:text-blue-400 dark:hover:text-blue-300"
                :tabindex="6"
                >Masuk</TextLink
            >
        </div>
    </Form>
</template>
