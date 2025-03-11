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
      <!-- <v-navigation-drawer style="padding-top: 30px">
        <v-list>
          <v-list-subheader class="d-flex justify-center"> หมวดหมู่</v-list-subheader>
          <v-list-item value="all" color="info" rounded="xl"
            ><template v-slot:prepend>
              <v-icon icon="mdi-book-open-page-variant"></v-icon>
            </template>
            <v-list-item-title>ทั้งหมด</v-list-item-title>
          </v-list-item>
          <v-list-item
            v-for="(item, i) in catelists"
            :key="i"
            :value="item"
            color="info"
            rounded="xl"
          >
            <template v-slot:prepend>
              <v-icon icon="mdi-book-open-page-variant"></v-icon>
            </template>
            <v-list-item-title>{{ item.name }}</v-list-item-title>
          </v-list-item>
        </v-list>
        <v-divider></v-divider>
      </v-navigation-drawer> -->
      <v-main style="padding-top: 100px">
        <v-container>
          <div class="d-flex flex-column ga-2">
            <div>
              <v-card>
                <v-img
                  height="200px"
                  src="https://cdn.vuetifyjs.com/images/cards/sunshine.jpg"
                  cover
                ></v-img>
                <v-card-title> Top western road trips </v-card-title>
                <v-card-subtitle> 1,000 miles of wonder </v-card-subtitle>
                <v-card-actions>
                  <v-btn color="orange-lighten-2" text="Explore"></v-btn>
                  <v-spacer></v-spacer>
                </v-card-actions>
              </v-card>
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
