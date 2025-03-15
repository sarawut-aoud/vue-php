<template>
  <v-window show-arrows>
    <template v-slot:prev="{ props }">
      <v-btn
        class="mt-auto mb-2"
        icon="mdi-chevron-left"
        size="small"
        variant="outlined"
        color="white"
        @click="props.onClick"
      >
      </v-btn>
    </template>
    <template v-slot:next="{ props }">
      <v-btn
        class="mt-auto mb-2"
        icon="mdi-chevron-right"
        size="small"
        variant="outlined"
        color="white"
        @click="props.onClick"
      >
      </v-btn>
    </template>
    <v-window-item v-for="n in props.images" :key="`card-${n}`">
      <v-img
        :lazy-src="`https://picsum.photos/10/6?image=${i * n * 5 + 10}`"
        :src="n.path"
        height="205"
        width="400"
        cover
      ></v-img>
    </v-window-item>
  </v-window>
</template>
<script setup>
import { defineProps, ref } from "vue";
const props = defineProps({
  images: Array,
});
</script>
