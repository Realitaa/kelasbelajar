<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Users,
    Clock,
    Compass,
    ChevronDown,
    Menu,
    X,
    Check,
    Sparkles,
} from '@lucide/vue';
import { useBrowserLocation } from '@vueuse/core';
import { ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { login, register, dashboard } from '@/routes';
import { index } from '@/routes/classrooms';

const location = useBrowserLocation();

// Mobile menu toggle
const mobileMenuOpen = ref(false);

// FAQ active state (supports multiple active items)
const activeFaqIndex = ref<number | null>(null);

const toggleFaq = (index: number) => {
    activeFaqIndex.value = activeFaqIndex.value === index ? null : index;
};

// Navigation items
const navLinks = [
    { name: 'Beranda', href: '#beranda' },
    { name: 'Mentor', href: '#mentor' },
    { name: 'Tentang', href: '#tentang' },
    { name: 'Blog', href: '#blog' },
];

// Why Choose Us features
const features = [
    {
        title: 'Belajar Fleksibel',
        description:
            'Akses seluruh materi pembelajaran kapan saja dan di mana saja sesuai kecepatan belajar Anda.',
        Icon: Clock,
        bgClass:
            'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400',
    },
    {
        title: 'Mentor Profesional',
        description:
            'Dipandu langsung oleh praktisi berpengalaman dan ahli industri terkemuka.',
        Icon: Users,
        bgClass:
            'bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400',
    },
    {
        title: 'Komunitas Aktif',
        description:
            'Diskusikan materi dan berkolaborasi bersama ribuan siswa berbakat lainnya.',
        Icon: Compass,
        bgClass:
            'bg-orange-50 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400',
    },
];

// Learning Experience checklist
const experienceChecklist = [
    'Video Pembelajaran Kualitas HD',
    'Quiz Interaktif & Evaluasi Materi',
    'Progress Tracking Terintegrasi',
    'Forum Diskusi Aktif Bersama Mentor',
];

// FAQs
const faqs = [
    {
        question: 'Bagaimana cara mendaftar?',
        answer: 'Sangat mudah! Klik tombol "Daftar Gratis" di sudut kanan atas halaman ini. Cukup masukkan nama, email, dan password Anda untuk langsung mulai belajar.',
    },
    {
        question: 'Apakah bisa diakses dari HP?',
        answer: 'Tentu saja. Platform KelasBelajar didesain sepenuhnya responsif dan dioptimalkan secara modern untuk perangkat mobile, tablet, maupun desktop.',
    },
    {
        question: 'Apakah materi dapat diakses selamanya?',
        answer: 'Ya, kelas yang terdaftar menjadi milik Anda secara permanen (Lifetime Access), sehingga Anda dapat mengulang pembelajaran kapan saja di kemudian hari.',
    },
    {
        question: 'Apakah tersedia mentor?',
        answer: 'Ya! Setiap kelas premium didampingi langsung oleh mentor ahli. Anda dapat mengirimkan pertanyaan melalui forum diskusi, dan mentor kami akan merespons dengan cepat.',
    },
];
</script>

<template>
    <Head title="KelasBelajar - Platform Belajar Online Modern">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="min-h-screen bg-[#F8FAFF] font-sans text-[#111827] antialiased transition-colors duration-300 dark:bg-[#0F172A] dark:text-[#F8FAFC]"
    >
        <!-- NAVBAR -->
        <header
            class="sticky top-0 z-50 h-20 border-b border-gray-200/60 bg-white/80 backdrop-blur-md transition-colors duration-300 dark:border-slate-800/60 dark:bg-[#0F172A]/80"
        >
            <div
                class="mx-auto flex h-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <AppLogo :icon-only="true" />
                    <span
                        class="text-xl font-bold tracking-tight text-[#111827] dark:text-white"
                        >Kelas<span class="text-blue-600">Belajar</span></span
                    >
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden items-center gap-8 md:flex">
                    <a
                        v-for="link in navLinks"
                        :key="link.name"
                        :href="link.href"
                        class="text-sm font-medium text-gray-600 transition-colors hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                    >
                        {{ link.name }}
                    </a>
                </nav>

                <!-- Actions / Auth Buttons & Theme Switcher -->
                <div class="hidden items-center gap-4 md:flex">
                    <!-- Authenticated Link / Guest Buttons -->
                    <Link
                        v-if="$page.props.auth.user"
                        :href="
                            $page.props.auth.user.role == 'administrator'
                                ? dashboard()
                                : index()
                        "
                        class="flex h-10 items-center justify-center rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-md shadow-blue-500/20 transition-all hover:bg-blue-700 hover:shadow-lg"
                    >
                        {{
                            $page.props.auth.user.role == 'administrator'
                                ? 'Dashboard'
                                : 'Kelas'
                        }}
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="flex h-10 items-center justify-center rounded-lg px-4 text-sm font-semibold text-gray-700 transition-colors hover:text-blue-600 dark:text-gray-200 dark:hover:text-blue-400"
                        >
                            Masuk
                        </Link>
                        <Link
                            :href="register()"
                            class="flex h-10 items-center justify-center rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-md shadow-blue-500/10 transition-all hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
                        >
                            Daftar Gratis
                        </Link>
                    </template>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center gap-2 md:hidden">
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-300"
                    >
                        <Menu v-if="!mobileMenuOpen" class="h-5 w-5" />
                        <X v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div
                v-show="mobileMenuOpen"
                class="border-b border-gray-200 bg-white px-4 py-4 shadow-lg transition-all duration-200 md:hidden dark:border-slate-800 dark:bg-[#0F172A]"
            >
                <nav class="mb-6 flex flex-col gap-4">
                    <a
                        v-for="link in navLinks"
                        :key="link.name"
                        :href="link.href"
                        @click="mobileMenuOpen = false"
                        class="text-base font-medium text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                    >
                        {{ link.name }}
                    </a>
                </nav>
                <div class="flex flex-col gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="flex h-11 items-center justify-center rounded-lg bg-blue-600 text-sm font-semibold text-white shadow-md"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="flex h-11 items-center justify-center rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 dark:border-slate-800 dark:text-gray-300"
                        >
                            Masuk
                        </Link>
                        <Link
                            :href="register()"
                            class="flex h-11 items-center justify-center rounded-lg bg-blue-600 text-sm font-semibold text-white shadow-md"
                        >
                            Daftar Gratis
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- HERO SECTION -->
        <section
            id="beranda"
            class="relative flex min-h-[850px] items-center overflow-hidden pt-12 pb-20 md:pt-20 md:pb-28"
        >
            <!-- Background Orbs -->
            <div
                class="pointer-events-none absolute top-1/4 left-1/2 -z-10 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-500/10 blur-[120px] dark:bg-blue-600/15"
            ></div>

            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12"
                >
                    <!-- Left Hero Content -->
                    <div class="flex flex-col justify-center lg:col-span-5">
                        <div
                            class="mb-6 inline-flex w-fit items-center gap-1.5 rounded-full border border-blue-100 bg-blue-50/50 px-3.5 py-1.5 text-xs font-semibold text-blue-600 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-400"
                        >
                            <span>🚀 Platform Belajar Online Modern</span>
                        </div>
                        <h1
                            class="mb-6 text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-[64px] lg:leading-[72px] dark:text-white"
                        >
                            Belajar Lebih Cerdas Bersama
                            <span
                                class="bg-linear-to-r from-blue-600 to-[#7D9CFF] bg-clip-text text-transparent dark:from-blue-400 dark:to-blue-200"
                                >KelasBelajar</span
                            >
                        </h1>
                        <p
                            class="mb-8 max-w-lg text-lg leading-relaxed text-gray-600 dark:text-gray-300"
                        >
                            Akses ratusan materi pembelajaran, video interaktif,
                            kuis, dan sertifikat digital dalam satu platform
                            yang dapat diakses kapan saja dan di mana saja.
                        </p>

                        <!-- Buttons: Stacked on Mobile -->
                        <div class="mb-12 flex flex-col gap-4 sm:flex-row">
                            <Link
                                :href="register()"
                                class="flex h-12 items-center justify-center rounded-xl bg-blue-600 px-8 text-base font-semibold text-white shadow-lg shadow-blue-500/20 transition-all hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-xl dark:bg-blue-600 dark:hover:bg-blue-500"
                            >
                                Mulai Belajar
                            </Link>
                        </div>
                    </div>

                    <!-- Right Hero Content -->
                    <div
                        class="relative flex items-center justify-center lg:col-span-7"
                    >
                        <!-- Blue blur effect behind the illustration -->
                        <div
                            class="pointer-events-none absolute inset-0 -z-10 scale-75 rounded-full bg-blue-500/20 blur-[130px] dark:bg-blue-500/25"
                        ></div>

                        <!-- Main Hero Image Area -->
                        <div
                            class="relative flex w-full max-w-[650px] items-center justify-center lg:h-[700px]"
                        >
                            <img
                                src="/images/hero-img.png"
                                alt="KelasBelajar Platform"
                                class="h-auto max-h-[320px] w-full object-contain drop-shadow-2xl transition-transform duration-500 select-none hover:scale-[1.01] lg:max-h-[700px]"
                            />

                            <!-- Floating Clouds (Standard Decoration) -->
                            <div
                                class="absolute top-10 right-[15%] hidden animate-bounce duration-[4s] sm:block"
                            >
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white/70 shadow-lg backdrop-blur-xs dark:bg-slate-900/70"
                                >
                                    <Sparkles class="h-5 w-5 text-blue-500" />
                                </div>
                            </div>
                            <div
                                class="absolute bottom-[25%] left-4 hidden animate-bounce duration-[6s] sm:block"
                            >
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white/60 shadow-md backdrop-blur-xs dark:bg-slate-900/60"
                                >
                                    <Sparkles class="h-4 w-4 text-purple-400" />
                                </div>
                            </div>

                            <!-- Floating Card 1: Progress Belajar -->
                            <div
                                class="absolute -top-4 left-6 flex items-center gap-3 rounded-2xl border border-white/20 bg-white/90 p-4 shadow-xl backdrop-blur-md transition-all hover:scale-105 sm:left-12 dark:border-slate-800/40 dark:bg-slate-900/90"
                            >
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                                >
                                    <BookOpen class="h-5 w-5" />
                                </div>
                                <div class="flex min-w-[120px] flex-col">
                                    <span
                                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                                        >Progress Belajar</span
                                    >
                                    <div class="mt-1 flex items-center gap-2">
                                        <div
                                            class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800"
                                        >
                                            <div
                                                class="h-full rounded-full bg-blue-600"
                                                style="width: 85%"
                                            ></div>
                                        </div>
                                        <span
                                            class="text-xs font-bold text-gray-800 dark:text-white"
                                            >85%</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- WHY CHOOSE US -->
        <section
            id="mentor"
            class="bg-[#F8FAFF] py-20 md:py-28 dark:bg-slate-900/30"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto mb-16 max-w-3xl text-center">
                    <h2
                        class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-white"
                    >
                        Mengapa Memilih KelasBelajar?
                    </h2>
                    <p
                        class="mt-4 text-lg leading-relaxed text-gray-500 dark:text-gray-400"
                    >
                        Kami menyediakan ekosistem belajar yang terarah dan
                        lengkap untuk membantu Anda meraih karir impian Anda.
                    </p>
                </div>

                <!-- 3 Feature Cards -->
                <div
                    class="grid grid-cols-1 gap-8 sm:grid-cols-1 lg:grid-cols-3"
                >
                    <div
                        v-for="feat in features"
                        :key="feat.title"
                        class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div
                            :class="[
                                'mb-6 flex h-12 w-12 items-center justify-center rounded-xl transition-transform group-hover:scale-110',
                                feat.bgClass,
                            ]"
                        >
                            <component :is="feat.Icon" class="h-6 w-6" />
                        </div>
                        <h3
                            class="mb-2 text-lg font-bold text-gray-900 dark:text-white"
                        >
                            {{ feat.title }}
                        </h3>
                        <p
                            class="text-sm leading-relaxed text-gray-500 dark:text-gray-400"
                        >
                            {{ feat.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- LEARNING EXPERIENCE -->
        <section id="tentang" class="py-20 md:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12"
                >
                    <!-- Left Column: Modern Dashboard Mockup -->
                    <div class="relative flex justify-center lg:col-span-6">
                        <div
                            class="pointer-events-none absolute inset-0 -z-10 scale-75 rounded-full bg-blue-500/10 blur-[100px] dark:bg-blue-600/10"
                        ></div>

                        <!-- High Fidelity CSS Mockup -->
                        <div
                            class="w-full max-w-[500px] rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900"
                        >
                            <!-- Mockup Header -->
                            <div
                                class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-3.5 w-3.5 rounded-full bg-red-400"
                                    ></span>
                                    <span
                                        class="h-3.5 w-3.5 rounded-full bg-amber-400"
                                    ></span>
                                    <span
                                        class="h-3.5 w-3.5 rounded-full bg-green-400"
                                    ></span>
                                </div>
                                <span
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                    >{{ location.href }}</span
                                >
                            </div>

                            <!-- Mockup Content Grid -->
                            <div class="mt-6 grid grid-cols-12 gap-4">
                                <!-- Sidebar Mockup -->
                                <div class="col-span-3 flex flex-col gap-3">
                                    <div
                                        class="h-6 w-full rounded-md bg-blue-100 dark:bg-blue-900/40"
                                    ></div>
                                    <div
                                        class="h-6 w-3/4 rounded-md bg-gray-100 dark:bg-slate-800"
                                    ></div>
                                    <div
                                        class="h-6 w-5/6 rounded-md bg-gray-100 dark:bg-slate-800"
                                    ></div>
                                    <div
                                        class="h-6 w-2/3 rounded-md bg-gray-100 dark:bg-slate-800"
                                    ></div>
                                </div>
                                <!-- Content Mockup -->
                                <div class="col-span-9 flex flex-col gap-4">
                                    <div
                                        class="rounded-xl border border-blue-100 bg-blue-50/40 p-4 dark:border-blue-950/40 dark:bg-blue-950/20"
                                    >
                                        <div
                                            class="h-4 w-1/3 rounded bg-blue-200 dark:bg-blue-800"
                                        ></div>
                                        <div
                                            class="mt-2 h-3 w-2/3 rounded bg-blue-100 dark:bg-blue-900/50"
                                        ></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div
                                            class="rounded-xl border border-gray-100 p-4 dark:border-slate-800"
                                        >
                                            <div
                                                class="h-4 w-1/2 rounded bg-gray-200 dark:bg-slate-700"
                                            ></div>
                                            <div
                                                class="mt-2 h-6 w-1/3 rounded bg-blue-600"
                                            ></div>
                                        </div>
                                        <div
                                            class="rounded-xl border border-gray-100 p-4 dark:border-slate-800"
                                        >
                                            <div
                                                class="h-4 w-1/2 rounded bg-gray-200 dark:bg-slate-700"
                                            ></div>
                                            <div
                                                class="mt-2 h-6 w-1/3 rounded bg-emerald-500"
                                            ></div>
                                        </div>
                                    </div>
                                    <div
                                        class="rounded-xl border border-gray-100 p-4 dark:border-slate-800"
                                    >
                                        <div
                                            class="h-4 w-1/4 rounded bg-gray-200 dark:bg-slate-700"
                                        ></div>
                                        <div class="mt-3 flex flex-col gap-2">
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <div
                                                    class="dark:bg-slate-850 h-3 w-1/2 rounded bg-gray-100"
                                                ></div>
                                                <div
                                                    class="h-3 w-6 rounded bg-gray-200 dark:bg-slate-700"
                                                ></div>
                                            </div>
                                            <div
                                                class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800"
                                            >
                                                <div
                                                    class="h-full bg-blue-600"
                                                    style="width: 70%"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Text & Checklist -->
                    <div class="flex flex-col justify-center lg:col-span-6">
                        <h2
                            class="mb-6 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-white"
                        >
                            Pengalaman Belajar yang Interaktif & Terarah
                        </h2>
                        <p
                            class="mb-8 text-lg leading-relaxed text-gray-600 dark:text-gray-300"
                        >
                            Kami merancang seluruh proses pembelajaran agar
                            interaktif dan mudah diikuti, membantu Anda
                            membangun keterampilan secara mendalam langkah demi
                            langkah.
                        </p>

                        <!-- Checklist Grid -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div
                                v-for="item in experienceChecklist"
                                :key="item"
                                class="flex items-center gap-3"
                            >
                                <div
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                                >
                                    <Check class="h-4 w-4" />
                                </div>
                                <span
                                    class="text-base font-medium text-gray-700 dark:text-gray-300"
                                    >{{ item }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ SECTION -->
        <section
            id="blog"
            class="bg-[#F8FAFF] py-20 md:py-28 dark:bg-slate-900/30"
        >
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2
                        class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-white"
                    >
                        Pertanyaan Umum (FAQ)
                    </h2>
                    <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">
                        Punya pertanyaan? Kami memiliki jawaban atas beberapa
                        pertanyaan yang paling sering ditanyakan.
                    </p>
                </div>

                <!-- Accordion style list -->
                <div class="flex flex-col gap-4">
                    <div
                        v-for="(faq, idx) in faqs"
                        :key="idx"
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white transition-all dark:border-slate-800 dark:bg-slate-900"
                    >
                        <button
                            @click="toggleFaq(idx)"
                            class="flex w-full items-center justify-between p-6 text-left font-bold text-gray-900 hover:bg-gray-50/50 dark:text-white dark:hover:bg-slate-800/40"
                        >
                            <span class="pr-4 text-base sm:text-lg">{{
                                faq.question
                            }}</span>
                            <ChevronDown
                                :class="[
                                    'h-5 w-5 shrink-0 text-gray-500 transition-transform duration-200',
                                    activeFaqIndex === idx ? 'rotate-180' : '',
                                ]"
                            />
                        </button>
                        <div
                            v-show="activeFaqIndex === idx"
                            class="border-gray-150 border-t bg-gray-50/20 p-6 text-sm leading-relaxed text-gray-600 sm:text-base dark:border-slate-800 dark:bg-slate-900/40 dark:text-gray-300"
                        >
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA SECTION -->
        <section class="py-20 md:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Large Gradient Banner -->
                <div
                    class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-600 to-[#7D9CFF] px-8 py-16 text-center text-white shadow-2xl dark:from-blue-600 dark:to-blue-800"
                >
                    <!-- Deco Pattern -->
                    <div
                        class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.05)_1px,transparent_1px)] bg-[size:32px_32px] opacity-20"
                    ></div>

                    <div
                        class="relative z-10 mx-auto flex max-w-3xl flex-col items-center"
                    >
                        <h2
                            class="mb-6 text-3xl font-extrabold tracking-tight sm:text-5xl"
                        >
                            Mulai Perjalanan Belajar Anda Hari Ini
                        </h2>
                        <p
                            class="mb-10 max-w-2xl text-lg leading-relaxed text-white/90"
                        >
                            Gabung bersama ribuan siswa yang telah meningkatkan
                            keterampilannya dan capai potensi karir impian Anda
                            sekarang juga.
                        </p>
                        <Link
                            :href="register()"
                            class="flex h-14 items-center justify-center rounded-xl bg-white px-8 text-base font-bold text-blue-600 shadow-xl transition-all hover:scale-105 hover:bg-gray-100"
                        >
                            Daftar Gratis
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer
            class="border-t border-gray-200 bg-white py-16 transition-colors duration-300 dark:border-slate-800 dark:bg-[#0F172A]"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Grid -->
                <div class="mb-16 grid grid-cols-2 gap-8 md:grid-cols-12">
                    <!-- Column 1: Brand description -->
                    <div class="col-span-2 flex flex-col gap-6 md:col-span-4">
                        <div class="flex items-center gap-2">
                            <AppLogo :icon-only="true" />
                            <span
                                class="text-xl font-bold tracking-tight text-[#111827] dark:text-white"
                                >Kelas<span class="text-blue-600"
                                    >Belajar</span
                                ></span
                            >
                        </div>
                        <p
                            class="max-w-sm text-sm leading-relaxed text-gray-500 dark:text-gray-400"
                        >
                            Platform learning management system online modern
                            untuk membantu Anda menguasai keahlian profesional
                            yang dibutuhkan masa depan.
                        </p>
                        <!-- Social links using beautiful raw SVGs -->
                        <div class="flex gap-4">
                            <!-- Instagram SVG -->
                            <a
                                href="#"
                                class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                aria-label="Instagram"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="h-5 w-5"
                                >
                                    <rect
                                        width="20"
                                        height="20"
                                        x="2"
                                        y="2"
                                        rx="5"
                                        ry="5"
                                    />
                                    <path
                                        d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"
                                    />
                                    <line
                                        x1="17.5"
                                        x2="17.51"
                                        y1="6.5"
                                        y2="6.5"
                                    />
                                </svg>
                            </a>
                            <!-- LinkedIn SVG -->
                            <a
                                href="#"
                                class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                aria-label="LinkedIn"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="h-5 w-5"
                                >
                                    <path
                                        d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"
                                    />
                                    <rect width="4" height="12" x="2" y="9" />
                                    <circle cx="4" cy="4" r="2" />
                                </svg>
                            </a>
                            <!-- YouTube SVG -->
                            <a
                                href="#"
                                class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                aria-label="YouTube"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="h-5 w-5"
                                >
                                    <path
                                        d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17z"
                                    />
                                    <path d="m10 15 5-3-5-3z" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Column 2: Produk -->
                    <div class="col-span-1 md:col-span-2 md:col-start-6">
                        <h4
                            class="mb-6 text-sm font-bold tracking-wider text-gray-900 uppercase dark:text-white"
                        >
                            Produk
                        </h4>
                        <ul class="flex flex-col gap-4 text-sm">
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Semua Kelas</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Program Sertifikat</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Kelas Populer</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Materi Gratis</a
                                >
                            </li>
                        </ul>
                    </div>

                    <!-- Column 3: Perusahaan -->
                    <div class="col-span-1 md:col-span-2">
                        <h4
                            class="mb-6 text-sm font-bold tracking-wider text-gray-900 uppercase dark:text-white"
                        >
                            Perusahaan
                        </h4>
                        <ul class="flex flex-col gap-4 text-sm">
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Tentang Kami</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Karir</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Mitra Kerja</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Pers & Berita</a
                                >
                            </li>
                        </ul>
                    </div>

                    <!-- Column 4: Bantuan -->
                    <div class="col-span-1 md:col-span-2">
                        <h4
                            class="mb-6 text-sm font-bold tracking-wider text-gray-900 uppercase dark:text-white"
                        >
                            Bantuan
                        </h4>
                        <ul class="flex flex-col gap-4 text-sm">
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Pusat Bantuan</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Hubungi Kami</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Kebijakan Privasi</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >Syarat & Ketentuan</a
                                >
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom Line -->
                <div
                    class="border-gray-150 flex flex-col items-center justify-between border-t pt-8 text-xs text-gray-400 sm:flex-row dark:border-slate-800 dark:text-gray-500"
                >
                    <p>
                        &copy; 2026 KelasBelajar. Hak Cipta Dilindungi
                        Undang-Undang.
                    </p>
                    <p class="mt-4 sm:mt-0">
                        Dibuat dengan dedikasi untuk masa depan cerdas.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
