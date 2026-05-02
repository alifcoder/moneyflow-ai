<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    mode: String,
    transaction: Object,
    options: Object,
    types: Array,
});

const isEdit = computed(() => props.mode === 'edit');

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    transaction_date: props.transaction?.transaction_date || today,
    type: props.transaction?.type || 'expense',
    category_id: props.transaction?.category_id || '',
    cashbox_id: props.transaction?.cashbox_id || '',
    currency_id: props.transaction?.currency_id || '',
    amount: props.transaction?.amount || '',
    comment: props.transaction?.comment || '',
});

const filteredCategories = computed(() =>
    props.options.categories.filter((category) => category.type === form.type),
);

watch(
    () => form.type,
    () => {
        if (!filteredCategories.value.some((category) => category.id === Number(form.category_id))) {
            form.category_id = filteredCategories.value[0]?.id || '';
        }
    },
);

watch(
    () => form.cashbox_id,
    () => {
        const cashbox = props.options.cashboxes.find((item) => item.id === Number(form.cashbox_id));

        if (cashbox && !form.currency_id) {
            form.currency_id = cashbox.currency_id;
        }
    },
);

if (!form.category_id && filteredCategories.value.length > 0) {
    form.category_id = filteredCategories.value[0].id;
}

if (!form.cashbox_id && props.options.cashboxes.length > 0) {
    form.cashbox_id = props.options.cashboxes[0].id;
}

if (!form.currency_id && props.options.currencies.length > 0) {
    form.currency_id = props.options.currencies[0].id;
}

const submit = () => {
    if (isEdit.value) {
        form.put(route('transactions.update', props.transaction.id));

        return;
    }

    form.post(route('transactions.store'));
};
</script>

<template>
    <Head :title="isEdit ? 'Edit transaction' : 'New transaction'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ isEdit ? 'Edit transaction' : 'New transaction' }}
                </h2>
                <Link
                    class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    :href="route('transactions.index')"
                >
                    Back
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-10">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <form
                    class="grid gap-4 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Date
                        </label>
                        <input
                            v-model="form.transaction_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            type="date"
                            required
                        />
                        <div v-if="form.errors.transaction_date" class="mt-1 text-sm text-red-600">
                            {{ form.errors.transaction_date }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Type
                        </label>
                        <select
                            v-model="form.type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option v-for="type in types" :key="type" :value="type">
                                {{ type }}
                            </option>
                        </select>
                        <div v-if="form.errors.type" class="mt-1 text-sm text-red-600">
                            {{ form.errors.type }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Category
                        </label>
                        <select
                            v-model="form.category_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option
                                v-for="category in filteredCategories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">
                            {{ form.errors.category_id }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Cashbox
                        </label>
                        <select
                            v-model="form.cashbox_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option
                                v-for="cashbox in options.cashboxes"
                                :key="cashbox.id"
                                :value="cashbox.id"
                            >
                                {{ cashbox.name }} · {{ cashbox.currency_code }}
                            </option>
                        </select>
                        <div v-if="form.errors.cashbox_id" class="mt-1 text-sm text-red-600">
                            {{ form.errors.cashbox_id }}
                        </div>
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
                                v-for="currency in options.currencies"
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

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Amount
                        </label>
                        <input
                            v-model="form.amount"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            min="0.0001"
                            step="0.0001"
                            type="number"
                            required
                        />
                        <div v-if="form.errors.amount" class="mt-1 text-sm text-red-600">
                            {{ form.errors.amount }}
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Comment
                        </label>
                        <textarea
                            v-model="form.comment"
                            class="mt-1 block min-h-28 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <div v-if="form.errors.comment" class="mt-1 text-sm text-red-600">
                            {{ form.errors.comment }}
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row">
                        <button
                            class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 sm:w-auto"
                            type="submit"
                            :disabled="form.processing"
                        >
                            {{ isEdit ? 'Update transaction' : 'Create transaction' }}
                        </button>
                        <Link
                            class="w-full rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 sm:w-auto"
                            :href="route('transactions.index')"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
