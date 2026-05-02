<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    cashboxes: Object,
    currencies: Array,
    filters: Object,
    canUseAllScope: Boolean,
});

const editingId = ref(null);

const filters = reactive({
    search: props.filters.search || '',
    currency_id: props.filters.currency_id || '',
    enabled: props.filters.enabled ?? '',
    scope: props.filters.scope || '',
});

const form = useForm({
    owner_scope: 'own',
    currency_id: props.currencies[0]?.id || '',
    name: '',
    is_default: false,
    enabled: true,
});

const cleanFilters = () =>
    Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );

const applyFilters = () => {
    router.get(route('cashboxes.index'), cleanFilters(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.owner_scope = 'own';
    form.currency_id = props.currencies[0]?.id || '';
    form.is_default = false;
    form.enabled = true;
};

const editCashbox = (cashbox) => {
    editingId.value = cashbox.id;
    form.clearErrors();
    form.currency_id = cashbox.currency_id;
    form.name = cashbox.name;
    form.is_default = cashbox.is_default;
    form.enabled = cashbox.enabled;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    };

    if (editingId.value) {
        form.put(route('cashboxes.update', editingId.value), options);

        return;
    }

    form.post(route('cashboxes.store'), options);
};

const destroyCashbox = (cashbox) => {
    if (!cashbox.can_delete || !window.confirm('Delete this cashbox?')) {
        return;
    }

    router.delete(route('cashboxes.destroy', cashbox.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Cashboxes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Cashboxes
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
                            Currency
                        </label>
                        <select
                            v-model="filters.currency_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option
                                v-for="currency in currencies"
                                :key="currency.id"
                                :value="currency.id"
                            >
                                {{ currency.code }}
                            </option>
                        </select>
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
                            :href="route('cashboxes.index')"
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
                            Currency
                        </label>
                        <select
                            v-model="form.currency_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option
                                v-for="currency in currencies"
                                :key="currency.id"
                                :value="currency.id"
                            >
                                {{ currency.code }} · {{ currency.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.currency_id" class="mt-1 text-sm text-red-600">
                            {{ form.errors.currency_id }}
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
                            :disabled="form.processing || currencies.length === 0"
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
                        v-for="cashbox in cashboxes.data"
                        :key="cashbox.id"
                        class="rounded-lg bg-white p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold text-gray-900">
                                    {{ cashbox.name }}
                                </div>
                                <div class="mt-1 text-sm text-gray-600">
                                    {{ cashbox.currency.code }} · {{ cashbox.currency.name }}
                                </div>
                            </div>
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="cashbox.owner === 'global' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700'"
                            >
                                {{ cashbox.owner === 'global' ? 'Global' : 'Own' }}
                            </span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">
                                {{ cashbox.enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                            <span
                                v-if="cashbox.is_default"
                                class="rounded-full bg-amber-50 px-2 py-1 text-amber-700"
                            >
                                Default
                            </span>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button
                                class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
                                type="button"
                                :disabled="!cashbox.can_update"
                                @click="editCashbox(cashbox)"
                            >
                                Edit
                            </button>
                            <button
                                class="flex-1 rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                                type="button"
                                :disabled="!cashbox.can_delete"
                                @click="destroyCashbox(cashbox)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div class="hidden rounded-lg bg-white shadow-sm md:block md:overflow-x-auto">
                    <table class="min-w-[42rem] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Currency</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Owner</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="cashbox in cashboxes.data" :key="cashbox.id">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ cashbox.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ cashbox.currency.code }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="cashbox.owner === 'global' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700'"
                                    >
                                        {{ cashbox.owner === 'global' ? 'Global' : 'Own' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ cashbox.enabled ? 'Enabled' : 'Disabled' }}
                                    <span v-if="cashbox.is_default"> · Default</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        class="rounded-md px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                        type="button"
                                        :disabled="!cashbox.can_update"
                                        @click="editCashbox(cashbox)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        class="rounded-md px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        type="button"
                                        :disabled="!cashbox.can_delete"
                                        @click="destroyCashbox(cashbox)"
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
                        v-for="link in cashboxes.links"
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
