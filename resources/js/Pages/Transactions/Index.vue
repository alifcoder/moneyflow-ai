<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    transactions: Object,
    filters: Object,
    options: Object,
    types: Array,
    canUseAllScope: Boolean,
});

const filters = reactive({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    type: props.filters.type || '',
    category_id: props.filters.category_id || '',
    currency_id: props.filters.currency_id || '',
    cashbox_id: props.filters.cashbox_id || '',
    search: props.filters.search || '',
    scope: props.filters.scope || '',
});

const cleanFilters = () =>
    Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );

const applyFilters = () => {
    router.get(route('transactions.index'), cleanFilters(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const destroyTransaction = (transaction) => {
    if (!transaction.can_delete || !window.confirm('Delete this transaction?')) {
        return;
    }

    router.delete(route('transactions.destroy', transaction.id), {
        preserveScroll: true,
    });
};

const amountClass = (type) =>
    type === 'income' ? 'text-emerald-700' : 'text-red-700';

const signedAmount = (transaction) =>
    `${transaction.type === 'income' ? '+' : '-'} ${transaction.amount} ${transaction.currency.code}`;
</script>

<template>
    <Head title="Transactions" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Transactions
                </h2>
                <Link
                    class="rounded-md bg-gray-900 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-gray-700"
                    :href="route('transactions.create')"
                >
                    New transaction
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <form
                    class="mb-6 grid gap-4 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-8"
                    @submit.prevent="applyFilters"
                >
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From</label>
                        <input
                            v-model="filters.date_from"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            type="date"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">To</label>
                        <input
                            v-model="filters.date_to"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            type="date"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select
                            v-model="filters.type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option v-for="type in types" :key="type" :value="type">
                                {{ type }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select
                            v-model="filters.category_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option
                                v-for="category in options.categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Currency</label>
                        <select
                            v-model="filters.currency_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option
                                v-for="currency in options.currencies"
                                :key="currency.id"
                                :value="currency.id"
                            >
                                {{ currency.code }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cashbox</label>
                        <select
                            v-model="filters.cashbox_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option
                                v-for="cashbox in options.cashboxes"
                                :key="cashbox.id"
                                :value="cashbox.id"
                            >
                                {{ cashbox.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Comment</label>
                        <input
                            v-model="filters.search"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            type="search"
                        />
                    </div>
                    <div v-if="canUseAllScope">
                        <label class="block text-sm font-medium text-gray-700">Scope</label>
                        <select
                            v-model="filters.scope"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Own</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-8">
                        <button
                            class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 sm:w-auto"
                            type="submit"
                        >
                            Apply
                        </button>
                        <Link
                            class="w-full rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 sm:w-auto"
                            :href="route('transactions.index')"
                        >
                            Reset
                        </Link>
                    </div>
                </form>

                <div
                    v-if="transactions.data.length === 0"
                    class="rounded-lg bg-white p-8 text-center text-gray-600 shadow-sm"
                >
                    No transactions found.
                </div>

                <div v-else class="space-y-3 md:hidden">
                    <div
                        v-for="transaction in transactions.data"
                        :key="transaction.id"
                        class="rounded-lg bg-white p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm text-gray-500">
                                    {{ transaction.transaction_date }}
                                </div>
                                <div class="mt-1 font-semibold text-gray-900">
                                    {{ transaction.category.name }}
                                </div>
                                <div class="mt-1 text-sm text-gray-600">
                                    {{ transaction.cashbox.name }} · {{ transaction.comment || 'No comment' }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                    :class="transaction.type === 'income' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                                >
                                    {{ transaction.type }}
                                </span>
                                <div class="mt-2 font-semibold" :class="amountClass(transaction.type)">
                                    {{ signedAmount(transaction) }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <Link
                                class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-center text-sm font-semibold text-gray-700"
                                :href="route('transactions.edit', transaction.id)"
                            >
                                Edit
                            </Link>
                            <button
                                class="flex-1 rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                                type="button"
                                :disabled="!transaction.can_delete"
                                @click="destroyTransaction(transaction)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="transactions.data.length > 0"
                    class="hidden rounded-lg bg-white shadow-sm md:block md:overflow-x-auto"
                >
                    <table class="min-w-[52rem] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Date</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Type</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Category</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Cashbox</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Comment</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Amount</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="transaction in transactions.data" :key="transaction.id">
                                <td class="px-4 py-3 text-sm text-gray-700">{{ transaction.transaction_date }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="transaction.type === 'income' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                                    >
                                        {{ transaction.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ transaction.category.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ transaction.cashbox.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ transaction.comment || '-' }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold" :class="amountClass(transaction.type)">
                                    {{ signedAmount(transaction) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        class="rounded-md px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100"
                                        :href="route('transactions.edit', transaction.id)"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        class="rounded-md px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        type="button"
                                        :disabled="!transaction.can_delete"
                                        @click="destroyTransaction(transaction)"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="link in transactions.links"
                        :key="link.label"
                        class="rounded-md border px-3 py-2 text-sm"
                        :class="link.active ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700'"
                        :href="link.url || ''"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
