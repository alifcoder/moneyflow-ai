<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
});

const filters = reactive({
    search: props.filters.search || '',
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
});

const cleanFilters = () =>
    Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );

const applyFilters = () => {
    router.get(route('users.index'), cleanFilters(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const submit = () => {
    form.post(route('users.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const impersonate = (user) => {
    if (!user.can_impersonate || !window.confirm(`Log in as ${user.name}?`)) {
        return;
    }

    router.post(route('users.impersonate', user.id));
};
</script>

<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Users
            </h2>
        </template>

        <div class="py-6 sm:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <form
                    class="mb-6 grid gap-4 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4"
                    @submit.prevent="applyFilters"
                >
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Search
                        </label>
                        <input
                            v-model="filters.search"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            type="search"
                        />
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
                            :href="route('users.index')"
                        >
                            Reset
                        </Link>
                    </div>
                </form>

                <form
                    class="mb-6 grid gap-4 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6"
                    @submit.prevent="submit"
                >
                    <div>
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
                            Email
                        </label>
                        <input
                            v-model="form.email"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                            type="email"
                        />
                        <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <input
                            v-model="form.password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                            type="password"
                        />
                        <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Confirm password
                        </label>
                        <input
                            v-model="form.password_confirmation"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                            type="password"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Role
                        </label>
                        <select
                            v-model="form.role"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option
                                v-for="role in roles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                        <div v-if="form.errors.role" class="mt-1 text-sm text-red-600">
                            {{ form.errors.role }}
                        </div>
                    </div>

                    <div class="flex items-end">
                        <button
                            class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 sm:w-auto"
                            type="submit"
                            :disabled="form.processing"
                        >
                            Create user
                        </button>
                    </div>
                </form>

                <div
                    v-if="users.data.length === 0"
                    class="rounded-lg bg-white p-8 text-center text-gray-600 shadow-sm"
                >
                    No users found.
                </div>

                <div v-else class="space-y-3 md:hidden">
                    <div
                        v-for="user in users.data"
                        :key="user.id"
                        class="rounded-lg bg-white p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-gray-900">
                                    {{ user.name }}
                                </div>
                                <div class="mt-1 truncate text-sm text-gray-600">
                                    {{ user.email }}
                                </div>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2 py-1 text-xs font-medium"
                                :class="user.role === 'super_admin' ? 'bg-purple-50 text-purple-700' : 'bg-emerald-50 text-emerald-700'"
                            >
                                {{ user.role_label }}
                            </span>
                        </div>

                        <div class="mt-4">
                            <button
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
                                type="button"
                                :disabled="!user.can_impersonate"
                                @click="impersonate(user)"
                            >
                                Login as user
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="users.data.length > 0"
                    class="hidden rounded-lg bg-white shadow-sm md:block md:overflow-x-auto"
                >
                    <table class="w-full min-w-[48rem] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Role</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Verified</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ user.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ user.email }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="user.role === 'super_admin' ? 'bg-purple-50 text-purple-700' : 'bg-emerald-50 text-emerald-700'"
                                    >
                                        {{ user.role_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ user.email_verified_at ? 'Yes' : 'No' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        class="rounded-md px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                        type="button"
                                        :disabled="!user.can_impersonate"
                                        @click="impersonate(user)"
                                    >
                                        Login as user
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="link in users.links"
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
