<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Legend,
    LinearScale,
    PieController,
    Tooltip,
} from 'chart.js';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

Chart.register(ArcElement, BarController, BarElement, CategoryScale, LinearScale, PieController, Tooltip, Legend);

const props = defineProps({
    reports: Object,
    filters: Object,
    options: Object,
    types: Array,
    canUseAllScope: Boolean,
});

const monthlyCanvas = ref(null);
const categoryCanvas = ref(null);
const currencyCanvas = ref(null);
const cashboxCanvas = ref(null);
const totalPieCanvas = ref(null);
const categoryPieCanvas = ref(null);
const currencyPieCanvas = ref(null);
const cashboxPieCanvas = ref(null);
let charts = [];

const palette = [
    '#047857',
    '#b91c1c',
    '#2563eb',
    '#d97706',
    '#7c3aed',
    '#0f766e',
    '#be123c',
    '#4b5563',
];

const filters = reactive({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    currency_id: props.filters.currency_id || '',
    cashbox_id: props.filters.cashbox_id || '',
    type: props.filters.type || '',
    scope: props.filters.scope || '',
});

const activeType = computed(() => props.filters.type || '');

const money = (value) =>
    Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const compactNumber = (value) =>
    Number(value || 0).toLocaleString(undefined, {
        notation: 'compact',
        maximumFractionDigits: 1,
    });

const chartValue = (row) => {
    if (activeType.value === 'income') {
        return Number(row.income || 0);
    }

    if (activeType.value === 'expense') {
        return Number(row.expense || 0);
    }

    return Number(row.income || 0) + Number(row.expense || 0);
};

const pieRows = (rows) =>
    rows
        .map((row, index) => ({
            label: row.label,
            value: chartValue(row),
            color: palette[index % palette.length],
        }))
        .filter((row) => row.value > 0);

const totalPieRows = computed(() => [
    {
        label: 'Income',
        value: Number(props.reports.totals.income || 0),
        color: '#047857',
    },
    {
        label: 'Expense',
        value: Number(props.reports.totals.expense || 0),
        color: '#b91c1c',
    },
].filter((row) => row.value > 0));

const categoryPieRows = computed(() => pieRows(props.reports.byCategory || []));
const currencyPieRows = computed(() => pieRows(props.reports.byCurrency || []));
const cashboxPieRows = computed(() => pieRows(props.reports.byCashbox || []));

const cleanFilters = () =>
    Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );

const applyFilters = () => {
    router.get(route('reports.index'), cleanFilters(), {
        preserveScroll: true,
    });
};

const chartDataset = (rows) => ({
    labels: rows.map((row) => row.label),
    datasets: [
        {
            label: 'Income',
            backgroundColor: '#047857',
            data: rows.map((row) => row.income),
            maxBarThickness: 44,
        },
        {
            label: 'Expense',
            backgroundColor: '#b91c1c',
            data: rows.map((row) => row.expense),
            maxBarThickness: 44,
        },
    ],
});

const renderBarChart = (canvas, rows) => {
    if (!canvas.value) {
        return null;
    }

    return new Chart(canvas.value, {
        type: 'bar',
        data: chartDataset(rows),
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 100,
            layout: {
                padding: 0,
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxHeight: 8,
                        boxWidth: 8,
                        padding: 12,
                        usePointStyle: true,
                    },
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${money(context.parsed.y)}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        autoSkip: true,
                        maxRotation: 0,
                        callback(value) {
                            const label = this.getLabelForValue(value);

                            return label.length > 12 ? `${label.slice(0, 12)}...` : label;
                        },
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: compactNumber,
                        maxTicksLimit: 6,
                    },
                },
            },
        },
    });
};

const renderPieChart = (canvas, rows) => {
    if (!canvas.value || rows.length === 0) {
        return null;
    }

    return new Chart(canvas.value, {
        type: 'pie',
        data: {
            labels: rows.map((row) => row.label),
            datasets: [
                {
                    backgroundColor: rows.map((row) => row.color),
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    data: rows.map((row) => row.value),
                    hoverOffset: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 100,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.label}: ${money(context.parsed)}`,
                    },
                },
            },
        },
    });
};

const renderCharts = () => {
    charts.forEach((chart) => chart?.destroy());
    charts = [
        renderBarChart(monthlyCanvas, props.reports.monthly),
        renderBarChart(categoryCanvas, props.reports.byCategory),
        renderBarChart(currencyCanvas, props.reports.byCurrency),
        renderBarChart(cashboxCanvas, props.reports.byCashbox),
        renderPieChart(totalPieCanvas, totalPieRows.value),
        renderPieChart(categoryPieCanvas, categoryPieRows.value),
        renderPieChart(currencyPieCanvas, currencyPieRows.value),
        renderPieChart(cashboxPieCanvas, cashboxPieRows.value),
    ];
};

onMounted(() => nextTick(renderCharts));
watch(() => props.reports, () => nextTick(renderCharts), { deep: true });
onBeforeUnmount(() => charts.forEach((chart) => chart?.destroy()));
</script>

<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Reports
            </h2>
        </template>

        <div class="py-6 sm:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <form
                    class="mb-6 grid gap-4 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-7"
                    @submit.prevent="applyFilters"
                >
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From</label>
                        <input v-model="filters.date_from" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" type="date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">To</label>
                        <input v-model="filters.date_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" type="date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select v-model="filters.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">All</option>
                            <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Currency</label>
                        <select v-model="filters.currency_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">All</option>
                            <option v-for="currency in options.currencies" :key="currency.id" :value="currency.id">{{ currency.code }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cashbox</label>
                        <select v-model="filters.cashbox_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">All</option>
                            <option v-for="cashbox in options.cashboxes" :key="cashbox.id" :value="cashbox.id">{{ cashbox.name }}</option>
                        </select>
                    </div>
                    <div v-if="canUseAllScope">
                        <label class="block text-sm font-medium text-gray-700">Scope</label>
                        <select v-model="filters.scope" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Own</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-7">
                        <button class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white sm:w-auto" type="submit">Apply</button>
                        <Link class="w-full rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 sm:w-auto" :href="route('reports.index')">Reset</Link>
                    </div>
                </form>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Income</div>
                        <div class="mt-2 text-2xl font-semibold text-emerald-700">{{ money(reports.totals.income) }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Expense</div>
                        <div class="mt-2 text-2xl font-semibold text-red-700">{{ money(reports.totals.expense) }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Net</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ money(reports.totals.net) }}</div>
                    </div>
                </div>

                <div class="mt-6 grid min-w-0 gap-4 lg:gap-6 xl:grid-cols-2">
                    <section class="min-w-0 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">Monthly income vs expense</h3>
                        <div class="relative h-56 min-w-0 sm:h-64 lg:h-72">
                            <canvas ref="monthlyCanvas" class="block h-full w-full max-w-full" />
                        </div>
                    </section>
                    <section class="min-w-0 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">By category</h3>
                        <div class="relative h-56 min-w-0 sm:h-64 lg:h-72">
                            <canvas ref="categoryCanvas" class="block h-full w-full max-w-full" />
                        </div>
                    </section>
                    <section class="min-w-0 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">By currency</h3>
                        <div class="relative h-56 min-w-0 sm:h-64 lg:h-72">
                            <canvas ref="currencyCanvas" class="block h-full w-full max-w-full" />
                        </div>
                    </section>
                    <section class="min-w-0 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">By cashbox</h3>
                        <div class="relative h-56 min-w-0 sm:h-64 lg:h-72">
                            <canvas ref="cashboxCanvas" class="block h-full w-full max-w-full" />
                        </div>
                    </section>
                </div>

                <div class="mt-6 grid min-w-0 gap-4 lg:gap-6 xl:grid-cols-2">
                    <section class="min-w-0 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">Income vs expense share</h3>
                        <div v-if="totalPieRows.length === 0" class="text-sm text-gray-500">No chart data.</div>
                        <div v-else class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(12rem,16rem)] sm:items-center">
                            <div class="relative mx-auto h-48 w-full max-w-xs sm:h-56">
                                <canvas ref="totalPieCanvas" class="block h-full w-full max-w-full" />
                            </div>
                            <div class="space-y-2">
                                <div v-for="row in totalPieRows" :key="row.label" class="flex items-center justify-between gap-3 text-sm">
                                    <span class="flex min-w-0 items-center gap-2 text-gray-700">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: row.color }"></span>
                                        <span class="truncate">{{ row.label }}</span>
                                    </span>
                                    <span class="shrink-0 font-semibold text-gray-900">{{ money(row.value) }}</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="min-w-0 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">Category share</h3>
                        <div v-if="categoryPieRows.length === 0" class="text-sm text-gray-500">No chart data.</div>
                        <div v-else class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(12rem,16rem)] sm:items-center">
                            <div class="relative mx-auto h-48 w-full max-w-xs sm:h-56">
                                <canvas ref="categoryPieCanvas" class="block h-full w-full max-w-full" />
                            </div>
                            <div class="space-y-2">
                                <div v-for="row in categoryPieRows" :key="row.label" class="flex items-center justify-between gap-3 text-sm">
                                    <span class="flex min-w-0 items-center gap-2 text-gray-700">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: row.color }"></span>
                                        <span class="truncate">{{ row.label }}</span>
                                    </span>
                                    <span class="shrink-0 font-semibold text-gray-900">{{ money(row.value) }}</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="min-w-0 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">Currency share</h3>
                        <div v-if="currencyPieRows.length === 0" class="text-sm text-gray-500">No chart data.</div>
                        <div v-else class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(12rem,16rem)] sm:items-center">
                            <div class="relative mx-auto h-48 w-full max-w-xs sm:h-56">
                                <canvas ref="currencyPieCanvas" class="block h-full w-full max-w-full" />
                            </div>
                            <div class="space-y-2">
                                <div v-for="row in currencyPieRows" :key="row.label" class="flex items-center justify-between gap-3 text-sm">
                                    <span class="flex min-w-0 items-center gap-2 text-gray-700">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: row.color }"></span>
                                        <span class="truncate">{{ row.label }}</span>
                                    </span>
                                    <span class="shrink-0 font-semibold text-gray-900">{{ money(row.value) }}</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="min-w-0 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">Cashbox share</h3>
                        <div v-if="cashboxPieRows.length === 0" class="text-sm text-gray-500">No chart data.</div>
                        <div v-else class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(12rem,16rem)] sm:items-center">
                            <div class="relative mx-auto h-48 w-full max-w-xs sm:h-56">
                                <canvas ref="cashboxPieCanvas" class="block h-full w-full max-w-full" />
                            </div>
                            <div class="space-y-2">
                                <div v-for="row in cashboxPieRows" :key="row.label" class="flex items-center justify-between gap-3 text-sm">
                                    <span class="flex min-w-0 items-center gap-2 text-gray-700">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: row.color }"></span>
                                        <span class="truncate">{{ row.label }}</span>
                                    </span>
                                    <span class="shrink-0 font-semibold text-gray-900">{{ money(row.value) }}</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-3">
                    <section class="rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-3 text-base font-semibold text-gray-900">Categories</h3>
                        <div v-for="row in reports.byCategory" :key="row.label" class="flex justify-between border-b border-gray-100 py-2 text-sm last:border-0">
                            <span>{{ row.label }}</span>
                            <span class="font-semibold">{{ money(row.net) }}</span>
                        </div>
                    </section>
                    <section class="rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-3 text-base font-semibold text-gray-900">Currencies</h3>
                        <div v-for="row in reports.byCurrency" :key="row.label" class="flex justify-between border-b border-gray-100 py-2 text-sm last:border-0">
                            <span>{{ row.label }}</span>
                            <span class="font-semibold">{{ money(row.net) }}</span>
                        </div>
                    </section>
                    <section class="rounded-lg bg-white p-4 shadow-sm">
                        <h3 class="mb-3 text-base font-semibold text-gray-900">Cashboxes</h3>
                        <div v-for="row in reports.byCashbox" :key="row.label" class="flex justify-between border-b border-gray-100 py-2 text-sm last:border-0">
                            <span>{{ row.label }}</span>
                            <span class="font-semibold">{{ money(row.net) }}</span>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
