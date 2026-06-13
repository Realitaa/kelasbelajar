<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationFirst,
    PaginationItem,
    PaginationLast,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';

const props = defineProps<{
    total: number;
    currentPage: number;
    perPage: number;
}>();

const getPageUrl = (pageNumber: number) => {
    const url = new URL(window.location.href, window.location.origin);
    url.searchParams.set('page', pageNumber.toString());

    return url.pathname + url.search;
};

const lastPage = computed(() => Math.ceil(props.total / props.perPage));
</script>

<template>
    <div v-if="total > perPage" class="py-4">
        <Pagination
            :total="total"
            :items-per-page="perPage"
            :sibling-count="1"
            show-edges
            :default-page="currentPage"
        >
            <PaginationContent v-slot="{ items }">
                <PaginationFirst as-child :disabled="currentPage <= 1">
                    <Link :href="getPageUrl(1)" />
                </PaginationFirst>

                <PaginationPrevious as-child :disabled="currentPage <= 1">
                    <Link :href="getPageUrl(currentPage - 1)" />
                </PaginationPrevious>

                <template v-for="(item, index) in items">
                    <PaginationItem
                        v-if="item.type === 'page'"
                        :key="index"
                        :value="item.value"
                        :is-active="item.value === currentPage"
                        as-child
                    >
                        <Link :href="getPageUrl(item.value)">
                            {{ item.value }}
                        </Link>
                    </PaginationItem>
                    <PaginationEllipsis v-else :key="item.type" :index="index" />
                </template>

                <PaginationNext as-child :disabled="currentPage >= lastPage">
                    <Link :href="getPageUrl(currentPage + 1)" />
                </PaginationNext>

                <PaginationLast as-child :disabled="currentPage >= lastPage">
                    <Link :href="getPageUrl(lastPage)" />
                </PaginationLast>
            </PaginationContent>
        </Pagination>
    </div>
</template>
