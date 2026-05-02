<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
    dashboard: Object,
    filters: Object,
    options: Object,
    types: Array,
    canUseAllScope: Boolean,
});

const chartCanvas = ref(null);
let chart = null;

const filters = reactive({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    currency_id: props.filters.currency_id || '',
    cashbox_id: props.filters.cashbox_id || '',
    type: props.filters.type || '',
    scope: props.filters.scope || '',
});

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

const applyFilters = () => {
    router.get(route('dashboard'), cleanFilters(), {
        preserveScroll: true,
    });
};

const cleanFilters = () =>
    Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );

const chartData = () => ({
    labels: ['Income', 'Expense', 'Net'],
    datasets: [
        {
            label: 'Totals',
            backgroundColor: ['#047857', '#b91c1c', '#374151'],
            maxBarThickness: 44,
            data: [
                props.dashboard.totals.income,
                props.dashboard.totals.expense,
                props.dashboard.totals.net,
            ],
        },
    ],
});

const renderChart = () => {
    if (!chartCanvas.value) {
        return;
    }

    chart?.destroy();
    chart = new Chart(chartCanvas.value, {
        type: 'bar',
        data: chartData(),
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 100,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.label}: ${money(context.parsed.y)}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
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

onMounted(() => nextTick(renderChart));
watch(() => props.dashboard.totals, () => nextTick(renderChart), { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
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
                        <Link class="w-full rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 sm:w-auto" :href="route('dashboard')">Reset</Link>
                    </div>
                </form>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Total income</div>
                        <div class="mt-2 text-2xl font-semibold text-emerald-700">{{ money(dashboard.totals.income) }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Total expense</div>
                        <div class="mt-2 text-2xl font-semibold text-red-700">{{ money(dashboard.totals.expense) }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Net balance</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ money(dashboard.totals.net) }}</div>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="min-w-0 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                        <div class="mb-4 text-base font-semibold text-gray-900">Totals chart</div>
                        <div class="relative h-56 min-w-0 sm:h-64">
                            <canvas ref="chartCanvas" class="block h-full w-full max-w-full" />
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="mb-4 text-base font-semibold text-gray-900">Cashbox balances</div>
                        <div v-if="dashboard.cashboxBalances.length === 0" class="text-sm text-gray-500">No cashbox data.</div>
                        <div v-else class="space-y-3">
                            <div v-for="cashbox in dashboard.cashboxBalances" :key="cashbox.label" class="flex items-center justify-between gap-4">
                                <div class="text-sm font-medium text-gray-800">{{ cashbox.label }}</div>
                                <div class="text-sm font-semibold" :class="cashbox.net >= 0 ? 'text-emerald-700' : 'text-red-700'">{{ money(cashbox.net) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-lg bg-white p-4 shadow-sm">
                    <div class="mb-4 text-base font-semibold text-gray-900">Recent transactions</div>
                    <div v-if="dashboard.recentTransactions.length === 0" class="text-sm text-gray-500">No recent transactions.</div>
                    <div v-else class="space-y-3">
                        <div v-for="transaction in dashboard.recentTransactions" :key="transaction.id" class="flex flex-col gap-2 border-b border-gray-100 pb-3 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="font-medium text-gray-900">{{ transaction.category }}</div>
                                <div class="text-sm text-gray-500">{{ transaction.transaction_date }} · {{ transaction.cashbox }} · {{ transaction.comment || 'No comment' }}</div>
                            </div>
                            <div class="font-semibold" :class="transaction.type === 'income' ? 'text-emerald-700' : 'text-red-700'">
                                {{ transaction.type === 'income' ? '+' : '-' }} {{ money(transaction.amount) }} {{ transaction.currency }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
