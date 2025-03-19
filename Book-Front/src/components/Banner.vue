<template>
  <v-card width="1500" rounded="xl" class="elevation-5" flat>
    <v-window v-model="onboarding">
      <v-window-item v-for="(item, n) in path" :key="`card-${n}`" :value="n">
        <v-img
          height="500"
          :src="item"
          :lazy-src="`https://picsum.photos/10/6?image=${n * 5 + 10}`"
          cover
        ></v-img>
      </v-window-item>
    </v-window>

    <v-card-actions class="justify-space-between">
      <v-btn icon="mdi-chevron-left" variant="plain" @click="prev"></v-btn>
      <v-item-group v-model="onboarding" class="text-center" mandatory>
        <v-item
          v-for="(item, n) in path"
          :key="`btn-${n}`"
          v-slot="{ isSelected, toggle }"
          :value="n"
        >
          <v-btn
            :variant="isSelected ? 'outlined' : 'text'"
            icon="mdi-record"
            @click="toggle"
            size="small"
          ></v-btn>
        </v-item>
      </v-item-group>
      <v-btn icon="mdi-chevron-right" variant="plain" @click="next"></v-btn>
    </v-card-actions>
  </v-card>
</template>
<script setup>
import { ref, onMounted } from "vue";

const path = ref([
  "/api/assets/dashboard.jpg",
  "/api/assets/banner2.jpg",
  "/api/assets/banner3.jpg",
  "/api/assets/banner4.jpg",
]);
const onboarding = ref(0);

const next = () => {
  onboarding.value = onboarding.value + 1 > 3 ? 0 : onboarding.value + 1;
};
const prev = () => {
  onboarding.value = onboarding.value - 1 <= -1 ? 3 : onboarding.value - 1;
};
onMounted(() => {
  setInterval(() => {
    next();
  }, 6000);
});
</script>
