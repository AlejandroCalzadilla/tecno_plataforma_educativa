<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

interface PageViewItem {
    route_name: string;
    views: number;
}

const page = usePage();

const pageViews = computed<PageViewItem[]>(() => {
    const value = page.props.pageViews;

    if (!Array.isArray(value)) {
        return [];
    }

    return value as PageViewItem[];
});

const currentRouteName = computed(() => {
    const current = route().current();

    return typeof current === 'string' ? current : null;
});

const currentPageView = computed<PageViewItem | null>(() => {
    if (!currentRouteName.value) {
        return null;
    }
    
    return pageViews.value.find((item) => item.route_name === currentRouteName.value) ?? null;
});
</script>

<template>
    <aside
        v-if="currentPageView"
        class="fixed bottom-4 right-4 z-50 w-80 rounded-xl border border-border bg-card p-4 shadow-sm opacity-45    "
    >
        <h4 class="mb-3 text-sm font-bold text-foreground">Visitas por página</h4>
        <div class="flex items-center justify-between rounded-lg bg-muted/40 px-3 py-2">
            <span class="truncate text-xs font-medium text-muted-foreground">{{ currentPageView.route_name }}</span>
            <span class="ml-3 text-sm font-bold text-foreground">{{ currentPageView.views }}</span>
        </div>
    </aside>
</template>
