<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const date = ref('');
const time = ref('11:59');

interface Draws {
  choice1: string[];
  random3: string[];
  dream4: string[];
  star5: string[];
  win6: string[];
  lucky7: string[];
}

const draws = ref<Draws>({
  choice1: [''],
  random3: ['', '', ''],
  dream4: ['', '', '', ''],
  star5: ['', '', '', '', ''],
  win6: ['', '', '', '', '', ''],
  lucky7: ['', '', '', '', '', '', '']
});

const generateNumbers = (type: keyof Draws, count: number) => {
  const newNumbers = Array.from({ length: count }, () =>
    Math.floor(Math.random() * 10).toString()
  );
  draws.value[type] = newNumbers;
};

const copyNumbers = (numbers: string[]) => {
  navigator.clipboard.writeText(numbers.join(''));
};

const handleNumberChange = (type: keyof Draws, index: number, value: string) => {
  if (value.length <= 1 && /^\d*$/.test(value)) {
    draws.value[type][index] = value;
  }
};

const clearAll = () => {
  draws.value = {
    choice1: [''],
    random3: ['', '', ''],
    dream4: ['', '', '', ''],
    star5: ['', '', '', '', ''],
    win6: ['', '', '', '', '', ''],
    lucky7: ['', '', '', '', '', '', '']
  };
  date.value = '';
  time.value = '11:59';
};

const saveDraw = () => {
  console.log('Saving draw:', { date: date.value, time: time.value, draws: draws.value });
  alert('Draw saved successfully!');
};

const drawTypes = [
  { key: 'choice1' as keyof Draws, label: 'Choice-1', count: 1 },
  { key: 'random3' as keyof Draws, label: 'Random 3', count: 3 },
  { key: 'dream4' as keyof Draws, label: 'Dream 4', count: 4 },
  { key: 'star5' as keyof Draws, label: 'Star 5', count: 5 },
  { key: 'win6' as keyof Draws, label: 'Win 6', count: 6 },
  { key: 'lucky7' as keyof Draws, label: 'Lucky 7', count: 7 }
];
</script>

<template>
  <AppLayout>
    <div class="p-2 bg-gradient-to-br from-gray-50 to-gray-100">
      <div class=" mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
          <!-- Date & Time Selection -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Select Date & Time
            </label>
            <div class="flex gap-4">
              <div class="relative flex-1">
                <Input
                  type="date"
                  v-model="date"
                  class="w-full"
                  placeholder="mm/dd/yyyy"
                />
              </div>
              <div class="relative flex-1">
                <Input
                  type="time"
                  v-model="time"
                  class="w-full"
                />
              </div>
            </div>
          </div>

          <!-- Draw Table -->
          <div class="border rounded-lg overflow-hidden">
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
                <tr
                  v-for="(drawType, index) in drawTypes"
                  :key="drawType.key"
                  :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                >
                  <td class="px-6 py-4 font-medium text-gray-900 border-r">
                    {{ drawType.label }}
                  </td>
                  <td class="px-6 py-4 border-r">
                    <div class="flex gap-2 justify-center">
                      <Input
                        v-for="(num, idx) in draws[drawType.key]"
                        :key="idx"
                        type="text"
                        :value="num"
                        @input="(e) => handleNumberChange(drawType.key, idx, (e.target as HTMLInputElement).value)"
                        class="w-12 h-12 text-center text-lg font-semibold"
                        maxlength="1"
                      />
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex gap-2 justify-center">
                      <Button
                        @click="generateNumbers(drawType.key, drawType.count)"
                        variant="outline"
                        size="sm"
                      >
                        Generate
                      </Button>
                      <Button
                        @click="copyNumbers(draws[drawType.key])"
                        variant="outline"
                        size="sm"
                      >
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
            <Button
              @click="saveDraw"
              class="bg-blue-500 hover:bg-blue-600 text-white"
            >
              Save Draw
            </Button>
            <Button
              @click="clearAll"
              variant="destructive"
            >
              Clear All
            </Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
