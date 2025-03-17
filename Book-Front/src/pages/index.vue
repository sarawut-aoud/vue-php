<script setup>
import { ref, onMounted } from "vue";
import { Notivue, Notification, push } from "notivue";
import GallaryItems from "@/components/GallaryItems.vue";
import api from "/utils/axios";
import AppHeader from "@/components/AppHeader.vue";
import AppFooter from "@/components/AppFooter.vue";
const theme = ref("light");

const catelists = ref([]);
const getList = async () => {
  let { data } = await api.get("/api/category/getList");
  if (data?.data.length > 0) {
    catelists.value = data.data;
  }
};

const tab = ref("all");
console.log(tab.value);
onMounted(() => {
  getList();
});
const getTheme = (e) => {
  theme.value = e;
};
</script>

<template>
  <v-responsive class="border rounded">
    <v-app :theme="theme" class="position-relative">
      <App-header @themes="getTheme"></App-header>
      <v-main style="padding-top: 100px">
        <v-container>
          <div class="d-flex flex-column ga-2">
            <div class="d-flex align-start flex-column">
              <div style="font-size: 24px; font-weight: 600">ReRead Co.,Ltd.</div>
              <div class="w-100 justify-center d-flex">
                <v-card width="1500" rounded="xl" class="elevation-5">
                  <v-img :src="'/src/assets/dashboard.jpg'" cover></v-img>
                </v-card>
              </div>
            </div>
            <div>
              <Gallary-items :cateId="null"></Gallary-items>
            </div>
          </div>
        </v-container>
      </v-main>

      <AppFooter />
    </v-app>
  </v-responsive>
</template>
