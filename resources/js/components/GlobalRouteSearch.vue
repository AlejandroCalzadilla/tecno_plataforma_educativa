<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

interface ZiggyRouteItem {
    uri: string;
    methods: string[];
}

interface SearchResult {
    name: string;
    label: string;
    uri: string;
    href: string;
    searchable: string;
}

const page = usePage();
const query = ref('');
const notFoundInPage = ref(false);
const HIGHLIGHT_ATTR = 'data-global-search-highlight';

const normalize = (value: string) =>
    value
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '');

const friendlyLabels: Record<string, string> = {
    dashboard: 'Dashboard',
    users: 'Usuarios',
    categorias: 'Categorías',
    servicios: 'Servicios',
    calendarios: 'Oferta académica',
    sesiones: 'Sesiones',
    licencias: 'Licencias',
    'informes-clase': 'Informes de clase',
    catalogo: 'Catálogo',
    pagos: 'Pagos',
    settings: 'Configuración',
};

const toFriendlyLabel = (routeName: string, uri: string) => {
    const routePrefix = routeName.split('.')[0];
    const uriPrefix = uri.split('/')[0] || routePrefix;
    const key = friendlyLabels[routePrefix] ? routePrefix : uriPrefix;

    if (friendlyLabels[key]) {
        return friendlyLabels[key];
    }

    return key
        .replaceAll('-', ' ')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const routes = computed<SearchResult[]>(() => {
    const ziggy = page.props.ziggy as { routes?: Record<string, ZiggyRouteItem> } | undefined;
    const routeTable = ziggy?.routes ?? {};

    return Object.entries(routeTable)
        .filter(([, definition]) => definition.methods.includes('GET'))
        .filter(([, definition]) => !definition.uri.includes('{'))
        .map(([name, definition]) => {
            let href = `/${definition.uri}`;

            try {
                href = route(name);
            } catch {
                href = `/${definition.uri}`;
            }

            const words = `${name.replaceAll('.', ' ')} ${definition.uri.replaceAll('/', ' ')}`;
            const label = toFriendlyLabel(name, definition.uri);

            return {
                name,
                label,
                uri: definition.uri,
                href,
                searchable: normalize(`${words} ${label}`),
            };
        });
});

const results = computed(() => {
    const q = normalize(query.value.trim());

    if (!q) {
        return [];
    }

    return routes.value.filter((item) => item.searchable.includes(q)).slice(0, 8);
});

const escapeHtml = (value: string) =>
    value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

const escapeRegExp = (value: string) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const highlighted = (text: string) => {
    const term = query.value.trim();

    if (!term) {
        return escapeHtml(text);
    }

    const splitRegex = new RegExp(`(${escapeRegExp(term)})`, 'i');
    const parts = text.split(splitRegex);
    const normalizedTerm = normalize(term);

    return parts
        .map((part) => {
            if (normalize(part) === normalizedTerm) {
                return `<mark class="rounded bg-primary/20 px-0.5 text-foreground">${escapeHtml(part)}</mark>`;
            }

            return escapeHtml(part);
        })
        .join('');
};

const clearSearch = () => {
    query.value = '';
    notFoundInPage.value = false;
    clearPageHighlights();
};

const getSearchContentRoot = () => document.querySelector('main') ?? document.body;

const clearPageHighlights = () => {
    const root = getSearchContentRoot();
    const highlights = root.querySelectorAll(`mark[${HIGHLIGHT_ATTR}="true"]`);

    highlights.forEach((highlight) => {
        const parent = highlight.parentNode;

        if (!parent) {
            return;
        }

        parent.replaceChild(document.createTextNode(highlight.textContent ?? ''), highlight);
        parent.normalize();
    });
};

const highlightInCurrentView = (term: string) => {
    clearPageHighlights();

    if (!term) {
        return 0;
    }

    const root = getSearchContentRoot();
    const escapedTerm = escapeRegExp(term);
    const regex = new RegExp(escapedTerm, 'gi');
    const textNodes: Text[] = [];

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
            const textNode = node as Text;
            const parent = textNode.parentElement;

            if (!parent || !textNode.textContent?.trim()) {
                return NodeFilter.FILTER_REJECT;
            }

            if (parent.closest('[data-global-search-root]')) {
                return NodeFilter.FILTER_REJECT;
            }

            const excludedTags = ['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA', 'INPUT', 'MARK'];

            if (excludedTags.includes(parent.tagName)) {
                return NodeFilter.FILTER_REJECT;
            }

            return NodeFilter.FILTER_ACCEPT;
        },
    });

    while (walker.nextNode()) {
        textNodes.push(walker.currentNode as Text);
    }

    let totalMatches = 0;

    textNodes.forEach((textNode) => {
        const content = textNode.textContent ?? '';

        if (!regex.test(content)) {
            regex.lastIndex = 0;
            return;
        }

        regex.lastIndex = 0;
        const fragment = document.createDocumentFragment();
        let lastIndex = 0;

        for (const match of content.matchAll(new RegExp(escapedTerm, 'gi'))) {
            const start = match.index ?? 0;
            const foundText = match[0] ?? '';

            if (start > lastIndex) {
                fragment.appendChild(document.createTextNode(content.slice(lastIndex, start)));
            }

            const mark = document.createElement('mark');
            mark.setAttribute(HIGHLIGHT_ATTR, 'true');
            mark.className = 'rounded bg-primary/20 px-0.5 text-foreground';
            mark.textContent = foundText;
            fragment.appendChild(mark);

            lastIndex = start + foundText.length;
            totalMatches += 1;
        }

        if (lastIndex < content.length) {
            fragment.appendChild(document.createTextNode(content.slice(lastIndex)));
        }

        textNode.parentNode?.replaceChild(fragment, textNode);
    });

    return totalMatches;
};

const findInCurrentView = () => {
    const term = query.value.trim();

    if (!term) {
        notFoundInPage.value = false;
        clearPageHighlights();
        return;
    }

    notFoundInPage.value = highlightInCurrentView(term) === 0;
};

watch(query, async () => {
    await nextTick();
    findInCurrentView();
});

onBeforeUnmount(() => {
    clearPageHighlights();
});
</script>

<template>
    <div class="relative w-full max-w-md" data-global-search-root>
        <input
            v-model="query"
            type="search"
            placeholder="Buscar rutas o palabras..."
            class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
            @keydown.enter.prevent="findInCurrentView"
        />

        <div
            v-if="query.trim().length"
            class="absolute top-full z-50 mt-2 w-full rounded-lg border border-border bg-card p-2 shadow-sm"
        >
            <button
                type="button"
                class="mb-2 w-full rounded-md px-3 py-2 text-left text-sm font-semibold text-foreground hover:bg-muted"
                @click="findInCurrentView"
            >
                Buscar "{{ query.trim() }}" en esta vista (Ctrl+F)
            </button>

            <ul class="space-y-1">
                <li v-for="item in results" :key="item.name">
                    <Link
                        :href="item.href"
                        class="block rounded-md px-3 py-2 hover:bg-muted"
                        @click="clearSearch"
                    >
                        <p class="text-sm font-semibold text-foreground" v-html="highlighted(item.label)" />
                        <p class="text-xs text-muted-foreground">
                            <span v-html="highlighted(item.name)" />
                            <span> · /</span>
                            <span v-html="highlighted(item.uri)" />
                        </p>
                    </Link>
                </li>
            </ul>

            <p v-if="notFoundInPage" class="mt-2 px-3 text-xs text-muted-foreground">
                No se encontró ese texto en esta vista.
            </p>
        </div>
    </div>
</template>
