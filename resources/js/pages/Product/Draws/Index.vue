<script setup lang="ts">
import { reactive, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/vue3';


const { categories, products, filters } = defineProps<{
    categories: Array<any>;
    filters: Array<any>;
    products: Array<any>;
}>();

const filter = ref({
    category_id: filters?.category_id ?? ''
});

const date = ref('');
const time = ref('11:59');

const drawNumbers = reactive<Record<number, string[]>>({});
products.forEach((product: any) => {
    drawNumbers[product.id] = Array(product.pick_number).fill('');
});

const generateForProduct = (product: any) => {
    drawNumbers[product.id] = Array.from(
        { length: product.pick_number },
        () => Math.floor(Math.random() * 10).toString()
    );
};

const copyProductNumbers = (product: any) => {
    navigator.clipboard.writeText(
        drawNumbers[product.id].join('')
    );
};

const handleNumberChange = (
    product: any,
    index: number,
    value: string
) => {
    if (/^\d?$/.test(value)) {
        drawNumbers[product.id][index] = value;
    }
};

const clearAll = () => {
    products.forEach((product: any) => {
        drawNumbers[product.id] = Array(product.pick_number).fill('');
    });

    // Optional resets
    date.value = '';
    time.value = '11:59';
};


const handleSearch = () => {
    router.get(
        route('draws.index'),
        { ...filter.value },
        {
            preserveScroll: true,
            replace: true,
            showProgress: false
        }
    );
};
</script>

<template>
    <AppLayout>
        <div class="p-2 bg-gradient-to-br from-gray-50 to-gray-100">
            <div class=" mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <!-- Date & Time Selection -->
                    <div class="mb-6">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Select Category
                                </label>
                                <select v-model="filter.category_id" v-on:change="handleSearch"
                                    class="w-full border-2 border-gray-200 px-2 py-1.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                                    <option value="">Select Category</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Select Date
                                </label>
                                <Input type="date" v-model="date" class="w-full" placeholder="mm/dd/yyyy" />
                            </div>
                            <div class="relative flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Select Time
                                </label>
                                <Input type="time" v-model="time" class="w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- Draw Table -->
                    <div class="border rounded-lg overflow-y-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 border-r">
                                        Type
                                    </th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700 border-r">
                                        Numbers
                                    </th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(product, index) in products" :key="index"
                                    :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                    <td
                                        class="px-3 text-sm md:text-md md:px-6 py-2 md:py-4 font-medium text-gray-900 border-r">
                                        {{ product.title }}
                                    </td>
                                    <td class="px-6 py-4 border-r">
                                        <div class="flex gap-2 justify-center">
                                            <Input v-for="(_, idx) in product.pick_number" :key="idx" type="text"
                                                :value="drawNumbers[product.id]?.[idx]"
                                                @input="handleNumberChange(product, idx, $event.target.value)"
                                                class="w-8 md:w-12 h-8 md:h-12 text-center text-lg font-semibold"
                                                maxlength="1" />
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex gap-2 justify-center">
                                            <Button variant="outline" size="sm" @click="generateForProduct(product)">
                                                Generate
                                            </Button>

                                            <Button size="sm" @click="copyProductNumbers(product)">
                                                Copy
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 mt-6">
                        <Button class="bg-blue-500 hover:bg-blue-600 text-white">
                            Save Draw
                        </Button>
                        <Button variant="destructive" @click="clearAll">
                            Clear All
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
