<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    currencies: Object,
    filters: Object,
    canUseAllScope: Boolean,
});

const editingId = ref(null);

const filters = reactive({
    search: props.filters.search || '',
    enabled: props.filters.enabled ?? '',
    scope: props.filters.scope || '',
});

const form = useForm({
    owner_scope: 'own',
    code: '',
    name: '',
    symbol: '',
    is_default: false,
    enabled: true,
});

const applyFilters = () => {
    router.get(route('currencies.index'), cleanFilters(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const cleanFilters = () =>
    Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.owner_scope = 'own';
    form.is_default = false;
    form.enabled = true;
};

const editCurrency = (currency) => {
    editingId.value = currency.id;
    form.clearErrors();
    form.code = currency.code;
    form.name = currency.name;
    form.symbol = currency.symbol || '';
    form.is_default = currency.is_default;
    form.enabled = currency.enabled;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    };

    if (editingId.value) {
        form.put(route('currencies.update', editingId.value), options);

        return;
    }

    form.post(route('currencies.store'), options);
};

const destroyCurrency = (currency) => {
    if (!currency.can_delete || !window.confirm('Delete this currency?')) {
        return;
    }

    router.delete(route('currencies.destroy', currency.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Currencies" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Currencies
            </h2>
        </template>

        <div class="py-6 sm:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <form
                    class="mb-6 grid gap-4 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6"
                    @submit.prevent="applyFilters"
                >
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Search
                        </label>
                        <input
                            v-model="filters.search"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            type="search"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Status
                        </label>
                        <select
                            v-model="filters.enabled"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>

                    <div v-if="canUseAllScope">
                        <label class="block text-sm font-medium text-gray-700">
                            Scope
                        </label>
                        <select
                            v-model="filters.scope"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Global + own</option>
                            <option value="all">All</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 sm:col-span-2">
                        <button
                            class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 sm:w-auto"
                            type="submit"
                        >
                            Apply
                        </button>
                        <Link
                            class="w-full rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 sm:w-auto"
                            :href="route('currencies.index')"
                        >
                            Reset
                        </Link>
                    </div>
                </form>

                <form
                    class="mb-6 grid gap-4 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6"
                    @submit.prevent="submit"
                >
                    <div v-if="canUseAllScope && !editingId">
                        <label class="block text-sm font-medium text-gray-700">
                            Owner
                        </label>
                        <select
                            v-model="form.owner_scope"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="own">Own</option>
                            <option value="global">Global</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Code
                        </label>
                        <input
                            v-model="form.code"
                            class="mt-1 block w-full rounded-md border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            maxlength="10"
                            required
                        />
                        <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">
                            {{ form.errors.code }}
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Name
                        </label>
                        <input
                            v-model="form.name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Symbol
                        </label>
                        <input
                            v-model="form.symbol"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <label class="flex items-center gap-2 self-end text-sm text-gray-700">
                        <input
                            v-model="form.is_default"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            type="checkbox"
                        />
                        Default
                    </label>

                    <label class="flex items-center gap-2 self-end text-sm text-gray-700">
                        <input
                            v-model="form.enabled"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            type="checkbox"
                        />
                        Enabled
                    </label>

                    <div class="flex gap-2 sm:col-span-2 lg:col-span-6">
                        <button
                            class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 sm:w-auto"
                            type="submit"
                            :disabled="form.processing"
                        >
                            {{ editingId ? 'Update' : 'Create' }}
                        </button>
                        <button
                            v-if="editingId"
                            class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 sm:w-auto"
                            type="button"
                            @click="resetForm"
                        >
                            Cancel
                        </button>
                    </div>
                </form>

                <div class="space-y-3 md:hidden">
                    <div
                        v-for="currency in currencies.data"
                        :key="currency.id"
                        class="rounded-lg bg-white p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold text-gray-900">
                                    {{ currency.code }} · {{ currency.name }}
                                </div>
                                <div class="mt-1 text-sm text-gray-600">
                                    {{ currency.symbol || 'No symbol' }}
                                </div>
                            </div>
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="currency.owner === 'global' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700'"
                            >
                                {{ currency.owner === 'global' ? 'Global' : 'Own' }}
                            </span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">
                                {{ currency.enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                            <span
                                v-if="currency.is_default"
                                class="rounded-full bg-amber-50 px-2 py-1 text-amber-700"
                            >
                                Default
                            </span>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button
                                class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
                                type="button"
                                :disabled="!currency.can_update"
                                @click="editCurrency(currency)"
                            >
                                Edit
                            </button>
                            <button
                                class="flex-1 rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                                type="button"
                                :disabled="!currency.can_delete"
                                @click="destroyCurrency(currency)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div class="hidden overflow-hidden rounded-lg bg-white shadow-sm md:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Code</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Symbol</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Owner</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="currency in currencies.data" :key="currency.id">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ currency.code }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ currency.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ currency.symbol || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="currency.owner === 'global' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700'"
                                    >
                                        {{ currency.owner === 'global' ? 'Global' : 'Own' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ currency.enabled ? 'Enabled' : 'Disabled' }}
                                    <span v-if="currency.is_default"> · Default</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        class="rounded-md px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                        type="button"
                                        :disabled="!currency.can_update"
                                        @click="editCurrency(currency)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        class="rounded-md px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        type="button"
                                        :disabled="!currency.can_delete"
                                        @click="destroyCurrency(currency)"
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
                        v-for="link in currencies.links"
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
