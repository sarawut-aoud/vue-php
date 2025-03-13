<template>
  <v-responsive class="border rounded">
    <v-app :theme="theme" class="position-relative">
      <App-header @themes="getTheme"></App-header>
      <v-main style="padding-top: 100px">
        <v-container border>
          <v-card title="Nutrition" flat>
            <template v-slot:text>
              <v-text-field
                v-model="search"
                label="Search"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                hide-details
                single-line
              ></v-text-field>
            </template>

            <v-data-table :headers="headers" :items="[]" :search="search"></v-data-table>
          </v-card>
        </v-container>
      </v-main>
    </v-app>
  </v-responsive>
  <app-footer></app-footer>
  <Notivue v-slot="item">
    <Notification :item="item" />
  </Notivue>
</template>
<script setup>
import { ref, onMounted, watch } from "vue";
import { Notivue, Notification, push } from "notivue";
import { useJWT } from "@/composables/useJWT";
import { useCookie } from "@/composables/useCookie";
const { getItem } = useJWT();
const { getCookie } = useCookie();
const globalitem = ref(getItem(getCookie("jwt")));
const theme = ref("light");
const getTheme = (e) => {
  theme.value = e;
};
const search = ref(null);
const headers = ref([
  {
    align: "start",
    key: "name",
    sortable: false,
    title: "Dessert (100g serving)",
  },
  { key: "calories", title: "Calories" },
  { key: "fat", title: "Fat (g)" },
  { key: "carbs", title: "Carbs (g)" },
  { key: "protein", title: "Protein (g)" },
  { key: "iron", title: "Iron (%)" },
]);
</script>
